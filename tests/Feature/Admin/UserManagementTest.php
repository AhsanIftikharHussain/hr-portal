<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_user_management(): void
    {
        $this->get(route('admin.users.index'))->assertRedirectToRoute('login');
    }

    public function test_hr_admin_and_employee_are_forbidden_from_user_management(): void
    {
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($hrAdmin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_can_view_user_management_navigation_and_list(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $employee = User::factory()->withRole(Role::EMPLOYEE)->create([
            'name' => 'Portal Employee',
            'email' => 'portal.employee@example.test',
        ]);

        $this->actingAs($superAdmin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee($employee->name)
            ->assertSee($employee->email)
            ->assertDontSee($employee->password);
    }

    public function test_user_management_navigation_is_visible_only_to_super_admin(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($superAdmin)->get(route('admin.dashboard'))->assertSee('User Management');
        $this->actingAs($hrAdmin)->get(route('admin.dashboard'))->assertDontSee('User Management');
        $this->actingAs($employee)->get(route('profile.edit'))->assertDontSee('User Management');
    }

    public function test_user_list_filters_by_search_role_and_status(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        User::factory()->withRole(Role::HR_ADMIN)->create(['name' => 'Matching HR', 'is_active' => true]);
        User::factory()->withRole(Role::EMPLOYEE)->create(['name' => 'Matching Employee', 'is_active' => true]);
        User::factory()->withRole(Role::HR_ADMIN)->inactive()->create(['name' => 'Inactive Matching HR']);

        $this->actingAs($superAdmin)->get(route('admin.users.index', [
            'search' => 'Matching',
            'role' => Role::HR_ADMIN,
            'account_status' => 'active',
        ]))->assertSee('Matching HR')
            ->assertDontSee('Matching Employee')
            ->assertDontSee('Inactive Matching HR');
    }

    public function test_super_admin_creates_active_hr_admin_with_hashed_password_and_role(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        Role::factory()->create(['name' => 'HR Admin', 'slug' => Role::HR_ADMIN]);
        Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'New HR Admin',
            'email' => 'NEW.HR@example.test',
            'roles' => [Role::HR_ADMIN],
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'is_active' => '1',
        ])->assertRedirectToRoute('admin.users.index');

        $createdUser = User::query()->where('email', 'new.hr@example.test')->sole();
        $this->assertTrue($createdUser->is_active);
        $this->assertTrue($createdUser->hasRole(Role::HR_ADMIN));
        $this->assertTrue(Hash::check('SecurePassword123!', $createdUser->password));
        $this->assertNotSame('SecurePassword123!', $createdUser->password);

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $createdUser->email,
            'password' => 'SecurePassword123!',
        ])->assertRedirectToRoute('admin.dashboard');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_created_employee_can_authenticate_and_is_redirected_to_profile(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        Role::factory()->create(['name' => 'HR Admin', 'slug' => Role::HR_ADMIN]);
        Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'New Employee',
            'email' => 'new.employee@example.test',
            'roles' => [Role::EMPLOYEE],
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'is_active' => '1',
        ]);
        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'new.employee@example.test',
            'password' => 'SecurePassword123!',
        ])->assertRedirectToRoute('profile.edit');

        $this->assertAuthenticatedAs(User::query()->where('email', 'new.employee@example.test')->sole());
    }

    public function test_create_rejects_duplicate_email_weak_password_mismatch_and_super_admin_role(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        Role::factory()->create(['name' => 'HR Admin', 'slug' => Role::HR_ADMIN]);
        $existingUser = User::factory()->create();

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Invalid User',
            'email' => $existingUser->email,
            'roles' => [Role::SUPER_ADMIN],
            'password' => 'weak',
            'password_confirmation' => 'different',
            'is_active' => '1',
        ])->assertSessionHasErrors(['email', 'roles.0', 'password']);

        $this->assertSame(2, User::query()->count());
    }

    public function test_super_admin_updates_profile_fields_status_and_multiple_role_assignments(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $hrRole = Role::factory()->create(['name' => 'HR Admin', 'slug' => Role::HR_ADMIN]);
        $employeeRole = Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);
        $account = User::factory()->create();
        $account->roles()->attach($employeeRole);

        $this->actingAs($superAdmin)->put(route('admin.users.update', $account), [
            'name' => 'Updated Account',
            'email' => 'updated.account@example.test',
            'roles' => [Role::HR_ADMIN, Role::EMPLOYEE],
            'is_active' => '0',
        ])->assertRedirectToRoute('admin.users.index');

        $account->refresh();
        $this->assertSame('Updated Account', $account->name);
        $this->assertSame('updated.account@example.test', $account->email);
        $this->assertFalse($account->is_active);
        $this->assertEqualsCanonicalizing([$hrRole->id, $employeeRole->id], $account->roles()->pluck('roles.id')->all());
    }

    public function test_super_admin_account_cannot_be_deactivated_or_have_roles_replaced(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);

        $this->actingAs($superAdmin)->put(route('admin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'roles' => [Role::EMPLOYEE],
            'is_active' => '0',
        ])->assertSessionHasErrors(['roles', 'is_active']);

        $superAdmin->refresh();
        $this->assertTrue($superAdmin->is_active);
        $this->assertTrue($superAdmin->hasRole(Role::SUPER_ADMIN));
    }

    public function test_user_can_be_deactivated_and_reactivated_without_deletion(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $employeeRole = Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);
        $account = User::factory()->create();
        $account->roles()->attach($employeeRole);

        $payload = [
            'name' => $account->name,
            'email' => $account->email,
            'roles' => [Role::EMPLOYEE],
            'is_active' => '0',
        ];
        $this->actingAs($superAdmin)->put(route('admin.users.update', $account), $payload)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $account->id, 'is_active' => false]);

        $payload['is_active'] = '1';
        $this->actingAs($superAdmin)->put(route('admin.users.update', $account), $payload)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $account->id, 'is_active' => true]);
    }

    public function test_super_admin_resets_user_password_securely(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $account = User::factory()->create();

        $this->actingAs($superAdmin)->put(route('admin.users.password.update', $account), [
            'password' => 'ReplacementPassword123!',
            'password_confirmation' => 'ReplacementPassword123!',
        ])->assertRedirectToRoute('admin.users.edit', $account);

        $account->refresh();
        $this->assertTrue(Hash::check('ReplacementPassword123!', $account->password));
        $this->assertNotSame('ReplacementPassword123!', $account->password);

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => $account->email,
            'password' => 'ReplacementPassword123!',
        ])->assertRedirectToRoute('profile.edit');
    }

    public function test_password_reset_rejects_weak_or_mismatched_password(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $account = User::factory()->create();
        $originalPassword = $account->password;

        $this->actingAs($superAdmin)->put(route('admin.users.password.update', $account), [
            'password' => 'weak',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertSame($originalPassword, $account->fresh()->password);
    }

    public function test_user_list_escapes_account_names(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        User::factory()->create(['name' => '<script>alert("users")</script>']);

        $this->actingAs($superAdmin)->get(route('admin.users.index'))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("users")</script>', false);
    }
}
