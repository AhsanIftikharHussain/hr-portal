<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperAdminGateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_global_override_grants_restricted_ability(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        Gate::define('restricted-test-ability', fn (): bool => false);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('restricted-test-ability'));
    }

    public function test_hr_admin_has_hr_administration_ability(): void
    {
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->assertTrue(Gate::forUser($hrAdmin)->allows('hr-administration'));
    }

    public function test_employee_does_not_have_hr_administration_ability(): void
    {
        $employee = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->assertFalse(Gate::forUser($employee)->allows('hr-administration'));
    }
}
