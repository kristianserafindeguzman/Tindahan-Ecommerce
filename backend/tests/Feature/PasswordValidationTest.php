<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\OtpService;
use App\Services\SemaphoreService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordValidationTest extends TestCase
{
    private array $sent = [];
    private User $consumer;
    private User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('role');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('password_hash');
            $table->string('account_status')->default('active');
            $table->string('profile_picture')->nullable();
            $table->date('birthday')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
        });
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');
            $table->unsignedBigInteger('owner_id');
            $table->string('store_name');
            $table->string('store_picture');
            $table->string('opening_time');
            $table->string('closing_time');
            $table->text('operating_days');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
        });
        Schema::create('approval_status', function (Blueprint $table) {
            $table->id('approval_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('status');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone_number');
            $table->string('code');
            $table->string('type');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        $this->consumer = User::create([
            'role' => 'Consumer', 'full_name' => 'Test Consumer', 'email' => 'consumer@example.com',
            'phone_number' => '09171234567', 'password_hash' => 'secret123', 'account_status' => 'active',
        ]);
        $this->vendor = User::create([
            'role' => 'Vendor', 'full_name' => 'Test Vendor', 'email' => 'vendor@example.com',
            'phone_number' => '09171234568', 'password_hash' => 'secret123', 'account_status' => 'active',
        ]);
        $storeId = DB::table('stores')->insertGetId([
            'owner_id' => $this->vendor->user_id, 'store_name' => 'Approved Store',
            'store_picture' => 'store.png', 'opening_time' => '08:00', 'closing_time' => '18:00',
            'operating_days' => '{}', 'latitude' => 14.6, 'longitude' => 121.0,
        ], 'store_id');
        DB::table('approval_status')->insert(['store_id' => $storeId, 'status' => 'approved']);
        Storage::fake('public');
        $this->mock(SemaphoreService::class)->shouldNotReceive('sendOtp');
        $this->mock(OtpService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $type) {
                $this->sent[] = [$phone, $type];
            });
            $mock->shouldReceive('verify')->withAnyArgs()->andReturn(['ok' => true]);
        });
    }

    public static function invalidPasswords(): array
    {
        return [
            'too short' => ['Abcdef!', 'Password must be at least 8 characters.'],
            'no uppercase' => ['abcdefg!', 'Password must contain at least one uppercase letter.'],
            'no lowercase' => ['ABCDEFG!', 'Password must contain at least one lowercase letter.'],
            'no symbol' => ['Abcdefgh', 'Password must contain at least one symbol.'],
            'space is not a symbol' => ['Abcdefg ', 'Password must contain at least one symbol.'],
            'unicode character count' => ["Abcdef\u{1F600}", 'Password must be at least 8 characters.'],
        ];
    }

    private function consumerPayload(string $password): array
    {
        return [
            'full_name' => 'New Consumer', 'email' => 'new@example.com', 'phone_number' => '09179999991',
            'birthday' => '1995-06-15', 'password' => $password, 'password_confirmation' => $password,
        ];
    }

    private function vendorPayload(string $password): array
    {
        return [
            'full_name' => 'New Vendor', 'email' => 'newvendor@example.com', 'phone_number' => '09179999992',
            'password' => $password, 'password_confirmation' => $password, 'verification_token' => 'verified-token',
            'store_name' => 'Test Store', 'store_picture' => UploadedFile::fake()->image('store.png'),
            'operating_days' => json_encode(['Monday' => ['is_open' => true]]),
            'opening_time' => '08:00', 'closing_time' => '18:00', 'latitude' => 14.6, 'longitude' => 121.0,
        ];
    }

    private function resetToken(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->consumer->email, 'token' => Hash::make('reset-token'), 'created_at' => now(),
        ]);
    }

    #[DataProvider('invalidPasswords')]
    public function test_all_password_setting_endpoints_reject_weak_passwords_without_side_effects(string $password, string $message): void
    {
        $this->resetToken();
        $this->postJson('/api/register/consumer', $this->consumerPayload($password))
            ->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', $message);
        $this->post('/api/register/vendor', $this->vendorPayload($password), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', $message);
        $this->postJson('/api/forgot-password/reset', [
            'phone_number' => $this->consumer->phone_number, 'reset_token' => 'reset-token',
            'password' => $password, 'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', $message);

        foreach ([
            [$this->consumer, '/api/profile/password-request-otp'],
            [$this->vendor, '/api/vendor/profile/password-request-otp'],
        ] as [$user, $endpoint]) {
            Sanctum::actingAs($user);
            $this->postJson($endpoint, [
                'current_password' => 'secret123', 'new_password' => $password,
                'new_password_confirmation' => $password,
            ])->assertUnprocessable()->assertJsonValidationErrors('new_password')
                ->assertJsonPath('errors.new_password.0', $message);
            $this->assertFalse(Cache::has('pending_password_' . $user->user_id));
            $this->assertTrue(Hash::check('secret123', $user->refresh()->password_hash));
        }
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('stores', 1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertSame([], $this->sent);
    }

    public function test_policy_accepts_eight_characters_longer_passwords_symbols_and_unicode_without_requiring_digits(): void
    {
        foreach (['Abcdefg!', 'LongPassword!', " Abcdefg! ", "\u{00C9}abcdef!", "Abcdefg\u{1F600}", "\u{10400}abcdef!"] as $password) {
            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $password],
                ['password' => ['required', 'string', new StrongPassword(), 'confirmed']]
            );
            $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));
        }
        foreach (['', ['Abcdefg!']] as $password) {
            $this->assertTrue(Validator::make(
                ['password' => $password],
                ['password' => ['required', 'string', new StrongPassword()]]
            )->fails());
        }
    }

    public function test_registration_accepts_strong_passwords_for_consumers_and_vendors(): void
    {
        $this->postJson('/api/register/consumer', $this->consumerPayload('Abcdefg!'))->assertCreated();
        $consumer = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Abcdefg!', $consumer->password_hash));
        $this->assertSame([[$consumer->phone_number, 'registration']], $this->sent);

        OtpCode::create([
            'phone_number' => '09179999992', 'code' => Hash::make('verified-token'), 'type' => 'registration',
            'expires_at' => now()->addMinutes(10), 'verified_at' => now(),
        ]);
        $this->post('/api/register/vendor', $this->vendorPayload('LongPassword!'), ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertTrue(Hash::check('LongPassword!', User::where('email', 'newvendor@example.com')->firstOrFail()->password_hash));
        $this->assertDatabaseCount('stores', 2);
    }

    public function test_confirmation_mismatches_are_rejected_before_passwords_are_saved_or_queued(): void
    {
        $payload = $this->consumerPayload('Abcdefg!');
        $payload['password_confirmation'] = 'Different!';
        $this->postJson('/api/register/consumer', $payload)->assertUnprocessable()->assertJsonValidationErrors('password');
        $vendor = $this->vendorPayload('Abcdefg!');
        $vendor['password_confirmation'] = 'Different!';
        $this->post('/api/register/vendor', $vendor, ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->resetToken();
        $this->postJson('/api/forgot-password/reset', [
            'phone_number' => $this->consumer->phone_number, 'reset_token' => 'reset-token',
            'password' => 'Abcdefg!', 'password_confirmation' => 'Different!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        foreach ([
            [$this->consumer, '/api/profile/password-request-otp'],
            [$this->vendor, '/api/vendor/profile/password-request-otp'],
        ] as [$user, $endpoint]) {
            Sanctum::actingAs($user);
            $this->postJson($endpoint, [
                'current_password' => 'secret123', 'new_password' => 'Abcdefg!',
                'new_password_confirmation' => 'Different!',
            ])->assertUnprocessable()->assertJsonValidationErrors('new_password');
            $this->assertFalse(Cache::has('pending_password_' . $user->user_id));
            $this->assertTrue(Hash::check('secret123', $user->refresh()->password_hash));
        }
        $this->assertSame([], $this->sent);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_password_reset_saves_a_strong_password_and_consumes_the_token(): void
    {
        $this->resetToken();
        $this->postJson('/api/forgot-password/reset', [
            'phone_number' => $this->consumer->phone_number, 'reset_token' => 'reset-token',
            'password' => 'Abcdefg!', 'password_confirmation' => 'Abcdefg!',
        ])->assertOk();
        $this->assertTrue(Hash::check('Abcdefg!', $this->consumer->refresh()->password_hash));
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_profile_changes_preserve_whitespace_and_only_apply_after_otp_verification(): void
    {
        $password = ' Abcdefg! ';
        foreach ([
            [$this->consumer, '/api/profile'],
            [$this->vendor, '/api/vendor/profile'],
        ] as [$user, $prefix]) {
            Sanctum::actingAs($user);
            $this->postJson($prefix . '/password-request-otp', [
                'current_password' => 'secret123', 'new_password' => $password,
                'new_password_confirmation' => $password,
            ])->assertOk();
            $pending = Cache::get('pending_password_' . $user->user_id);
            $this->assertTrue(Hash::check($password, $pending));
            $this->assertFalse(Hash::check(trim($password), $pending));
            $this->assertTrue(Hash::check('secret123', $user->refresh()->password_hash));
            $this->postJson($prefix . '/password-verify-otp', ['code' => '012345'])->assertOk();
            $this->assertTrue(Hash::check($password, $user->refresh()->password_hash));
            $this->assertFalse(Cache::has('pending_password_' . $user->user_id));
        }
        $this->assertCount(2, $this->sent);
    }

    public function test_existing_weak_passwords_still_work_at_login(): void
    {
        $this->postJson('/api/login', ['email' => $this->consumer->email, 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('role', 'Consumer')->assertJsonStructure(['token']);
    }
}
