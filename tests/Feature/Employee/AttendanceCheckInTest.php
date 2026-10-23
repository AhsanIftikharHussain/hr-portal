<?php

namespace Tests\Feature\Employee;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_linked_employee_checks_in_with_server_authoritative_pakistan_values(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 20:03:00', 'UTC'));
        [$user, $employee] = $this->employeeContext();
        $otherEmployee = Employee::factory()->create();

        $this->actingAs($user)->post(route('employee.attendance.check-in'), [
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '1999-01-01',
            'check_in_at' => '1999-01-01 00:00:00',
            'status' => AttendanceStatus::Late->value,
            'source' => AttendanceSource::HrManual->value,
        ])->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'Checked in successfully at 01:03 AM.');

        $attendanceRecord = AttendanceRecord::query()->sole();
        $this->assertSame($employee->id, $attendanceRecord->employee_id);
        $this->assertSame('2026-10-21', $attendanceRecord->attendance_date->toDateString());
        $this->assertSame('2026-10-21 01:03:00', $attendanceRecord->check_in_at->format('Y-m-d H:i:s'));
        $this->assertNull($attendanceRecord->check_out_at);
        $this->assertSame(AttendanceStatus::Present, $attendanceRecord->status);
        $this->assertSame(AttendanceSource::EmployeePortal, $attendanceRecord->source);
        $this->assertSame($user->id, $attendanceRecord->recorded_by);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_duplicate_check_in_uses_database_constraint_and_preserves_original_record(): void
    {
        $this->travelTo('2026-10-21 10:30:00');
        [$user, $employee] = $this->employeeContext();
        $original = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 09:00:00',
            'check_out_at' => null,
            'source' => AttendanceSource::EmployeePortal,
            'recorded_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHasErrors(['attendance' => 'Attendance has already been recorded for today.']);

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame('2026-10-21 09:00:00', $original->fresh()->check_in_at->format('Y-m-d H:i:s'));
    }

    public function test_hr_created_attendance_cannot_be_replaced_by_employee_check_in(): void
    {
        $this->travelTo('2026-10-21 10:30:00');
        [$user, $employee] = $this->employeeContext();
        $hrRecord = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'source' => AttendanceSource::HrManual,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame(AttendanceSource::HrManual, $hrRecord->fresh()->source);
    }

    public function test_employee_can_check_in_on_a_weekend_holiday_without_special_status(): void
    {
        $this->travelTo('2026-10-25 09:00:00');
        [$user] = $this->employeeContext();
        Holiday::factory()->create(['holiday_date' => '2026-10-25', 'is_active' => true]);

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertSessionHasNoErrors();

        $attendanceRecord = AttendanceRecord::query()->sole();
        $this->assertSame('Sunday', $attendanceRecord->attendance_date->format('l'));
        $this->assertSame(AttendanceStatus::Present, $attendanceRecord->status);
    }

    public function test_approved_leave_is_not_changed_when_employee_checks_in(): void
    {
        $this->travelTo('2026-10-21 09:00:00');
        [$user, $employee] = $this->employeeContext();
        $leaveRequest = LeaveRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('You have approved leave covering today');
        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertSessionHasNoErrors();

        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    /** @return array{User, Employee} */
    private function employeeContext(): array
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        return [$user, $employee];
    }
}
