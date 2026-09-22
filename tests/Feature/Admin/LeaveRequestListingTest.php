<?php

namespace Tests\Feature\Admin;

use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveRequestListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_list_searches_employee_name_and_code(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $matched = Employee::factory()->create(['full_name' => 'Unique Leave Person', 'employee_code' => 'EMP-SEARCH-1']);
        $other = Employee::factory()->create(['full_name' => 'Different Person', 'employee_code' => 'EMP-OTHER-1']);
        LeaveRequest::factory()->for($matched)->create();
        LeaveRequest::factory()->for($other)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.index', ['search' => 'SEARCH-1']))
            ->assertOk()
            ->assertViewHas('leaveRequests', fn ($requests): bool => $requests->pluck('employee_id')->all() === [$matched->id]);
    }

    public function test_list_filters_by_department_type_status_and_overlapping_dates(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $department = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Matching Filter Person']);
        $leaveType = LeaveType::factory()->create();
        LeaveRequest::factory()->approved($user)->for($employee)->for($leaveType)->create([
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-25',
            'duration_days' => 6,
        ]);
        LeaveRequest::factory()->for(Employee::factory()->create(['full_name' => 'Excluded Filter Person']))->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]);

        $this->actingAs($user)->get(route('admin.leave-requests.index', [
            'department_id' => $department->id,
            'leave_type_id' => $leaveType->id,
            'status' => LeaveRequestStatus::Approved->value,
            'date_from' => '2026-09-22',
            'date_to' => '2026-09-22',
        ]))->assertOk()->assertViewHas('leaveRequests', fn ($requests): bool => $requests->pluck('employee_id')->all() === [$employee->id]);
    }

    public function test_list_paginates_leave_requests(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        LeaveRequest::factory()->count(16)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.index'))
            ->assertOk()
            ->assertViewHas('leaveRequests', fn ($requests): bool => $requests->total() === 16 && $requests->perPage() === 15);
    }
}
