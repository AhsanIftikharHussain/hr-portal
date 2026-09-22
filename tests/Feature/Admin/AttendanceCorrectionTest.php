<?php

namespace Tests\Feature\Admin;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_existing_record_can_be_corrected_with_audit_metadata(): void
    {
        $this->travelTo('2026-09-22 12:30:00');
        $corrector = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create([
            'attendance_date' => '2026-09-21',
            'status' => AttendanceStatus::Present,
            'source' => AttendanceSource::HrManual,
        ]);

        $this->actingAs($corrector)->put(route('admin.attendance.update', $record), [
            'employee_id' => $record->employee_id,
            'attendance_date' => '2026-09-21',
            'status' => AttendanceStatus::Late->value,
            'check_in_at' => '2026-09-21T10:00',
            'check_out_at' => '2026-09-21T18:00',
            'notes' => 'Corrected arrival time.',
        ])->assertRedirectToRoute('admin.attendance.index', ['date' => '2026-09-21']);

        $record->refresh();
        $this->assertSame(AttendanceStatus::Late, $record->status);
        $this->assertSame(AttendanceSource::HrCorrection, $record->source);
        $this->assertSame(AttendanceSource::HrManual, $record->original_source);
        $this->assertSame($corrector->id, $record->corrected_by);
        $this->assertSame('2026-09-22 12:30:00', $record->corrected_at->format('Y-m-d H:i:s'));
    }

    public function test_repeated_correction_preserves_first_original_source(): void
    {
        $corrector = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create([
            'source' => AttendanceSource::HrCorrection,
            'original_source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($corrector)->put(route('admin.attendance.update', $record), [
            'employee_id' => $record->employee_id,
            'attendance_date' => $record->attendance_date->toDateString(),
            'status' => AttendanceStatus::Present->value,
            'check_in_at' => $record->check_in_at->format('Y-m-d\TH:i'),
            'check_out_at' => $record->check_out_at->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(AttendanceSource::EmployeePortal, $record->fresh()->original_source);
    }

    public function test_correction_cannot_create_duplicate_employee_date(): void
    {
        $corrector = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create(['attendance_date' => '2026-09-21']);
        AttendanceRecord::factory()->for($record->employee)->create(['attendance_date' => '2026-09-22']);

        $this->actingAs($corrector)->put(route('admin.attendance.update', $record), [
            'employee_id' => $record->employee_id,
            'attendance_date' => '2026-09-22',
            'status' => AttendanceStatus::Present->value,
        ])->assertSessionHasErrors(['attendance_date']);

        $this->assertSame('2026-09-21', $record->fresh()->attendance_date->toDateString());
    }

    public function test_archived_employee_historical_record_can_be_corrected_without_reassignment(): void
    {
        $corrector = User::factory()->withRole(Role::HR_ADMIN)->create();
        $record = AttendanceRecord::factory()->create();
        $record->employee->delete();

        $this->actingAs($corrector)->put(route('admin.attendance.update', $record), [
            'employee_id' => $record->employee_id,
            'attendance_date' => $record->attendance_date->toDateString(),
            'status' => AttendanceStatus::Absent->value,
            'notes' => 'Historical correction.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(AttendanceStatus::Absent, $record->fresh()->status);
    }
}
