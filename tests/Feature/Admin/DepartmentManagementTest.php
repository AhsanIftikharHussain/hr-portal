<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_role_cannot_manage_departments(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->get(route('admin.departments.index'))->assertForbidden();
    }

    public function test_hr_admin_can_create_and_update_department(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.departments.store'), ['name' => 'Fictional Operations'])
            ->assertRedirectToRoute('admin.departments.index');
        $department = Department::query()->where('name', 'Fictional Operations')->sole();
        $this->actingAs($user)->put(route('admin.departments.update', $department), ['name' => 'Fictional Business Operations'])
            ->assertRedirectToRoute('admin.departments.index');
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Fictional Business Operations']);
    }

    public function test_referenced_department_is_deactivated_without_removing_relationship(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.departments.deactivate', $employee->department))
            ->assertRedirect();
        $this->assertDatabaseHas('departments', ['id' => $employee->department_id, 'is_active' => false]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'department_id' => $employee->department_id]);
    }

    public function test_super_admin_can_reactivate_department(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $department = Department::factory()->create(['is_active' => false]);

        $this->actingAs($user)->delete(route('admin.departments.reactivate', $department))->assertRedirect();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'is_active' => true]);
    }
}
