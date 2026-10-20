<?php

namespace Tests\Feature\Employee;

use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_only_sees_own_filtered_attendance_without_hr_notes(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-10',
            'status' => AttendanceStatus::Late,
            'notes' => 'Private HR correction note',
        ]);
        AttendanceRecord::factory()->create([
            'attendance_date' => '2026-10-11',
            'notes' => 'Other employee private note',
        ]);

        $this->actingAs($user)->get(route('employee.attendance.index', ['month' => '2026-10', 'status' => 'late']))
            ->assertOk()
            ->assertSee('Sat, 10 Oct 2026')
            ->assertSee('Late')
            ->assertDontSee('Private HR correction note')
            ->assertDontSee('Other employee private note')
            ->assertDontSee('Sun, 11 Oct 2026');
    }

    public function test_employee_cannot_create_or_correct_attendance_through_admin_routes(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $record = AttendanceRecord::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->get(route('admin.attendance.create'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.attendance.edit', $record))->assertForbidden();
        $this->actingAs($user)->put(route('admin.attendance.update', $record), [])->assertForbidden();
    }
}
