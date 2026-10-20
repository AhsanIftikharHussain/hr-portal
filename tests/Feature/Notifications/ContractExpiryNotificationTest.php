<?php

namespace Tests\Feature\Notifications;

use App\ContractStatus;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ContractExpiringSoon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use InvalidArgumentException;
use Tests\TestCase;

class ContractExpiryNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_notifies_active_super_and_hr_admins_but_not_manager_or_employee(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $superAdmin->roles()->syncWithoutDetaching(
            Role::query()->where('slug', Role::HR_ADMIN)->firstOrFail()->id,
        );
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        $employeeUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $inactiveHrAdmin = User::factory()->inactive()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create(['full_name' => 'Contract Recipient Employee']);
        EmployeeContract::factory()->create([
            'employee_id' => $employee->id,
            'status' => ContractStatus::Active,
            'end_date' => '2026-11-05',
        ]);

        $this->artisan('hr:notify-contract-expiry')
            ->expectsOutputToContain('sent 2')
            ->assertSuccessful();

        $this->assertSame(1, $superAdmin->notifications()->where('type', ContractExpiringSoon::class)->count());
        $this->assertSame(1, $hrAdmin->notifications()->where('type', ContractExpiringSoon::class)->count());
        $this->assertSame(0, $manager->notifications()->count());
        $this->assertSame(0, $employeeUser->notifications()->count());
        $this->assertSame(0, $inactiveHrAdmin->notifications()->count());
        $this->assertDatabaseCount('contract_expiry_notification_deliveries', 2);
    }

    public function test_command_excludes_irrelevant_statuses_outside_window_and_archived_employees(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-10-20']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Upcoming, 'end_date' => '2026-11-19']);

        foreach ([ContractStatus::Draft, ContractStatus::Expired, ContractStatus::Terminated, ContractStatus::Superseded] as $status) {
            EmployeeContract::factory()->create(['status' => $status, 'end_date' => '2026-11-01']);
        }

        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-10-19']);
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-11-20']);
        $archivedEmployee = Employee::factory()->archived()->create();
        EmployeeContract::factory()->create([
            'employee_id' => $archivedEmployee->id,
            'status' => ContractStatus::Active,
            'end_date' => '2026-11-01',
        ]);

        $this->artisan('hr:notify-contract-expiry')->assertSuccessful();

        $this->assertSame(2, $hrAdmin->notifications()->count());
        $this->assertDatabaseCount('contract_expiry_notification_deliveries', 2);
    }

    public function test_repeated_command_does_not_duplicate_notifications(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-11-01']);

        $this->artisan('hr:notify-contract-expiry')->assertSuccessful();
        $this->artisan('hr:notify-contract-expiry')
            ->expectsOutputToContain('sent 0, skipped 1 duplicate')
            ->assertSuccessful();

        $this->assertSame(1, $hrAdmin->notifications()->count());
        $this->assertDatabaseCount('contract_expiry_notification_deliveries', 1);
    }

    public function test_material_end_date_change_creates_one_new_notification(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $contract = EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-11-01']);

        $this->artisan('hr:notify-contract-expiry')->assertSuccessful();
        $contract->update(['end_date' => '2026-11-05']);
        $this->artisan('hr:notify-contract-expiry')->assertSuccessful();

        $this->assertSame(2, $hrAdmin->notifications()->count());
        $this->assertDatabaseCount('contract_expiry_notification_deliveries', 2);
    }

    public function test_mail_failure_preserves_database_notification_and_can_be_retried(): void
    {
        Exceptions::fake();
        config(['mail.default' => 'missing-mailer']);
        $this->travelTo('2026-10-20 08:00:00');
        User::factory()->withRole(Role::HR_ADMIN)->create();
        EmployeeContract::factory()->create(['status' => ContractStatus::Active, 'end_date' => '2026-11-01']);

        $this->artisan('hr:notify-contract-expiry')->assertFailed();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('contract_expiry_notification_deliveries', [
            'database_notified_at' => '2026-10-20 08:00:00',
            'mail_notified_at' => null,
        ]);
        Exceptions::assertReported(InvalidArgumentException::class);

        config(['mail.default' => 'array']);
        $this->artisan('hr:notify-contract-expiry')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseMissing('contract_expiry_notification_deliveries', ['mail_notified_at' => null]);
    }
}
