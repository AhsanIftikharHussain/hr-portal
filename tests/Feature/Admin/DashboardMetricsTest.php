<?php

namespace Tests\Feature\Admin;

use App\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_metrics_count_current_employees_by_status_and_exclude_archived_records(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->count(2)->create(['employment_status' => EmploymentStatus::Active]);
        Employee::factory()->create(['employment_status' => EmploymentStatus::OnProbation]);
        Employee::factory()->create(['employment_status' => EmploymentStatus::NoticePeriod]);
        Employee::factory()->create(['employment_status' => EmploymentStatus::Resigned]);
        Employee::factory()->archived()->create(['employment_status' => EmploymentStatus::Active]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return collect($metrics)->pluck('count', 'label')->all() === [
                    'Total Employees' => 5,
                    'Active Employees' => 2,
                    'On Probation' => 1,
                    'Notice Period' => 1,
                ];
            });
    }

    public function test_status_overview_includes_accurate_counts_and_zero_values(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->count(2)->create(['employment_status' => EmploymentStatus::Terminated]);
        Employee::factory()->create(['employment_status' => EmploymentStatus::LaidOff]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('statusOverview', function (Collection $statusOverview): bool {
                $counts = $statusOverview->mapWithKeys(fn (array $item): array => [
                    $item['status']->value => $item['count'],
                ])->all();

                return $counts === [
                    'active' => 0,
                    'on-probation' => 0,
                    'notice-period' => 0,
                    'resigned' => 0,
                    'terminated' => 2,
                    'laid-off' => 1,
                    'inactive' => 0,
                ];
            });
    }

    public function test_department_overview_counts_only_active_non_archived_employees(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $development = Department::factory()->create(['name' => 'Fictional Development']);
        $design = Department::factory()->create(['name' => 'Fictional Design']);
        $inactiveDepartment = Department::factory()->create(['name' => 'Inactive Department', 'is_active' => false]);
        $jobTitle = JobTitle::factory()->create();
        Employee::factory()->count(2)->create(['department_id' => $development->id, 'job_title_id' => $jobTitle->id, 'employment_status' => EmploymentStatus::Active]);
        Employee::factory()->create(['department_id' => $development->id, 'job_title_id' => $jobTitle->id, 'employment_status' => EmploymentStatus::OnProbation]);
        Employee::factory()->archived()->create(['department_id' => $development->id, 'job_title_id' => $jobTitle->id, 'employment_status' => EmploymentStatus::Active]);
        Employee::factory()->create(['department_id' => $design->id, 'job_title_id' => $jobTitle->id, 'employment_status' => EmploymentStatus::Active]);
        Employee::factory()->create(['department_id' => $inactiveDepartment->id, 'job_title_id' => $jobTitle->id, 'employment_status' => EmploymentStatus::Active]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewHas('departments', function (Collection $departments) use ($development, $design): bool {
                return $departments->pluck('active_employees_count', 'id')->all() === [
                    $development->id => 2,
                    $design->id => 1,
                ];
            })
            ->assertSee(route('admin.employees.index', [
                'department_id' => $development->id,
                'employment_status' => EmploymentStatus::Active->value,
            ]));
    }

    public function test_recently_joined_employees_are_ordered_limited_and_exclude_archived_records(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->create(['full_name' => 'Earlier Current Employee', 'joining_date' => '2025-01-10']);
        Employee::factory()->create(['full_name' => 'Newest Current Employee', 'joining_date' => '2026-08-10']);
        Employee::factory()->create(['full_name' => 'Middle Current Employee', 'joining_date' => '2026-03-10']);
        Employee::factory()->archived()->create(['full_name' => 'Archived Newest Employee', 'joining_date' => '2026-09-10']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Newest Current Employee', 'Middle Current Employee', 'Earlier Current Employee'])
            ->assertDontSee('Archived Newest Employee');
    }

    public function test_dashboard_does_not_display_sensitive_employee_information(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        Employee::factory()->create([
            'full_name' => 'Visible Demo Employee',
            'national_id' => 'PRIVATE-NIC-555',
            'contact_number' => '+92 300 9999999',
            'address' => 'Private Residential Address',
            'emergency_contact_name' => 'Private Emergency Contact',
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Visible Demo Employee')
            ->assertDontSee('PRIVATE-NIC-555')
            ->assertDontSee('+92 300 9999999')
            ->assertDontSee('Private Residential Address')
            ->assertDontSee('Private Emergency Contact');
    }

    public function test_status_metrics_link_to_existing_employee_filters(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.employees.index', ['employment_status' => EmploymentStatus::Active->value]))
            ->assertSee(route('admin.employees.index', ['employment_status' => EmploymentStatus::OnProbation->value]))
            ->assertSee(route('admin.employees.index', ['employment_status' => EmploymentStatus::NoticePeriod->value]));
    }
}
