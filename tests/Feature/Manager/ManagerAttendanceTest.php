<?php

namespace Tests\Feature\Manager;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ManagerAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_attendance_is_read_only_and_scoped_to_managers_department(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);
        Employee::factory()->create(['department_id' => $department->id, 'full_name' => 'Visible Team Member']);
        $outsideEmployee = Employee::factory()->create(['department_id' => $otherDepartment->id, 'full_name' => 'Hidden Team Member']);

        $this->actingAs($manager)->get(route('manager.attendance.index'))
            ->assertOk()
            ->assertSee('Visible Team Member')
            ->assertDontSee('Hidden Team Member')
            ->assertDontSee('Record Attendance')
            ->assertDontSee('Correct');

        $this->actingAs($manager)->get(route('manager.attendance.index', ['employee_id' => $outsideEmployee->id]))
            ->assertSessionHasErrors('employee_id');

        $this->assertFalse(Route::has('manager.attendance.create'));
        $this->assertFalse(Route::has('manager.attendance.store'));
        $this->assertFalse(Route::has('manager.attendance.edit'));
        $this->assertFalse(Route::has('manager.attendance.update'));
    }
}
