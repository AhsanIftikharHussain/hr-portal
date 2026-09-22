<?php

namespace Tests\Feature\Admin;

use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pending_request_can_be_approved_with_reviewer_metadata(): void
    {
        $this->travelTo('2026-09-22 10:30:00');
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $leaveRequest), [
            'hr_comment' => 'Coverage has been arranged.',
        ])->assertRedirect();

        $leaveRequest->refresh();
        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->status);
        $this->assertSame($reviewer->id, $leaveRequest->reviewed_by);
        $this->assertSame('Coverage has been arranged.', $leaveRequest->hr_comment);
        $this->assertSame('2026-09-22 10:30:00', $leaveRequest->reviewed_at->format('Y-m-d H:i:s'));
    }

    public function test_pending_request_can_be_rejected_when_comment_is_provided(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.reject', $leaveRequest), [
            'hr_comment' => 'Insufficient staffing for these dates.',
        ])->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Rejected, $leaveRequest->fresh()->status);
    }

    public function test_rejection_requires_hr_comment(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.reject', $leaveRequest), [])
            ->assertSessionHasErrors(['hr_comment']);

        $this->assertSame(LeaveRequestStatus::Pending, $leaveRequest->fresh()->status);
    }

    public function test_approved_request_cannot_be_rejected_by_stale_review(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->approved($reviewer)->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.reject', $leaveRequest), [
            'hr_comment' => 'Stale decision.',
        ])->assertSessionHasErrors(['status']);

        $leaveRequest->refresh();
        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->status);
        $this->assertSame('Approved for test coverage.', $leaveRequest->hr_comment);
    }

    public function test_pending_request_can_be_cancelled_with_reason(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.cancel', $leaveRequest), [
            'hr_comment' => 'Employee withdrew the request.',
        ])->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $leaveRequest->fresh()->status);
    }

    public function test_approved_request_can_be_cancelled_with_reason(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->approved($reviewer)->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.cancel', $leaveRequest), [
            'hr_comment' => 'Approved leave was withdrawn.',
        ])->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $leaveRequest->fresh()->status);
    }

    public function test_rejected_and_cancelled_requests_are_terminal(): void
    {
        $reviewer = User::factory()->withRole(Role::HR_ADMIN)->create();
        $rejected = LeaveRequest::factory()->rejected($reviewer)->create();
        $cancelled = LeaveRequest::factory()->cancelled($reviewer)->create();

        $this->actingAs($reviewer)->post(route('admin.leave-requests.approve', $rejected))->assertSessionHasErrors(['status']);
        $this->actingAs($reviewer)->post(route('admin.leave-requests.cancel', $cancelled), ['hr_comment' => 'Repeat cancellation.'])->assertSessionHasErrors(['status']);

        $this->assertSame(LeaveRequestStatus::Rejected, $rejected->fresh()->status);
        $this->assertSame(LeaveRequestStatus::Cancelled, $cancelled->fresh()->status);
    }
}
