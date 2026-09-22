<?php

namespace Tests\Feature\Admin;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceRecordManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_admin_creates_manual_attendance_with_server_controlled_metadata(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Present->value,
            'check_in_at' => '2026-09-22T09:00',
            'check_out_at' => '2026-09-22T17:30',
            'notes' => 'Recorded during test coverage.',
            'source' => AttendanceSource::EmployeePortal->value,
            'recorded_by' => 999999,
        ]);

        $record = AttendanceRecord::query()->sole();
        $response->assertRedirectToRoute('admin.attendance.index', ['date' => '2026-09-22']);
        $this->assertSame(AttendanceSource::HrManual, $record->source);
        $this->assertSame($user->id, $record->recorded_by);
        $this->assertSame(510, $record->workingDurationMinutes());
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [])
            ->assertSessionHasErrors(['employee_id', 'attendance_date', 'status']);

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_duplicate_employee_and_date_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create(['attendance_date' => '2026-09-22']);

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $record->employee_id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Absent->value,
        ])->assertSessionHasErrors(['attendance_date']);

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_archived_employee_is_rejected_for_new_attendance(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->archived()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Absent->value,
        ])->assertSessionHasErrors(['employee_id']);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => 'remote-working',
        ])->assertSessionHasErrors(['status']);
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Present->value,
            'check_in_at' => '2026-09-22T17:00',
            'check_out_at' => '2026-09-22T09:00',
        ])->assertSessionHasErrors(['check_out_at']);
    }

    public function test_invalid_time_format_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Present->value,
            'check_in_at' => 'nine in the morning',
        ])->assertSessionHasErrors(['check_in_at']);
    }

    public function test_check_out_without_check_in_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Present->value,
            'check_out_at' => '2026-09-22T17:00',
        ])->assertSessionHasErrors(['check_in_at']);
    }

    public function test_absent_and_leave_records_reject_times(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Absent->value,
            'check_in_at' => '2026-09-22T09:00',
        ])->assertSessionHasErrors(['check_in_at']);
    }

    public function test_future_dated_manual_attendance_is_allowed_without_inventing_policy(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2099-12-01',
            'status' => AttendanceStatus::Absent->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2099-12-01', AttendanceRecord::query()->sole()->attendance_date->toDateString());
    }
}
