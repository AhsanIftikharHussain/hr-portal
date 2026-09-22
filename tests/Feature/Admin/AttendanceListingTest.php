<?php

namespace Tests\Feature\Admin;

use App\AttendanceStatus;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceListingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daily_view_shows_selected_date_record_and_clear_missing_state(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $recordedEmployee = Employee::factory()->create(['full_name' => 'Recorded Attendance Person']);
        $missingEmployee = Employee::factory()->create(['full_name' => 'Missing Attendance Person']);
        AttendanceRecord::factory()->for($recordedEmployee)->create(['attendance_date' => '2026-09-22']);

        $this->actingAs($user)->get(route('admin.attendance.index', ['date' => '2026-09-22']))
            ->assertOk()
            ->assertSee('Recorded Attendance Person')
            ->assertSee('Missing Attendance Person')
            ->assertSee('Not recorded')
            ->assertSee('Missing records are not treated as absent.');
    }

    public function test_daily_view_filters_department_status_and_employee_search(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $department = Department::factory()->create();
        $matched = Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Matched Attendance Person', 'employee_code' => 'ATT-SEARCH']);
        $excluded = Employee::factory()->create(['full_name' => 'Excluded Attendance Person']);
        AttendanceRecord::factory()->for($matched)->create(['attendance_date' => '2026-09-22', 'status' => AttendanceStatus::Late]);
        AttendanceRecord::factory()->for($excluded)->absent()->create(['attendance_date' => '2026-09-22']);

        $this->actingAs($user)->get(route('admin.attendance.index', [
            'date' => '2026-09-22',
            'department_id' => $department->id,
            'status' => AttendanceStatus::Late->value,
            'search' => 'ATT-SEARCH',
        ]))->assertOk()->assertViewHas('employees', fn ($employees): bool => $employees->pluck('id')->all() === [$matched->id]);
    }

    public function test_status_filter_does_not_convert_missing_records_to_absent(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $missingEmployee = Employee::factory()->create();
        $absentRecord = AttendanceRecord::factory()->absent()->create(['attendance_date' => '2026-09-22']);

        $this->actingAs($user)->get(route('admin.attendance.index', [
            'date' => '2026-09-22',
            'status' => AttendanceStatus::Absent->value,
        ]))->assertOk()->assertViewHas('employees', function ($employees) use ($missingEmployee, $absentRecord): bool {
            return $employees->pluck('id')->all() === [$absentRecord->employee_id]
                && ! $employees->pluck('id')->contains($missingEmployee->id);
        });
    }

    public function test_approved_leave_is_context_only_and_does_not_create_attendance(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['full_name' => 'Approved Leave Context Person']);
        LeaveRequest::factory()->approved($user)->for($employee)->create([
            'status' => LeaveRequestStatus::Approved,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-23',
            'duration_days' => 3,
        ]);

        $this->actingAs($user)->get(route('admin.attendance.index', ['date' => '2026-09-22']))
            ->assertOk()
            ->assertSee('Approved leave covers this date');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_daily_view_paginates_current_employees_and_excludes_archived_employees(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->count(21)->create();
        Employee::factory()->archived()->create(['full_name' => 'Archived Daily Person']);

        $this->actingAs($user)->get(route('admin.attendance.index', ['date' => '2026-09-22']))
            ->assertOk()
            ->assertViewHas('employees', fn ($employees): bool => $employees->total() === 21 && $employees->perPage() === 20)
            ->assertDontSee('Archived Daily Person');
    }
}
