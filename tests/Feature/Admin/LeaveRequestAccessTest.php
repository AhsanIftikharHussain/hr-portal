<?php

namespace Tests\Feature\Admin;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveRequestAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_leave_management(): void
    {
        $this->get(route('admin.leave-requests.index'))->assertRedirectToRoute('login');
    }

    public function test_employee_role_cannot_access_leave_management(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($user)->get(route('admin.leave-requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.leave-requests.show', $leaveRequest))->assertForbidden();
        $this->actingAs($user)->post(route('admin.leave-requests.approve', $leaveRequest))->assertForbidden();
    }

    public function test_hr_admin_can_access_leave_management(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.leave-requests.create'))->assertOk();
    }

    public function test_super_admin_can_access_leave_management_through_global_override(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.leave-requests.index'))->assertOk();
    }
}
