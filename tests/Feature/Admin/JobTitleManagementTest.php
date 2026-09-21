<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JobTitleManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_role_cannot_manage_designations(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->get(route('admin.job-titles.index'))->assertForbidden();
    }

    public function test_hr_admin_can_create_and_update_designation(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.job-titles.store'), ['name' => 'Fictional Specialist'])
            ->assertRedirectToRoute('admin.job-titles.index');
        $jobTitle = JobTitle::query()->where('name', 'Fictional Specialist')->sole();
        $this->actingAs($user)->put(route('admin.job-titles.update', $jobTitle), ['name' => 'Fictional Senior Specialist'])
            ->assertRedirectToRoute('admin.job-titles.index');
        $this->assertDatabaseHas('job_titles', ['id' => $jobTitle->id, 'name' => 'Fictional Senior Specialist']);
    }

    public function test_referenced_designation_is_deactivated_without_removing_relationship(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.job-titles.deactivate', $employee->jobTitle))->assertRedirect();
        $this->assertDatabaseHas('job_titles', ['id' => $employee->job_title_id, 'is_active' => false]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'job_title_id' => $employee->job_title_id]);
    }

    public function test_super_admin_can_reactivate_designation(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $jobTitle = JobTitle::factory()->create(['is_active' => false]);

        $this->actingAs($user)->delete(route('admin.job-titles.reactivate', $jobTitle))->assertRedirect();
        $this->assertDatabaseHas('job_titles', ['id' => $jobTitle->id, 'is_active' => true]);
    }
}
