<?php

namespace Tests\Feature\Employee;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceCheckOutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_checked_in_employee_checks_out_with_server_time_and_preserves_record_metadata(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-21 17:42:00', 'Asia/Karachi'));
        [$user, $employee] = $this->employeeContext();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 09:03:00',
            'check_out_at' => null,
            'status' => AttendanceStatus::Late,
            'source' => AttendanceSource::EmployeePortal,
            'recorded_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-out'), [
            'check_out_at' => '1999-01-01 00:00:00',
            'status' => AttendanceStatus::Absent->value,
            'source' => AttendanceSource::HrCorrection->value,
        ])->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'Checked out successfully at 05:42 PM.');

        $attendanceRecord->refresh();
        $this->assertSame('2026-10-21 09:03:00', $attendanceRecord->check_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 17:42:00', $attendanceRecord->check_out_at->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceStatus::Late, $attendanceRecord->status);
        $this->assertSame(AttendanceSource::EmployeePortal, $attendanceRecord->source);
        $this->assertNull($attendanceRecord->original_source);
        $this->assertSame($user->id, $attendanceRecord->recorded_by);
    }

    public function test_check_out_without_check_in_is_rejected(): void
    {
        $this->travelTo('2026-10-21 17:42:00');
        [$user] = $this->employeeContext();

        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHasErrors(['attendance' => 'You must check in before checking out.']);

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_duplicate_check_out_is_atomic_and_does_not_change_original_time(): void
    {
        $this->travelTo('2026-10-21 18:30:00');
        [$user, $employee] = $this->employeeContext();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 09:00:00',
            'check_out_at' => '2026-10-21 17:30:00',
            'source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertSessionHasErrors(['attendance' => 'You have already checked out for today.']);

        $this->assertSame('2026-10-21 17:30:00', $attendanceRecord->fresh()->check_out_at->format('Y-m-d H:i:s'));
    }

    public function test_hr_manual_record_with_open_time_cannot_be_mutated_by_employee(): void
    {
        $this->travelTo('2026-10-21 18:30:00');
        [$user, $employee] = $this->employeeContext();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_out_at' => null,
            'source' => AttendanceSource::HrManual,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertSessionHasErrors(['attendance' => 'Attendance has been recorded by HR and can only be changed by HR.']);

        $this->assertNull($attendanceRecord->fresh()->check_out_at);
    }

    public function test_hr_correction_record_cannot_be_mutated_by_employee(): void
    {
        $this->travelTo('2026-10-21 18:30:00');
        [$user, $employee] = $this->employeeContext();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_out_at' => null,
            'source' => AttendanceSource::HrCorrection,
            'original_source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertSessionHasErrors('attendance');

        $this->assertNull($attendanceRecord->fresh()->check_out_at);
        $this->assertSame(AttendanceSource::EmployeePortal, $attendanceRecord->fresh()->original_source);
    }

    public function test_hr_can_correct_employee_portal_attendance_after_self_service(): void
    {
        $this->travelTo('2026-10-21 18:30:00');
        [, $employee] = $this->employeeContext();
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($hrAdmin)->put(route('admin.attendance.update', $attendanceRecord), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'status' => AttendanceStatus::HalfDay->value,
            'check_in_at' => '2026-10-21T09:00',
            'check_out_at' => '2026-10-21T13:00',
            'notes' => 'HR correction after employee self-service.',
        ])->assertSessionHasNoErrors();

        $attendanceRecord->refresh();
        $this->assertSame(AttendanceSource::HrCorrection, $attendanceRecord->source);
        $this->assertSame(AttendanceSource::EmployeePortal, $attendanceRecord->original_source);
        $this->assertSame($hrAdmin->id, $attendanceRecord->corrected_by);
    }

    /** @return array{User, Employee} */
    private function employeeContext(): array
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        return [$user, $employee];
    }
}
