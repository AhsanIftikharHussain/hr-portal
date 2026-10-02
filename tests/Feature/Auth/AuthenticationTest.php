<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_returns_ok_for_true_guest(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_active_hr_admin_is_redirected_to_admin_after_login(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_super_admin_is_redirected_to_admin_after_login(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_employee_is_redirected_to_profile_after_login(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('profile.edit');

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

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_session_is_invalidated_before_login_page_is_rendered(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->inactive()->create();
        $sessionGuardName = Auth::guard('web')->getName();

        $this->actingAs($user)
            ->withSession(['_token' => 'legacy-csrf-token'])
            ->get(route('login'))
            ->assertRedirectToRoute('login')
            ->assertSessionMissing($sessionGuardName)
            ->assertSessionHas('_token', fn (string $token): bool => $token !== 'legacy-csrf-token');

        $this->assertGuest();
        $this->get(route('login'))->assertOk();
    }

    public function test_stale_session_is_invalidated_and_reaches_login_without_looping(): void
    {
        $sessionGuardName = Auth::guard('web')->getName();

        $this->withSession([
            $sessionGuardName => PHP_INT_MAX,
            '_token' => 'legacy-csrf-token',
        ])->get(route('profile.edit'))
            ->assertRedirectToRoute('login')
            ->assertSessionMissing($sessionGuardName)
            ->assertSessionHas('_token', fn (string $token): bool => $token !== 'legacy-csrf-token');

        $this->assertGuest();
        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_user_visiting_login_is_redirected_once_to_accessible_page(): void
    {
        $employee = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($employee)
            ->get(route('login'))
            ->assertRedirectToRoute('profile.edit');

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_authenticated_hr_admin_visiting_login_is_redirected_once_to_admin(): void
    {
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($hrAdmin)
            ->get(route('login'))
            ->assertRedirectToRoute('admin.dashboard');

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_authenticated_super_admin_visiting_login_is_redirected_once_to_admin(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($superAdmin)
            ->get(route('login'))
            ->assertRedirectToRoute('admin.dashboard');

        $this->get(route('admin.dashboard'))->assertOk();
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
