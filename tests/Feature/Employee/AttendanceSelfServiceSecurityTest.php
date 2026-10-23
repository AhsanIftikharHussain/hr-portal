<?php

namespace Tests\Feature\Employee;

use App\AttendanceSource;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceSelfServiceSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unlinked_employee_cannot_check_in_or_check_out(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHasErrors('attendance');
        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_archived_employee_link_is_not_valid_for_self_service(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id])->delete();

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_inactive_employee_user_is_logged_out_before_check_in(): void
    {
        $user = User::factory()->inactive()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('employee.attendance.check-in'))
            ->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_department_manager_role_alone_cannot_use_employee_attendance_mutations(): void
    {
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id]);

        $this->actingAs($manager)->post(route('employee.attendance.check-in'))->assertForbidden();
        $this->actingAs($manager)->post(route('employee.attendance.check-out'))->assertForbidden();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_employee_check_out_cannot_mutate_another_employees_record(): void
    {
        $this->travelTo('2026-10-21 17:00:00');
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id]);
        $otherRecord = AttendanceRecord::factory()->create([
            'attendance_date' => '2026-10-21',
            'check_out_at' => null,
            'source' => AttendanceSource::EmployeePortal,
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-out'))
            ->assertSessionHasErrors('attendance');

        $this->assertNull($otherRecord->fresh()->check_out_at);
    }

    public function test_dual_role_manager_keeps_manager_login_priority(): void
    {
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        $employeeRole = Role::factory()->create(['name' => 'Employee', 'slug' => Role::EMPLOYEE]);
        $manager->roles()->attach($employeeRole);
        Employee::factory()->create(['user_id' => $manager->id]);

        $this->post(route('login'), [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertRedirectToRoute('manager.dashboard');
    }
}
