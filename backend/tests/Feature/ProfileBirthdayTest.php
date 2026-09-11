<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Covers adding and changing a birthday from the consumer profile, where it stays optional for older accounts. */
class ProfileBirthdayTest extends TestCase
{
    use RefreshesTestDatabase;

    private function signInConsumer(): User
    {
        $user = User::create([
            'role' => 'Consumer',
            'full_name' => 'Juan Cruz',
            'email' => 'juan@example.com',
            'phone_number' => '09171234567',
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_a_consumer_can_add_their_birthday_on_the_profile(): void
    {
        $user = $this->signInConsumer();

        $this->postJson('/api/profile/personal-info', ['full_name' => 'Juan Cruz', 'birthday' => '1995-06-15'])->assertOk();

        $this->assertSame('1995-06-15', $user->refresh()->birthday->format('Y-m-d'));
    }

    public function test_the_profile_refuses_a_birthday_in_the_future(): void
    {
        $user = $this->signInConsumer();

        $this->postJson('/api/profile/personal-info', ['full_name' => 'Juan Cruz', 'birthday' => now()->addDay()->format('Y-m-d')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birthday');

        $this->assertNull($user->refresh()->birthday);
    }

    public function test_saving_without_a_birthday_keeps_the_one_already_saved(): void
    {
        $user = $this->signInConsumer();
        $user->update(['birthday' => '1995-06-15']);

        $this->postJson('/api/profile/personal-info', ['full_name' => 'Juana Cruz', 'birthday' => null])->assertOk();

        $user->refresh();
        $this->assertSame('Juana Cruz', $user->full_name);
        $this->assertSame('1995-06-15', $user->birthday->format('Y-m-d'));
    }

    public function test_the_profile_still_saves_without_a_birthday(): void
    {
        $user = $this->signInConsumer();

        $this->postJson('/api/profile/personal-info', ['full_name' => 'Juana Cruz'])->assertOk();

        $user->refresh();
        $this->assertSame('Juana Cruz', $user->full_name);
        $this->assertNull($user->birthday);
    }
}
