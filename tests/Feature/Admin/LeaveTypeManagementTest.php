<?php

namespace Tests\Feature\Admin;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveTypeManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_role_cannot_manage_leave_types(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->get(route('admin.leave-types.index'))->assertForbidden();
    }

    public function test_hr_admin_can_create_and_update_leave_type(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.leave-types.store'), [
            'name' => 'Fictional Study Leave',
            'code' => 'study-leave',
            'is_paid' => '0',
            'requires_attachment' => '1',
        ])->assertRedirectToRoute('admin.leave-types.index');
        $leaveType = LeaveType::query()->where('code', 'study-leave')->sole();

        $this->actingAs($user)->put(route('admin.leave-types.update', $leaveType), [
            'name' => 'Fictional Education Leave',
            'code' => 'education-leave',
            'is_paid' => '1',
            'requires_attachment' => '0',
        ])->assertRedirectToRoute('admin.leave-types.index');

        $this->assertDatabaseHas('leave_types', [
            'id' => $leaveType->id,
            'name' => 'Fictional Education Leave',
            'code' => 'education-leave',
            'is_paid' => true,
            'requires_attachment' => false,
        ]);
    }

    public function test_leave_type_requires_unique_name_and_valid_stable_code(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        LeaveType::factory()->create(['name' => 'Annual Leave', 'code' => 'annual-leave']);

        $this->actingAs($user)->post(route('admin.leave-types.store'), [
            'name' => 'Annual Leave',
            'code' => 'Invalid Code!',
        ])->assertSessionHasErrors(['name', 'code']);
    }

    public function test_referenced_leave_type_is_deactivated_without_removing_history(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($user)->post(route('admin.leave-types.deactivate', $leaveRequest->leaveType))->assertRedirect();

        $this->assertDatabaseHas('leave_types', ['id' => $leaveRequest->leave_type_id, 'is_active' => false]);
        $this->assertModelExists($leaveRequest);
    }

    public function test_super_admin_can_reactivate_leave_type(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $leaveType = LeaveType::factory()->inactive()->create();

        $this->actingAs($user)->delete(route('admin.leave-types.reactivate', $leaveType))->assertRedirect();

        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id, 'is_active' => true]);
    }
}
