<?php

namespace Tests\Feature\Employee;

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
            ->assertSee('Not Recorded');
    }
}
