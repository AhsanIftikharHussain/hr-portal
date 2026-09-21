<?php

namespace Tests\Feature\Admin;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_employee_management(): void
    {
        $this->get(route('admin.employees.index'))->assertRedirect(route('login'));
    }

    public function test_employee_role_is_forbidden_from_employee_management(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();

        $this->actingAs($user)->get(route('admin.employees.index'))->assertForbidden();
    }

    public function test_hr_admin_can_access_employee_management(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.employees.index'))->assertOk()->assertSee('Employees');
    }

    public function test_super_admin_can_access_employee_management(): void
    {
        $user = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.employees.index'))->assertOk();
    }

    public function test_hr_admin_can_render_employee_create_and_edit_forms(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->get(route('admin.employees.create'))
            ->assertOk()->assertSee('Personal Information')->assertSee('Emergency Contact');
        $this->actingAs($user)->get(route('admin.employees.edit', $employee))
            ->assertOk()->assertSee($employee->full_name);
    }

    public function test_valid_employee_can_be_created(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        [$department, $jobTitle] = $this->referenceData();

        $response = $this->actingAs($user)->post(route('admin.employees.store'), $this->validPayload($department, $jobTitle));

        $employee = Employee::query()->sole();
        $response->assertRedirectToRoute('admin.employees.show', $employee);
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-9001',
            'full_name' => 'Fictional Employee',
            'department_id' => $department->id,
            'job_title_id' => $jobTitle->id,
            'user_id' => null,
        ]);
    }

    public function test_required_employee_fields_return_clear_validation_errors(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->post(route('admin.employees.store'), [])
            ->assertInvalid([
                'employee_code' => 'The employee code field is required.',
                'full_name' => 'The full name field is required.',
                'department_id' => 'The department field is required.',
                'job_title_id' => 'The designation field is required.',
                'employment_type' => 'The employment type field is required.',
                'employment_status' => 'The employment status field is required.',
                'joining_date' => 'The joining date field is required.',
            ]);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_duplicate_employee_code_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['employee_code' => 'EMP-9001']);

        $this->actingAs($user)->post(route('admin.employees.store'), $this->validPayload($employee->department, $employee->jobTitle))
            ->assertInvalid(['employee_code']);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_invalid_relationships_are_rejected(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        [$department, $jobTitle] = $this->referenceData();
        $payload = $this->validPayload($department, $jobTitle);
        $payload['department_id'] = 999991;
        $payload['job_title_id'] = 999992;
        $payload['reporting_manager_id'] = 999993;

        $this->actingAs($user)->post(route('admin.employees.store'), $payload)
            ->assertInvalid(['department_id', 'job_title_id', 'reporting_manager_id']);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_can_be_updated_without_changing_unique_values(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['employee_code' => 'EMP-9001', 'official_email' => 'employee@company.example.test']);
        $payload = $this->validPayload($employee->department, $employee->jobTitle) + ['official_email' => $employee->official_email];
        $payload['full_name'] = 'Updated Fictional Employee';

        $this->actingAs($user)->put(route('admin.employees.update', $employee), $payload)
            ->assertRedirectToRoute('admin.employees.show', $employee);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'full_name' => 'Updated Fictional Employee']);
    }

    public function test_employee_cannot_report_to_themselves(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['employee_code' => 'EMP-9001']);
        $payload = $this->validPayload($employee->department, $employee->jobTitle);
        $payload['reporting_manager_id'] = $employee->id;

        $this->actingAs($user)->put(route('admin.employees.update', $employee), $payload)
            ->assertInvalid(['reporting_manager_id']);
        $this->assertNull($employee->fresh()->reporting_manager_id);
    }

    public function test_employee_list_searches_code_name_and_email(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->create(['employee_code' => 'MATCH-100', 'full_name' => 'Visible Person']);
        Employee::factory()->create(['employee_code' => 'OTHER-200', 'full_name' => 'Hidden Person']);

        $this->actingAs($user)->get(route('admin.employees.index', ['search' => 'MATCH-100']))
            ->assertOk()->assertSee('Visible Person')->assertDontSee('Hidden Person');
    }

    public function test_employee_list_filters_department_status_and_type(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $matchingDepartment = Department::factory()->create(['name' => 'Matching Department']);
        $otherDepartment = Department::factory()->create(['name' => 'Other Department']);
        $jobTitle = JobTitle::factory()->create();
        Employee::factory()->create([
            'full_name' => 'Matching Person', 'department_id' => $matchingDepartment->id, 'job_title_id' => $jobTitle->id,
            'employment_status' => EmploymentStatus::OnProbation, 'employment_type' => EmploymentType::Contract,
        ]);
        Employee::factory()->create(['full_name' => 'Other Person', 'department_id' => $otherDepartment->id, 'job_title_id' => $jobTitle->id]);

        $this->actingAs($user)->get(route('admin.employees.index', [
            'department_id' => $matchingDepartment->id,
            'employment_status' => EmploymentStatus::OnProbation->value,
            'employment_type' => EmploymentType::Contract->value,
        ]))->assertOk()->assertSee('Matching Person')->assertDontSee('Other Person');
    }

    public function test_employee_list_is_paginated(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->count(16)->create();

        $this->actingAs($user)->get(route('admin.employees.index'))
            ->assertOk()->assertViewHas('employees', fn ($employees): bool => $employees->count() === 15 && $employees->lastPage() === 2);
    }

    public function test_archiving_preserves_record_and_archived_records_can_be_restored(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['employment_status' => EmploymentStatus::Active]);

        $this->actingAs($user)->post(route('admin.employees.archive', $employee))->assertRedirectToRoute('admin.employees.index');
        $this->assertSoftDeleted($employee);
        $this->assertSame(EmploymentStatus::Active, $employee->fresh()->employment_status);
        $this->actingAs($user)->get(route('admin.employees.index', ['record_status' => 'archived']))
            ->assertOk()->assertSee($employee->full_name);

        $this->actingAs($user)->delete(route('admin.employees.restore', $employee))->assertRedirect();
        $this->assertNotSoftDeleted($employee);
    }

    public function test_employee_list_does_not_expose_sensitive_profile_fields(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->create(['full_name' => 'Safe Display', 'national_id' => 'SECRET-NIC-123', 'address' => 'Private test address']);

        $this->actingAs($user)->get(route('admin.employees.index'))
            ->assertOk()->assertSee('Safe Display')->assertDontSee('SECRET-NIC-123')->assertDontSee('Private test address');
    }

    public function test_employee_profile_escapes_user_supplied_content(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['full_name' => '<script>alert("test")</script>']);

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("test")</script>', false);
    }

    /** @return array{Department, JobTitle} */
    private function referenceData(): array
    {
        return [Department::factory()->create(), JobTitle::factory()->create()];
    }

    /** @return array<string, mixed> */
    private function validPayload(Department $department, JobTitle $jobTitle): array
    {
        return [
            'employee_code' => 'EMP-9001',
            'full_name' => 'Fictional Employee',
            'national_id' => 'TEST-NIC-9001',
            'personal_email' => 'fictional.employee@example.test',
            'official_email' => 'fictional.employee@company.example.test',
            'department_id' => $department->id,
            'job_title_id' => $jobTitle->id,
            'employment_type' => EmploymentType::Permanent->value,
            'employment_status' => EmploymentStatus::Active->value,
            'joining_date' => '2026-01-15',
        ];
    }
}
