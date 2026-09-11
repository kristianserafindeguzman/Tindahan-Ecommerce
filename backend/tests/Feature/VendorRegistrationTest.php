<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\Store;
use App\Models\User;
use App\Services\SemaphoreService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Covers the vendor sign-up's phone check, where a texted code must be verified by the same browser before the store can be registered. */
class VendorRegistrationTest extends TestCase
{
    use RefreshesTestDatabase;

    /** Codes the fake SMS gateway was asked to send, keyed by phone number. */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Replaces the SMS gateway so no test ever sends a real text, while keeping each code so it can be entered.
        $this->mock(SemaphoreService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturnUsing(function (string $phone, string $code) {
                $this->sent[$phone] = $code;
            });
        });
    }

    private function sendCode(array $overrides = [])
    {
        return $this->postJson('/api/register/vendor/otp', array_merge([
            'email' => 'nena@example.com',
            'phone_number' => '09171234567',
        ], $overrides));
    }

    /** Verifies the last code texted to the phone and returns the one-time token the verification hands back. */
    private function verify(string $phone = '09171234567'): string
    {
        return $this->postJson('/api/otp/verify', [
            'phone_number' => $phone,
            'code' => $this->sent[$phone],
            'type' => 'registration',
        ])->assertOk()->json('verification_token');
    }

    private function register(array $overrides = [])
    {
        // A real 1x1 PNG, so the image rule accepts it without needing the GD extension.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return $this->post('/api/register/vendor', array_merge([
            'full_name' => 'Nena Cruz',
            'email' => 'nena@example.com',
            'phone_number' => '09171234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'verification_token' => 'missing',
            'store_name' => 'Aling Nena Store',
            'store_picture' => UploadedFile::fake()->createWithContent('store.png', $png),
            'operating_days' => json_encode(['Monday' => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '20:00']]),
            'opening_time' => '08:00',
            'closing_time' => '20:00',
            'latitude' => 14.5764,
            'longitude' => 121.0851,
            'address' => 'Pasig City',
        ], $overrides), ['Accept' => 'application/json']);
    }

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'role' => 'Consumer',
            'full_name' => 'Existing User',
            'email' => 'existing@example.com',
            'phone_number' => '09180000001',
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ], $attributes));
    }

    public function test_the_store_name_and_storefront_photo_are_required(): void
    {
        $this->register(['store_name' => '   ', 'store_picture' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['store_name', 'store_picture']);

        $this->assertDatabaseMissing('users', ['email' => 'nena@example.com']);
    }

    public function test_a_code_is_texted_to_a_new_vendor_number(): void
    {
        $this->sendCode()->assertOk();

        $this->assertArrayHasKey('09171234567', $this->sent);
    }

    public function test_no_code_is_sent_to_a_phone_that_is_already_registered(): void
    {
        $this->makeUser(['phone_number' => '09171234567']);

        $this->sendCode()->assertStatus(422)->assertJsonValidationErrors('phone_number');
        $this->assertSame([], $this->sent);
    }

    public function test_no_code_is_sent_for_an_email_that_is_already_registered(): void
    {
        $this->makeUser(['email' => 'nena@example.com']);

        $this->sendCode()->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertSame([], $this->sent);
    }

    public function test_a_vendor_cannot_register_without_verifying_the_phone(): void
    {
        $this->sendCode()->assertOk();

        $this->register()->assertStatus(422)->assertJsonValidationErrors('phone_number');
        $this->assertSame(0, User::count());
    }

    public function test_a_vendor_registers_after_verifying_and_the_verification_is_used_up(): void
    {
        $this->sendCode()->assertOk();
        $token = $this->verify();

        $this->register(['verification_token' => $token])->assertCreated();

        $vendor = User::where('email', 'nena@example.com')->first();
        $this->assertSame('Vendor', $vendor->role);
        $this->assertSame('active', $vendor->account_status);
        $this->assertTrue(Store::where('owner_id', $vendor->user_id)->exists());
        $this->assertSame(0, OtpCode::where('phone_number', '09171234567')->whereNotNull('verified_at')->count());
    }

    public function test_someone_else_cannot_use_a_verification_they_did_not_receive(): void
    {
        $this->sendCode()->assertOk();
        $this->verify();

        $this->register(['verification_token' => str_repeat('x', 64)])->assertStatus(422)->assertJsonValidationErrors('phone_number');
        $this->assertSame(0, User::count());
    }

    public function test_a_verification_older_than_30_minutes_is_refused(): void
    {
        $this->sendCode()->assertOk();
        $token = $this->verify();
        OtpCode::where('phone_number', '09171234567')->update(['verified_at' => now()->subMinutes(31)]);

        $this->register(['verification_token' => $token])->assertStatus(422)->assertJsonValidationErrors('phone_number');
    }

    public function test_a_fake_code_is_ignored_outside_a_local_machine(): void
    {
        config(['services.semaphore.fake_code' => '123456']);

        $this->sendCode()->assertOk();

        $this->assertArrayHasKey('09171234567', $this->sent);
    }

    public function test_a_fake_code_on_a_local_machine_sends_no_text_and_verifies(): void
    {
        config(['services.semaphore.fake_code' => '123456']);
        $this->app['env'] = 'local';

        $this->sendCode()->assertOk();
        $this->assertSame([], $this->sent);

        $this->sent['09171234567'] = '123456';
        $token = $this->verify();
        $this->register(['verification_token' => $token])->assertCreated();
    }

    public function test_a_used_verification_cannot_register_a_second_vendor(): void
    {
        $this->sendCode()->assertOk();
        $token = $this->verify();
        $this->register(['verification_token' => $token])->assertCreated();

        $this->register(['verification_token' => $token, 'email' => 'second@example.com'])->assertStatus(422);
        $this->assertSame(1, User::where('role', 'Vendor')->count());
    }
}
