<?php

namespace Tests\Feature\Profile;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirectToRoute('login');
        $this->put(route('profile.update'), [])->assertRedirectToRoute('login');
        $this->put(route('profile.password.update'), [])->assertRedirectToRoute('login');
    }

    public function test_every_authenticated_role_can_view_profile(): void
    {
        foreach ([Role::SUPER_ADMIN, Role::HR_ADMIN, Role::EMPLOYEE] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->actingAs($user)->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('My Profile')
                ->assertSee($user->email);
        }
    }

    public function test_user_updates_own_name_and_email(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Employee',
            'email' => 'UPDATED.EMPLOYEE@example.test',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Updated Employee', $user->name);
        $this->assertSame('updated.employee@example.test', $user->email);
    }

    public function test_profile_rejects_duplicate_email(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $otherUser->email,
        ])->assertSessionHasErrors('email');

        $this->assertNotSame($otherUser->email, $user->fresh()->email);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $originalPassword = $user->password;

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'incorrect-password',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_password_change_rejects_weak_or_mismatched_password(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_user_changes_password_and_remains_authenticated(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }

    public function test_super_admin_can_update_profile_and_password(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($superAdmin)->put(route('profile.update'), [
            'name' => 'Updated Super Admin',
            'email' => 'updated.super@example.test',
        ])->assertRedirect();
        $this->actingAs($superAdmin)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'SuperSecurePassword123!',
            'password_confirmation' => 'SuperSecurePassword123!',
        ])->assertRedirect();

        $superAdmin->refresh();
        $this->assertSame('Updated Super Admin', $superAdmin->name);
        $this->assertTrue(Hash::check('SuperSecurePassword123!', $superAdmin->password));
    }

    public function test_inactive_authenticated_user_is_logged_out_on_protected_request(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->inactive()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
