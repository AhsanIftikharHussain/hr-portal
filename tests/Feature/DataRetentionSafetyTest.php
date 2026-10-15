<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\Policy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DataRetentionSafetyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_users_have_no_normal_permanent_delete_endpoint(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)
            ->delete("/admin/users/{$user->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($user);
    }

    public function test_leave_requests_have_no_normal_permanent_delete_endpoint(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($superAdmin)
            ->delete("/admin/leave/{$leaveRequest->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($leaveRequest);
    }

    public function test_attendance_records_have_no_normal_permanent_delete_endpoint(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $attendanceRecord = AttendanceRecord::factory()->create();

        $this->actingAs($superAdmin)
            ->delete("/admin/attendance/{$attendanceRecord->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($attendanceRecord);
    }

    public function test_employee_contracts_have_no_normal_permanent_delete_endpoint(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $employeeContract = EmployeeContract::factory()->create();

        $this->actingAs($superAdmin)
            ->delete("/admin/contracts/{$employeeContract->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($employeeContract);
    }

    public function test_policies_have_no_normal_permanent_delete_endpoint(): void
    {
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $policy = Policy::factory()->create();

        $this->actingAs($superAdmin)
            ->delete("/admin/policies/{$policy->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($policy);
    }
}
