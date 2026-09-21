<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_user(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Unapproved User',
            'email' => 'unapproved@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'unapproved@example.test']);
    }
}
