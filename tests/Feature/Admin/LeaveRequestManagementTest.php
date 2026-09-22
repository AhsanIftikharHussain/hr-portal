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

class LeaveRequestManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_required_request_fields_are_validated(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [])
            ->assertSessionHasErrors(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_unknown_employee_and_leave_type_are_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => 999999,
            'leave_type_id' => 999999,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-02',
            'reason' => 'Invalid relationships.',
        ])->assertSessionHasErrors(['employee_id', 'leave_type_id']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_hr_admin_creates_pending_request_with_inclusive_calendar_duration(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-05',
            'reason' => 'Fictional family commitment.',
            'duration_days' => 999,
            'status' => LeaveRequestStatus::Approved->value,
            'reviewed_by' => $user->id,
        ]);

        $leaveRequest = $employee->leaveRequests()->sole();
        $response->assertRedirectToRoute('admin.leave-requests.show', $leaveRequest);
        $this->assertSame(4, $leaveRequest->duration_days);
        $this->assertSame(LeaveRequestStatus::Pending, $leaveRequest->status);
        $this->assertNull($leaveRequest->reviewed_by);
    }

    public function test_same_day_request_has_one_calendar_day(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-02',
            'reason' => 'Fictional appointment.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $employee->leaveRequests()->sole()->duration_days);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-02',
            'reason' => 'Invalid range.',
        ])->assertSessionHasErrors(['end_date']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_inactive_leave_type_cannot_be_used_for_new_request(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->inactive()->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-02',
            'reason' => 'Fictional appointment.',
        ])->assertSessionHasErrors(['leave_type_id']);
    }

    public function test_archived_employee_cannot_receive_new_request(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->archived()->create();
        $leaveType = LeaveType::factory()->create();

        $this->actingAs($user)->post(route('admin.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-02',
            'reason' => 'Fictional appointment.',
        ])->assertSessionHasErrors(['employee_id']);
    }

    public function test_request_reason_is_escaped_on_detail_page(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create(['reason' => '<script>alert("leave")</script>']);

        $this->actingAs($user)->get(route('admin.leave-requests.show', $leaveRequest))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("leave")</script>', false);
    }

    public function test_detail_page_does_not_expose_unrelated_sensitive_employee_data(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create([
            'national_id' => 'PRIVATE-NIC-LEAVE',
            'contact_number' => '+92 300 1234567',
            'address' => 'Private Leave Address',
            'emergency_contact_name' => 'Private Leave Contact',
        ]);
        $leaveRequest = LeaveRequest::factory()->for($employee)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.show', $leaveRequest))
            ->assertOk()
            ->assertDontSee('PRIVATE-NIC-LEAVE')
            ->assertDontSee('+92 300 1234567')
            ->assertDontSee('Private Leave Address')
            ->assertDontSee('Private Leave Contact');
    }
}
