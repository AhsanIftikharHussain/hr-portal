<?php

namespace Tests\Feature\Employee;

use App\AttendanceSource;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceBusinessTimezoneTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pakistan_calendar_day_is_consistent_across_employee_admin_and_manager_dashboards(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 20:30:00', 'UTC'));
        $department = Department::factory()->create();
        $employeeUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'department_id' => $department->id,
        ]);
        AttendanceRecord::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-21',
            'check_in_at' => '2026-10-21 01:30:00',
            'check_out_at' => null,
            'source' => AttendanceSource::EmployeePortal,
        ]);
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $manager = User::factory()->withRole(Role::DEPARTMENT_MANAGER)->create();
        Employee::factory()->create(['user_id' => $manager->id, 'department_id' => $department->id]);

        $this->actingAs($employeeUser)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertViewHas('businessDate', fn (CarbonImmutable $date): bool => $date->toDateString() === '2026-10-21')
            ->assertSee('01:30 AM');
        $this->actingAs($hrAdmin)->get(route('admin.dashboard'))
            ->assertViewHas('today', fn ($date): bool => $date->toDateString() === '2026-10-21')
            ->assertViewHas('attendanceMetrics', fn (array $metrics): bool => $metrics['present'] === 1);
        $this->actingAs($manager)->get(route('manager.dashboard'))
            ->assertViewHas('today', fn ($date): bool => $date->toDateString() === '2026-10-21')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['present'] === 1);
    }

    public function test_contract_expiry_schedule_runs_at_eight_in_pakistan_timezone(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command, 'hr:notify-contract-expiry'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->getExpression());
        $this->assertSame('Asia/Karachi', $event->timezone);
        $this->assertSame('Asia/Karachi', config('app.business_timezone'));
        $this->assertSame('UTC', config('app.timezone'));
    }
}
