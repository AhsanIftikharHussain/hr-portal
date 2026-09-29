<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_manage_user_accounts_and_reset_passwords(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $account = User::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $account));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('resetPassword', $account));
    }

    public function test_hr_admin_and_employee_cannot_manage_user_accounts(): void
    {
        $account = User::factory()->create();

        foreach ([Role::HR_ADMIN, Role::EMPLOYEE] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
            $this->assertFalse(Gate::forUser($user)->allows('create', User::class));
            $this->assertFalse(Gate::forUser($user)->allows('update', $account));
            $this->assertFalse(Gate::forUser($user)->allows('resetPassword', $account));
        }
    }
}
