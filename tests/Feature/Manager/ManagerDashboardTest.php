<?php

namespace Tests\Feature\Manager;

use App\AttendanceStatus;
use App\EmploymentStatus;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_metrics_and_lists_are_scoped_to_managers_department(): void
    {
        $department = Department::factory()->create(['name' => 'Managed Department']);
        $otherDepartment = Department::factory()->create(['name' => 'Other Department']);
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);

        $present = Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Managed Present']);
        $absent = Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Managed Absent']);
        $notRecorded = Employee::factory()->create(['department_id' => $department->id, 'employment_status' => EmploymentStatus::Active]);
        $outsideEmployee = Employee::factory()->create(['department_id' => $otherDepartment->id, 'full_name' => 'Outside Confidential']);
        AttendanceRecord::factory()->create(['employee_id' => $present->id, 'attendance_date' => today(), 'status' => AttendanceStatus::Present]);
        AttendanceRecord::factory()->absent()->create(['employee_id' => $absent->id, 'attendance_date' => today()]);
        AttendanceRecord::factory()->create(['employee_id' => $outsideEmployee->id, 'attendance_date' => today()]);

        LeaveRequest::factory()->create(['employee_id' => $present->id, 'status' => LeaveRequestStatus::Pending]);
        LeaveRequest::factory()->create(['employee_id' => $outsideEmployee->id, 'status' => LeaveRequestStatus::Pending]);

        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Managed Department Dashboard')
            ->assertSee('Managed Present')
            ->assertDontSee('Outside Confidential')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['present'] === 1
                && $metrics['absent'] === 1
                && $metrics['not_recorded'] === 2
                && $metrics['pending_leave'] === 1);
    }
}
