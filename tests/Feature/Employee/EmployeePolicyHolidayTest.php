<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Policy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeePolicyHolidayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_sees_only_published_policies_and_draft_details_are_not_found(): void
    {
        $user = $this->employeeUser();
        $published = Policy::factory()->published()->create(['title' => 'Published Employee Policy']);
        $draft = Policy::factory()->create(['title' => 'Confidential Draft Policy']);
        $archived = Policy::factory()->archived()->create(['title' => 'Archived Policy']);

        $this->actingAs($user)->get(route('employee.policies.index'))
            ->assertOk()
            ->assertSee('Published Employee Policy')
            ->assertDontSee('Confidential Draft Policy')
            ->assertDontSee('Archived Policy');
        $this->actingAs($user)->get(route('employee.policies.show', $published))->assertOk();
        $this->actingAs($user)->get(route('employee.policies.show', $draft))->assertNotFound();
        $this->actingAs($user)->get(route('employee.policies.show', $archived))->assertNotFound();
    }

    public function test_employee_sees_only_active_holidays_for_selected_year(): void
    {
        $user = $this->employeeUser();
        Holiday::factory()->create(['name' => 'Visible Holiday', 'holiday_date' => '2026-12-25']);
        Holiday::factory()->inactive()->create(['name' => 'Inactive Holiday', 'holiday_date' => '2026-12-26']);
        Holiday::factory()->create(['name' => 'Different Year Holiday', 'holiday_date' => '2027-01-01']);

        $this->actingAs($user)->get(route('employee.holidays.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Visible Holiday')
            ->assertDontSee('Inactive Holiday')
            ->assertDontSee('Different Year Holiday');
    }

    public function test_employee_cannot_access_policy_or_holiday_administration(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->get(route('admin.policies.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.policies.create'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.holidays.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.holidays.create'))->assertForbidden();
    }

    public function test_published_policy_content_is_escaped(): void
    {
        $user = $this->employeeUser();
        $policy = Policy::factory()->published()->create(['content' => '<script>alert("policy")</script>']);

        $this->actingAs($user)->get(route('employee.policies.show', $policy))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("policy")</script>', false);
    }

    private function employeeUser(): User
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
