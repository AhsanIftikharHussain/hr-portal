<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_attendance(): void
    {
        $this->get(route('admin.attendance.index'))->assertRedirectToRoute('login');
    }

    public function test_employee_role_receives_403_for_attendance_routes(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $record = AttendanceRecord::factory()->create();

        $this->actingAs($user)->get(route('admin.attendance.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.attendance.edit', $record))->assertForbidden();
        $this->actingAs($user)->put(route('admin.attendance.update', $record), [])->assertForbidden();
    }

    public function test_hr_admin_can_access_daily_and_monthly_attendance(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.attendance.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.attendance.monthly'))->assertOk();
        $this->actingAs($user)->get(route('admin.attendance.create'))->assertOk();
    }

    public function test_super_admin_can_access_attendance_through_global_override(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.attendance.index'))->assertOk();
    }
}
