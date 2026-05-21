<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_logs_them_in(): void
    {
        $this->withoutExceptionHandling();

        $this->postJson('/api/auth/register', [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'tester@example.com');

        $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'someone@example.com', 'password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => 'someone@example.com',
            'password' => 'nope',
        ])->assertStatus(422);
    }

    public function test_subscription_lifecycle(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/me/subscriptions', [
            'bank_id' => null,
            'currency_id' => null,
            'threshold_pct' => 4.5,
        ])->assertCreated();

        $this->getJson('/api/me/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
