<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PolicyHolidayAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_policy_and_holiday_administration(): void
    {
        $this->get(route('admin.policies.index'))->assertRedirectToRoute('login');
        $this->get(route('admin.holidays.index'))->assertRedirectToRoute('login');
        $this->get(route('admin.holidays.calendar'))->assertRedirectToRoute('login');
    }

    public function test_employee_role_is_forbidden_from_policy_and_holiday_administration(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->get(route('admin.policies.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.policy-categories.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.holidays.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.holidays.calendar'))->assertForbidden();
    }

    public function test_hr_admin_and_super_admin_can_access_both_modules(): void
    {
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($hrAdmin)->get(route('admin.policies.index'))->assertOk();
        $this->actingAs($hrAdmin)->get(route('admin.holidays.calendar'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.policies.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.holidays.calendar'))->assertOk();
    }
}
