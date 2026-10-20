<?php

namespace Tests\Feature\Manager;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ManagerAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_role_seeder_creates_department_manager_role_idempotently(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseHas('roles', ['slug' => Role::DEPARTMENT_MANAGER, 'name' => 'Department Manager']);
    }

    public function test_guest_and_non_manager_users_cannot_access_manager_routes(): void
    {
        $this->get(route('manager.dashboard'))->assertRedirectToRoute('login');

        foreach ([Role::EMPLOYEE, Role::HR_ADMIN] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->get(route('manager.dashboard'))->assertForbidden();
        }
    }

    public function test_manager_can_access_manager_pages_and_profile_with_dedicated_navigation(): void
    {
        $department = Department::factory()->create(['name' => 'Operations']);
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);

        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Department Attendance')
            ->assertSee('Department Leave')
            ->assertDontSee('User Management')
            ->assertDontSee('Contracts &amp; Documents', false);

        $this->actingAs($manager)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Department Management');
    }

    public function test_manager_is_forbidden_from_all_hr_administration_areas(): void
    {
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        $employee = Employee::factory()->create(['user_id' => $manager->id]);
        $contract = EmployeeContract::factory()->create(['employee_id' => $employee->id]);
        $document = EmployeeDocument::factory()->create(['employee_id' => $employee->id]);
        $attendanceRecord = AttendanceRecord::factory()->create(['employee_id' => $employee->id]);

        $adminUrls = [
            route('admin.dashboard'),
            route('admin.employees.index'),
            route('admin.employees.create'),
            route('admin.attendance.index'),
            route('admin.attendance.create'),
            route('admin.attendance.edit', $attendanceRecord),
            route('admin.leave-requests.index'),
            route('admin.contracts.index'),
            route('admin.policies.index'),
            route('admin.holidays.calendar'),
            route('admin.users.index'),
            route('admin.departments.index'),
            route('admin.job-titles.index'),
            route('admin.leave-types.index'),
            route('admin.employees.show', $employee),
            route('admin.employees.edit', $employee),
            route('admin.contracts.edit', $contract),
            route('admin.employee-documents.edit', [$employee, $document]),
            route('admin.contracts.download', $contract),
            route('admin.employee-documents.download', [$employee, $document]),
        ];

        foreach ($adminUrls as $url) {
            $this->actingAs($manager)->get($url)->assertForbidden();
        }

        $this->actingAs($manager)->post(route('admin.employees.archive', $employee))->assertForbidden();
        $this->assertNotSoftDeleted($employee);

        $this->assertFalse(Route::has('manager.employees.index'));
        $this->assertFalse(Route::has('manager.contracts.index'));
        $this->assertFalse(Route::has('manager.documents.index'));
    }

    public function test_manager_without_a_linked_employee_sees_safe_configuration_message_and_no_data(): void
    {
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['full_name' => 'Confidential Other Employee']);

        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Department access is not configured')
            ->assertDontSee('Confidential Other Employee');

        $this->actingAs($manager)->get(route('manager.attendance.index'))->assertOk()->assertSee('Department access is not configured');
        $this->actingAs($manager)->get(route('manager.leave-requests.index'))->assertOk()->assertSee('Department access is not configured');
    }

    public function test_manager_with_an_invalid_department_link_sees_safe_configuration_message(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);

        $department->update(['is_active' => false]);

        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Department access is not configured');
    }
}
