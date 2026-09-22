<?php

namespace Tests\Feature\Admin;

use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_monthly_summary_counts_only_explicit_status_records(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $datesAndStatuses = [
            '2026-09-01' => AttendanceStatus::Present,
            '2026-09-02' => AttendanceStatus::Absent,
            '2026-09-03' => AttendanceStatus::Leave,
            '2026-09-04' => AttendanceStatus::HalfDay,
            '2026-09-05' => AttendanceStatus::Late,
            '2026-09-06' => AttendanceStatus::Present,
            '2026-09-30' => AttendanceStatus::Present,
        ];
        foreach ($datesAndStatuses as $date => $status) {
            AttendanceRecord::factory()->for($employee)->create([
                'attendance_date' => $date,
                'status' => $status,
                'check_in_at' => in_array($status, [AttendanceStatus::Absent, AttendanceStatus::Leave], true) ? null : $date.' 09:00:00',
                'check_out_at' => in_array($status, [AttendanceStatus::Absent, AttendanceStatus::Leave], true) ? null : $date.' 17:00:00',
            ]);
        }
        Employee::factory()->create(['full_name' => 'Employee Without Records']);

        $this->actingAs($user)->get(route('admin.attendance.monthly', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertViewHas('summaries', function ($summaries) use ($employee): bool {
                $summary = $summaries->firstWhere('employee_id', $employee->id);

                return (int) $summary->recorded_days_count === 7
                    && (int) $summary->present_count === 3
                    && (int) $summary->absent_count === 1
                    && (int) $summary->leave_count === 1
                    && (int) $summary->half_day_count === 1
                    && (int) $summary->late_count === 1;
            })
            ->assertDontSee('Employee Without Records');
    }

    public function test_employee_profile_displays_filtered_attendance_history_and_duration(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        AttendanceRecord::factory()->for($employee)->create([
            'attendance_date' => '2026-09-22',
            'check_in_at' => '2026-09-22 09:15:00',
            'check_out_at' => '2026-09-22 17:45:00',
        ]);
        AttendanceRecord::factory()->for($employee)->absent()->create(['attendance_date' => '2026-08-22']);

        $this->actingAs($user)->get(route('admin.employees.show', [
            'employee' => $employee,
            'attendance_month' => 9,
            'attendance_year' => 2026,
        ]))->assertOk()
            ->assertSee('22 Sep 2026')
            ->assertSee('8h 30m')
            ->assertDontSee('22 Aug 2026');
    }

    public function test_archived_employee_profile_retains_historical_attendance(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        AttendanceRecord::factory()->for($employee)->absent()->create(['attendance_date' => '2026-09-22']);
        $employee->delete();

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('22 Sep 2026')
            ->assertSee('Absent');
    }

    public function test_dashboard_counts_explicit_present_and_absent_records_only(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        AttendanceRecord::factory()->count(2)->create(['attendance_date' => '2026-09-22', 'status' => AttendanceStatus::Present]);
        AttendanceRecord::factory()->absent()->create(['attendance_date' => '2026-09-22']);
        Employee::factory()->count(2)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('attendanceMetrics', ['present' => 2, 'absent' => 1]);
    }

    public function test_dashboard_excludes_archived_employees_and_missing_records(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $archived = Employee::factory()->archived()->create();
        AttendanceRecord::factory()->for($archived)->absent()->create(['attendance_date' => '2026-09-22']);
        Employee::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('attendanceMetrics', ['present' => 0, 'absent' => 0]);
    }

    public function test_notes_are_escaped_on_correction_page(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create(['notes' => '<script>alert("attendance")</script>']);

        $this->actingAs($user)->get(route('admin.attendance.edit', $record))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("attendance")</script>', false);
    }
}
