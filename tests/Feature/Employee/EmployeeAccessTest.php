<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EmployeeAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_employee_portal(): void
    {
        $this->get(route('employee.dashboard'))->assertRedirectToRoute('login');
    }

    public function test_non_employee_roles_cannot_access_employee_portal(): void
    {
        foreach ([Role::HR_ADMIN, Role::DEPARTMENT_MANAGER] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->actingAs($user)->get(route('employee.dashboard'))->assertForbidden();
        }
    }

    public function test_unlinked_employee_account_sees_safe_message_without_employee_data(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['full_name' => 'Confidential Other Employee']);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Your account has not yet been linked to an employee record')
            ->assertDontSee('Confidential Other Employee');

        $this->actingAs($user)->get(route('employee.profile.show'))->assertOk()->assertSee('Please contact HR');
        $this->actingAs($user)->get(route('employee.leave-requests.index'))->assertOk()->assertSee('Please contact HR');
        $this->actingAs($user)->get(route('employee.attendance.index'))->assertOk()->assertSee('Please contact HR');
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_employee_portal_has_no_write_routes_for_attendance_or_hr_records(): void
    {
        $this->assertFalse(Route::has('employee.attendance.store'));
        $this->assertFalse(Route::has('employee.attendance.update'));
        $this->assertFalse(Route::has('employee.attendance.destroy'));
        $this->assertFalse(Route::has('employee.leave-requests.approve'));
        $this->assertFalse(Route::has('employee.leave-requests.reject'));
        $this->assertFalse(Route::has('employee.leave-requests.cancel'));
        $this->assertFalse(Route::has('employee.contracts.index'));
        $this->assertFalse(Route::has('employee.documents.index'));
    }
}
