<?php

namespace Tests\Feature\Employee;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_sees_only_own_summary_and_explicit_today_attendance(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Portal Employee',
            'employee_code' => 'EMP-SELF',
        ]);
        Employee::factory()->create(['full_name' => 'Hidden Company Employee']);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => today(),
            'status' => AttendanceStatus::Late,
        ]);
        Holiday::factory()->create(['name' => 'Company Foundation Day', 'holiday_date' => today()->addWeek()]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Portal Employee')
            ->assertSee('EMP-SELF')
            ->assertSee('Late')
            ->assertSee('Company Foundation Day')
            ->assertDontSee('Hidden Company Employee');
    }

    public function test_dashboard_shows_not_recorded_when_today_has_no_attendance_record(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Not Recorded')
            ->assertSee(route('employee.attendance.check-in'))
            ->assertDontSee(route('employee.attendance.check-out'));
    }

    public function test_dashboard_shows_check_out_only_for_open_employee_portal_record(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 09:03:00',
            'check_out_at' => null,
            'source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('09:03 AM')
            ->assertSee(route('employee.attendance.check-out'))
            ->assertDontSee(route('employee.attendance.check-in'));
    }

    public function test_dashboard_shows_completed_attendance_without_mutation_buttons(): void
    {
        $this->travelTo('2026-10-21 18:00:00');
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 09:00:00',
            'check_out_at' => '2026-10-21 17:30:00',
            'source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('09:00 AM')
            ->assertSee('05:30 PM')
            ->assertSee('8.5 hours')
            ->assertDontSee(route('employee.attendance.check-in'))
            ->assertDontSee(route('employee.attendance.check-out'));
    }

    public function test_dashboard_marks_hr_record_as_hr_controlled_without_mutation_buttons(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'source' => AttendanceSource::HrManual,
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Attendance has been recorded by HR')
            ->assertDontSee(route('employee.attendance.check-in'))
            ->assertDontSee(route('employee.attendance.check-out'));
    }
}
