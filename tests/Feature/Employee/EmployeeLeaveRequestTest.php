<?php

namespace Tests\Feature\Employee;

use App\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeLeaveRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_only_sees_and_opens_own_leave_requests(): void
    {
        [$user, $employee] = $this->employeeContext();
        $ownLeave = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'reason' => 'Own visible reason']);
        $otherLeave = LeaveRequest::factory()->create(['reason' => 'Other confidential reason']);

        $this->actingAs($user)->get(route('employee.leave-requests.index'))
            ->assertOk()
            ->assertSee($ownLeave->leaveType->name)
            ->assertDontSee('Other confidential reason');
        $this->actingAs($user)->get(route('employee.leave-requests.show', $ownLeave))->assertOk()->assertSee('Own visible reason');
        $this->actingAs($user)->get(route('employee.leave-requests.show', $otherLeave))->assertNotFound();
    }

    public function test_employee_submission_derives_owner_and_starts_pending(): void
    {
        [$user, $employee] = $this->employeeContext();
        $otherEmployee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();

        $this->actingAs($user)->post(route('employee.leave-requests.store'), [
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'reason' => 'Personal appointment.',
            'status' => LeaveRequestStatus::Approved->value,
        ])->assertRedirectToRoute('employee.leave-requests.index')
            ->assertSessionHas('success', 'Leave request submitted successfully.');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'duration_days' => 3,
            'status' => LeaveRequestStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $otherEmployee->id]);
    }

    public function test_employee_leave_submission_applies_shared_validation_rules(): void
    {
        [$user] = $this->employeeContext();

        $this->actingAs($user)->from(route('employee.leave-requests.create'))
            ->post(route('employee.leave-requests.store'), [
                'start_date' => '2026-11-12',
                'end_date' => '2026-11-10',
            ])
            ->assertRedirect(route('employee.leave-requests.create'))
            ->assertSessionHasErrors(['leave_type_id', 'end_date', 'reason']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_employee_cannot_use_admin_leave_review_routes(): void
    {
        [$user, $employee] = $this->employeeContext();
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->post(route('admin.leave-requests.approve', $leaveRequest))->assertForbidden();
        $this->actingAs($user)->post(route('admin.leave-requests.reject', $leaveRequest), ['hr_comment' => 'No'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.leave-requests.cancel', $leaveRequest), ['hr_comment' => 'Cancel'])->assertForbidden();

        $this->assertSame(LeaveRequestStatus::Pending, $leaveRequest->fresh()->status);
    }

    public function test_employee_leave_reason_is_escaped_on_detail_page(): void
    {
        [$user, $employee] = $this->employeeContext();
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'reason' => '<script>alert("leave")</script>',
        ]);

        $this->actingAs($user)->get(route('employee.leave-requests.show', $leaveRequest))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("leave")</script>', false);
    }

    /** @return array{User, Employee} */
    private function employeeContext(): array
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        return [$user, $employee];
    }
}
