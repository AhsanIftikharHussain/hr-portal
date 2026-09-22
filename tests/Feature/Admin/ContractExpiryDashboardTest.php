<?php

namespace Tests\Feature\Admin;

use App\ContractStatus;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ContractExpiryDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_counts_current_contracts_expiring_within_30_days(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-10-01']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Upcoming, 'end_date' => '2026-10-22']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-10-23']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Expired, 'end_date' => '2026-10-01']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Terminated, 'end_date' => '2026-10-01']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Superseded, 'end_date' => '2026-10-01']);
        $archivedEmployee = Employee::factory()->archived()->create();
        EmployeeContract::factory()->for($archivedEmployee)->create([
            'status' => ContractStatus::Active,
            'end_date' => '2026-10-01',
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('contractsExpiringSoon', 2)
            ->assertSee(route('admin.contracts.index', ['expiry_window' => 30]));
    }

    public function test_expiry_filter_matches_dashboard_definition(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $includedEmployee = Employee::factory()->create(['full_name' => 'Included Expiry Employee']);
        $excludedEmployee = Employee::factory()->create(['full_name' => 'Excluded Expiry Employee']);
        EmployeeContract::factory()->for($includedEmployee)->create([
            'status' => ContractStatus::Active,
            'end_date' => '2026-10-10',
        ]);
        EmployeeContract::factory()->for($excludedEmployee)->create([
            'status' => ContractStatus::Expired,
            'end_date' => '2026-10-10',
        ]);

        $this->actingAs($user)->get(route('admin.contracts.index', ['expiry_window' => 30]))
            ->assertOk()
            ->assertSee('Included Expiry Employee')
            ->assertDontSee('Excluded Expiry Employee');
    }
}
