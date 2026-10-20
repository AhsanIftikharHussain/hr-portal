<?php

namespace Tests\Feature\Notifications;

use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use InvalidArgumentException;
use Tests\TestCase;

class LeaveDecisionNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_successful_approval_notifies_only_the_linked_active_employee_user(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $recipient = User::factory()->withRole(Role::EMPLOYEE)->create();
        $unrelated = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($recipient, 'Annual Leave');

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $leaveRequest), [
            'hr_comment' => 'Approved.',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'type' => LeaveRequestApproved::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
        ]);
        $this->assertSame(1, $recipient->notifications()->count());
        $this->assertSame(0, $unrelated->notifications()->count());
        $this->assertSame('Leave request approved', $recipient->notifications()->first()->data['title']);
        $this->assertSame(
            route('employee.leave-requests.show', $leaveRequest),
            $recipient->notifications()->first()->data['url'],
        );
    }

    public function test_successful_rejection_notifies_the_employee_without_storing_the_reason_in_payload(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $recipient = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($recipient, 'Medical Leave');

        $this->actingAs($reviewer)->post(route('admin.leave-requests.reject', $leaveRequest), [
            'hr_comment' => 'Private staffing reason.',
        ])->assertRedirect()->assertSessionHas('status');

        $notification = $recipient->notifications()->sole();
        $this->assertSame(LeaveRequestStatus::Rejected, $leaveRequest->fresh()->status);
        $this->assertSame('Private staffing reason.', $leaveRequest->fresh()->hr_comment);
        $this->assertSame(LeaveRequestRejected::class, $notification->type);
        $this->assertSame('Leave request rejected', $notification->data['title']);
        $this->assertStringNotContainsString('Private staffing reason.', $notification->data['message']);
    }

    public function test_approval_without_a_linked_user_succeeds_without_notification(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $leaveRequest))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_inactive_linked_user_is_not_notified(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $inactiveUser = User::factory()->inactive()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($inactiveUser, 'Annual Leave');

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $leaveRequest))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_stale_leave_decisions_do_not_send_notifications(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $recipient = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($recipient, 'Annual Leave');
        $leaveRequest->update(['status' => LeaveRequestStatus::Approved]);

        $this->actingAs($reviewer)->post(route('admin.leave-requests.reject', $leaveRequest), [
            'hr_comment' => 'Stale review.',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_mail_transport_failure_does_not_reverse_or_obscure_successful_leave_approval(): void
    {
        Exceptions::fake();
        config(['mail.default' => 'missing-mailer']);
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $recipient = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($recipient, 'Annual Leave');

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $leaveRequest))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertSame(1, $recipient->notifications()->count());
        Exceptions::assertReported(InvalidArgumentException::class);
    }

    public function test_department_manager_approval_uses_the_same_employee_notification(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);
        $recipient = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = $this->leaveForUser($recipient, 'Annual Leave', $department);

        $this->actingAs($manager)->post(route('manager.leave-requests.approve', $leaveRequest))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(LeaveRequestApproved::class, $recipient->notifications()->sole()->type);
        $this->assertSame($manager->id, $leaveRequest->fresh()->reviewed_by);
    }

    private function leaveForUser(User $user, string $leaveTypeName, ?Department $department = null): LeaveRequest
    {
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department?->id ?? Department::factory(),
        ]);
        $leaveType = LeaveType::factory()->create(['name' => $leaveTypeName]);

        return LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'duration_days' => 3,
        ]);
    }
}
