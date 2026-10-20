<?php

namespace Tests\Feature\Manager;

use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ManagerLeaveRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_only_sees_and_opens_leave_requests_from_own_department(): void
    {
        [$manager, $managerEmployee, $department] = $this->managerContext();
        $teamEmployee = Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Visible Leave Employee']);
        $outsideEmployee = Employee::factory()->create(['full_name' => 'Hidden Leave Employee']);
        $archivedEmployee = Employee::factory()->archived()->create(['department_id' => $department->id, 'full_name' => 'Archived Leave Employee']);
        $teamLeave = LeaveRequest::factory()->create(['employee_id' => $teamEmployee->id]);
        $outsideLeave = LeaveRequest::factory()->create(['employee_id' => $outsideEmployee->id]);
        $archivedLeave = LeaveRequest::factory()->create(['employee_id' => $archivedEmployee->id]);

        $this->actingAs($manager)->get(route('manager.leave-requests.index'))
            ->assertOk()
            ->assertSee('Visible Leave Employee')
            ->assertDontSee('Hidden Leave Employee')
            ->assertDontSee('Archived Leave Employee');
        $this->actingAs($manager)->get(route('manager.leave-requests.show', $teamLeave))->assertOk();
        $this->actingAs($manager)->get(route('manager.leave-requests.show', $outsideLeave))->assertForbidden();
        $this->actingAs($manager)->get(route('manager.leave-requests.show', $archivedLeave))->assertForbidden();
        $this->assertNotSame($managerEmployee->id, $teamEmployee->id);
    }

    public function test_manager_can_approve_and_reject_pending_department_leave_with_audit_data(): void
    {
        [$manager, , $department] = $this->managerContext();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $approvedLeave = LeaveRequest::factory()->create(['employee_id' => $employee->id]);
        $rejectedLeave = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($manager)->post(route('manager.leave-requests.approve', $approvedLeave), [
            'hr_comment' => 'Department workload permits this leave.',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $approvedLeave->id,
            'status' => LeaveRequestStatus::Approved->value,
            'reviewed_by' => $manager->id,
            'hr_comment' => 'Department workload permits this leave.',
        ]);
        $this->assertNotNull($approvedLeave->fresh()->reviewed_at);

        $this->actingAs($manager)->post(route('manager.leave-requests.reject', $rejectedLeave), [
            'hr_comment' => 'Critical coverage is unavailable.',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('leave_requests', [
            'id' => $rejectedLeave->id,
            'status' => LeaveRequestStatus::Rejected->value,
            'reviewed_by' => $manager->id,
        ]);
    }

    public function test_manager_cannot_review_own_or_cross_department_leave(): void
    {
        [$manager, $managerEmployee, $department] = $this->managerContext();
        $ownLeave = LeaveRequest::factory()->create(['employee_id' => $managerEmployee->id]);
        $outsideLeave = LeaveRequest::factory()->create();

        $this->actingAs($manager)->post(route('manager.leave-requests.approve', $ownLeave))->assertForbidden();
        $this->actingAs($manager)->post(route('manager.leave-requests.reject', $ownLeave), ['hr_comment' => 'Self review'])->assertForbidden();
        $this->actingAs($manager)->post(route('manager.leave-requests.reject', $outsideLeave), ['hr_comment' => 'No'])->assertForbidden();

        $this->assertSame(LeaveRequestStatus::Pending, $ownLeave->fresh()->status);
        $this->assertSame(LeaveRequestStatus::Pending, $outsideLeave->fresh()->status);
        $this->assertSame($department->id, $managerEmployee->department_id);
    }

    public function test_review_transition_remains_atomic_and_cannot_overwrite_a_completed_review(): void
    {
        [$manager, , $department] = $this->managerContext();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $leave = LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id]);
        $originalReviewer = $leave->reviewed_by;

        $this->actingAs($manager)->post(route('manager.leave-requests.reject', $leave), ['hr_comment' => 'Late change'])
            ->assertSessionHasErrors('status');

        $leave->refresh();
        $this->assertSame(LeaveRequestStatus::Approved, $leave->status);
        $this->assertSame($originalReviewer, $leave->reviewed_by);
    }

    /** @return array{User, Employee, Department} */
    private function managerContext(): array
    {
        $department = Department::factory()->create();
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        $managerEmployee = Employee::factory()->create([
            'user_id' => $manager->id,
            'department_id' => $department->id,
        ]);

        return [$manager, $managerEmployee, $department];
    }
}
