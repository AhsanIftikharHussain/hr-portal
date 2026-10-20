<?php

namespace Tests\Feature\Admin;

use App\EmploymentStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DashboardPeopleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_lists_current_month_birthdays_without_birth_year_or_age(): void
    {
        $this->travelTo('2026-10-19 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $earlierBirthday = Employee::factory()->create([
            'employee_code' => 'BDAY-001',
            'full_name' => 'Earlier Birthday Employee',
            'date_of_birth' => '1988-10-05',
        ]);
        $laterBirthday = Employee::factory()->create([
            'employee_code' => 'BDAY-002',
            'full_name' => 'Later Birthday Employee',
            'date_of_birth' => '1992-10-25',
            'employment_status' => EmploymentStatus::OnProbation,
        ]);
        Employee::factory()->create(['employee_code' => 'BDAY-003', 'date_of_birth' => '1990-09-19']);
        Employee::factory()->create([
            'employee_code' => 'BDAY-004',
            'date_of_birth' => '1991-10-19',
            'employment_status' => EmploymentStatus::Inactive,
        ]);
        Employee::factory()->archived()->create(['employee_code' => 'BDAY-005', 'date_of_birth' => '1989-10-19']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('birthdaysThisMonth', function (Collection $employees) use ($earlierBirthday, $laterBirthday): bool {
                return $employees->pluck('id')->all() === [$earlierBirthday->id, $laterBirthday->id];
            })
            ->assertSeeInOrder(['Earlier Birthday Employee', 'Later Birthday Employee'])
            ->assertSee('05 Oct')
            ->assertSee('25 Oct')
            ->assertDontSee('1988')
            ->assertDontSee('1992')
            ->assertDontSee('years old');
    }

    public function test_dashboard_lists_current_month_completed_work_anniversaries(): void
    {
        $this->travelTo('2026-10-19 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $sixYears = Employee::factory()->create([
            'full_name' => 'Six Year Anniversary',
            'joining_date' => '2020-10-02',
        ]);
        $oneYear = Employee::factory()->create([
            'full_name' => 'One Year Anniversary',
            'joining_date' => '2025-10-25',
            'employment_status' => EmploymentStatus::NoticePeriod,
        ]);
        Employee::factory()->create(['joining_date' => '2026-10-01']);
        Employee::factory()->create(['joining_date' => '2020-09-19']);
        Employee::factory()->archived()->create(['joining_date' => '2020-10-19']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('workAnniversariesThisMonth', function (Collection $anniversaries) use ($sixYears, $oneYear): bool {
                return $anniversaries->pluck('employee.id')->all() === [$sixYears->id, $oneYear->id]
                    && $anniversaries->pluck('completedYears')->all() === [6, 1];
            })
            ->assertSeeInOrder(['Six Year Anniversary', 'One Year Anniversary'])
            ->assertSee('6 years')
            ->assertSee('1 year');
    }

    public function test_dashboard_lists_probation_confirmations_due_within_30_days(): void
    {
        $this->travelTo('2026-10-19 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $dueToday = Employee::factory()->create([
            'full_name' => 'Confirmation Due Today',
            'employment_status' => EmploymentStatus::OnProbation,
            'confirmation_date' => '2026-10-19',
        ]);
        $dueInThirtyDays = Employee::factory()->create([
            'full_name' => 'Confirmation Due In Thirty Days',
            'employment_status' => EmploymentStatus::OnProbation,
            'confirmation_date' => '2026-11-18',
        ]);
        Employee::factory()->create([
            'employment_status' => EmploymentStatus::OnProbation,
            'confirmation_date' => '2026-11-19',
        ]);
        Employee::factory()->create([
            'employment_status' => EmploymentStatus::Active,
            'confirmation_date' => '2026-10-20',
        ]);
        Employee::factory()->archived()->create([
            'employment_status' => EmploymentStatus::OnProbation,
            'confirmation_date' => '2026-10-20',
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('probationEndingSoon', function (Collection $employees) use ($dueToday, $dueInThirtyDays): bool {
                return $employees->pluck('employee.id')->all() === [$dueToday->id, $dueInThirtyDays->id]
                    && $employees->pluck('daysRemaining')->all() === [0, 30];
            })
            ->assertSeeInOrder(['Confirmation Due Today', 'Confirmation Due In Thirty Days'])
            ->assertSee('Due today')
            ->assertSee('30 days remaining');
    }

    public function test_dashboard_quick_actions_use_existing_authorized_routes(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Quick Actions')
            ->assertSee(route('admin.employees.create'))
            ->assertSee(route('admin.attendance.create'))
            ->assertSee(route('admin.leave-requests.index', ['status' => 'pending']));
    }

    public function test_dashboard_shows_empty_states_for_actionable_people_sections(): void
    {
        $this->travelTo('2026-10-19 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No pending leave requests')
            ->assertSee('No birthdays this month')
            ->assertSee('No work anniversaries this month')
            ->assertSee('No probation confirmations due in the next 30 days');
    }
}
