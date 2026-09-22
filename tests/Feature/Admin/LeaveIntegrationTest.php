<?php

namespace Tests\Feature\Admin;

use App\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_reports_pending_requests_and_employees_on_leave_today(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        LeaveRequest::factory()->count(2)->create();
        $onLeaveEmployee = Employee::factory()->create();
        LeaveRequest::factory()->approved($user)->for($onLeaveEmployee)->count(2)->create(['start_date' => '2026-09-21', 'end_date' => '2026-09-23', 'duration_days' => 3]);
        LeaveRequest::factory()->approved($user)->create(['start_date' => '2026-09-23', 'end_date' => '2026-09-24', 'duration_days' => 2]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('leaveMetrics', ['pending' => 2, 'on_leave_today' => 1])
            ->assertSee('Pending Leave Requests')
            ->assertSee('On Leave Today');
    }

    public function test_dashboard_leave_metrics_exclude_archived_employees(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->archived()->create();
        LeaveRequest::factory()->for($employee)->create();
        LeaveRequest::factory()->approved($user)->for($employee)->create(['start_date' => '2026-09-22', 'end_date' => '2026-09-22', 'duration_days' => 1]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('leaveMetrics', ['pending' => 0, 'on_leave_today' => 0]);
    }

    public function test_employee_profile_shows_leave_history_and_approved_usage_only(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['name' => 'Fictional Annual Leave']);
        LeaveRequest::factory()->approved($user)->for($employee)->for($leaveType)->create(['duration_days' => 4]);
        LeaveRequest::factory()->for($employee)->for($leaveType)->create(['duration_days' => 3, 'status' => LeaveRequestStatus::Pending]);

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertViewHas('approvedLeaveDays', 4)
            ->assertSee('Fictional Annual Leave')
            ->assertSee('4 approved calendar days')
            ->assertSee('No entitlement or remaining-balance policy has been configured.');
    }

    public function test_inactive_leave_type_history_remains_visible(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveType = LeaveType::factory()->inactive()->create(['name' => 'Legacy Fictional Leave']);
        $leaveRequest = LeaveRequest::factory()->for($leaveType)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.show', $leaveRequest))
            ->assertOk()
            ->assertSee('Legacy Fictional Leave');
    }
}
