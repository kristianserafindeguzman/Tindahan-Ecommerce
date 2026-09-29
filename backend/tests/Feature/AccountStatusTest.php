<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\SemaphoreService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Covers the pending status that keeps unfinished sign-ups apart from accounts an admin set inactive, run against a MySQL *_test database because older migrations use MySQL-only SQL. */
class AccountStatusTest extends TestCase
{
    use RefreshesTestDatabase;

    /** Codes the fake SMS gateway was asked to send, keyed by phone number. */
    private array $sent = [];

    /** Hands each made-up user its own phone number, in a range the tests never type in by hand. */
    private int $phoneCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Replaces the SMS gateway so no test ever sends a real text, while keeping each code so it can be entered.
        $this->mock(SemaphoreService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturnUsing(function (string $phone, string $code) {
                $this->sent[$phone] = $code;
            });
        });
    }

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'role' => 'Consumer',
            'full_name' => 'Test User',
            'email' => 'user' . uniqid() . '@example.com',
            'phone_number' => sprintf('0918%07d', ++$this->phoneCounter),
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ], $attributes));
    }

    private function signUp(array $overrides = [])
    {
        return $this->postJson('/api/register/consumer', array_merge([
            'full_name' => 'Juan Cruz',
            'birthday' => '1995-06-15',
            'email' => 'juan@example.com',
            'phone_number' => '09171234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides));
    }

    public function test_a_new_sign_up_is_saved_as_pending(): void
    {
        $this->signUp()->assertCreated();

        $this->assertSame('pending', User::where('email', 'juan@example.com')->value('account_status'));
        $this->assertArrayHasKey('09171234567', $this->sent);
    }

    public function test_a_sign_up_saves_the_birthday(): void
    {
        $this->signUp()->assertCreated();

        $this->assertSame('1995-06-15', User::where('email', 'juan@example.com')->first()->birthday->format('Y-m-d'));
    }

    public function test_a_sign_up_needs_a_real_past_birthday(): void
    {
        $this->signUp(['birthday' => ''])->assertStatus(422)->assertJsonValidationErrors('birthday');
        $this->signUp(['birthday' => now()->addDay()->format('Y-m-d')])->assertStatus(422)->assertJsonValidationErrors('birthday');
        $this->signUp(['birthday' => '15/06/1995'])->assertStatus(422)->assertJsonValidationErrors('birthday');
        $this->signUp(['birthday' => '1899-12-31'])->assertStatus(422)->assertJsonValidationErrors('birthday');

        $this->assertSame(0, User::count());
    }

    public function test_signing_up_again_replaces_an_unfinished_sign_up(): void
    {
        $this->signUp()->assertCreated();
        $this->signUp(['full_name' => 'Juana Cruz', 'phone_number' => '09179999999'])->assertCreated();

        $users = User::where('email', 'juan@example.com')->get();
        $this->assertCount(1, $users);
        $this->assertSame('09179999999', $users->first()->phone_number);
        $this->assertSame('pending', $users->first()->account_status);
    }

    public function test_signing_up_cannot_take_over_an_account_an_admin_set_inactive(): void
    {
        $victim = $this->makeUser([
            'email' => 'nena@example.com',
            'phone_number' => '09170000001',
            'account_status' => 'inactive',
            'suspension_message' => 'Set to inactive by DTI Negosyo Admin.',
        ]);

        $this->signUp(['email' => 'nena@example.com', 'phone_number' => '09175555555'])->assertStatus(422);

        $victim->refresh();
        $this->assertSame('09170000001', $victim->phone_number);
        $this->assertSame('inactive', $victim->account_status);
        $this->assertTrue(Hash::check('secret123', $victim->password_hash));
        $this->assertSame([], $this->sent);
    }

    public function test_signing_up_cannot_take_over_a_vendor_account_through_its_phone_number(): void
    {
        $vendor = $this->makeUser(['role' => 'Vendor', 'phone_number' => '09170000002', 'account_status' => 'inactive']);

        $this->signUp(['email' => 'someone@example.com', 'phone_number' => '09170000002'])->assertStatus(422);

        $vendor->refresh();
        $this->assertSame('Vendor', $vendor->role);
        $this->assertTrue(Hash::check('secret123', $vendor->password_hash));
    }

    public function test_signing_up_with_a_deleted_accounts_email_is_refused_cleanly(): void
    {
        $deleted = $this->makeUser(['email' => 'gone@example.com', 'account_status' => 'deleted']);
        $deleted->delete();

        $this->signUp(['email' => 'gone@example.com', 'phone_number' => '09170000008'])->assertStatus(422);
    }

    public function test_signing_up_is_refused_when_the_email_and_phone_belong_to_two_different_sign_ups(): void
    {
        $this->makeUser(['email' => 'a@example.com', 'account_status' => 'pending']);
        $this->makeUser(['phone_number' => '09170000003', 'account_status' => 'pending']);

        $this->signUp(['email' => 'a@example.com', 'phone_number' => '09170000003'])->assertStatus(422);
    }

    public function test_verifying_a_sign_up_code_activates_the_pending_account(): void
    {
        $this->signUp()->assertCreated();

        $this->postJson('/api/otp/verify', [
            'phone_number' => '09171234567',
            'code' => $this->sent['09171234567'],
            'type' => 'registration',
        ])->assertOk();

        $this->assertSame('active', User::where('email', 'juan@example.com')->value('account_status'));
    }

    public function test_a_sign_up_code_cannot_reactivate_an_account_an_admin_set_inactive(): void
    {
        $user = $this->makeUser(['phone_number' => '09170000004', 'account_status' => 'inactive']);
        OtpCode::create([
            'phone_number' => '09170000004',
            'code' => Hash::make('123456'),
            'type' => 'registration',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/otp/verify', [
            'phone_number' => '09170000004',
            'code' => '123456',
            'type' => 'registration',
        ]);

        $this->assertSame('inactive', $user->refresh()->account_status);
    }

    public function test_a_sign_up_code_is_not_sent_to_an_account_an_admin_set_inactive(): void
    {
        $this->makeUser(['phone_number' => '09170000005', 'account_status' => 'inactive']);

        $this->postJson('/api/otp/resend', ['phone_number' => '09170000005', 'type' => 'registration'])->assertNotFound();

        $this->assertSame([], $this->sent);
    }

    public function test_a_sign_up_code_is_resent_to_a_pending_account(): void
    {
        $this->makeUser(['phone_number' => '09170000006', 'account_status' => 'pending']);

        $this->postJson('/api/otp/resend', ['phone_number' => '09170000006', 'type' => 'registration'])->assertOk();

        $this->assertArrayHasKey('09170000006', $this->sent);
    }

    public function test_a_password_reset_code_is_not_resent_to_a_suspended_account(): void
    {
        $this->makeUser(['phone_number' => '09170000007', 'account_status' => 'suspended']);

        $this->postJson('/api/otp/resend', ['phone_number' => '09170000007', 'type' => 'password_reset'])->assertForbidden();

        $this->assertSame([], $this->sent);
    }

    public function test_login_tells_a_pending_sign_up_to_verify_its_number(): void
    {
        $this->signUp()->assertCreated();

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'password123'])
            ->assertForbidden()
            ->assertJson(['account_status' => 'pending', 'contact_support' => true]);
    }

    public function test_login_shows_an_inactive_account_its_notice(): void
    {
        $this->makeUser([
            'email' => 'inactive@example.com',
            'account_status' => 'inactive',
            'suspension_message' => 'Set to inactive by DTI Negosyo Admin.',
        ]);

        $this->postJson('/api/login', ['email' => 'inactive@example.com', 'password' => 'secret123'])
            ->assertForbidden()
            ->assertJson(['account_status' => 'inactive', 'notice' => 'Set to inactive by DTI Negosyo Admin.']);
    }

    public function test_an_admin_setting_an_account_inactive_saves_who_did_it(): void
    {
        $admin = $this->makeUser(['role' => 'Admin', 'full_name' => 'DTI Negosyo Admin']);
        $consumer = $this->makeUser();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/consumers/{$consumer->user_id}/status", ['account_status' => 'inactive'])->assertOk();

        $consumer->refresh();
        $this->assertSame('inactive', $consumer->account_status);
        $this->assertSame('Set to inactive by DTI Negosyo Admin.', $consumer->suspension_message);
    }
}
