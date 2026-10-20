<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_sees_conservative_read_only_profile_fields(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Self Service Employee',
            'national_id' => 'SENSITIVE-NIC-001',
            'emergency_contact_name' => 'Sensitive Contact',
        ]);

        $this->actingAs($user)->get(route('employee.profile.show'))
            ->assertOk()
            ->assertSee('Self Service Employee')
            ->assertSee('read-only')
            ->assertDontSee('SENSITIVE-NIC-001')
            ->assertDontSee('Sensitive Contact')
            ->assertDontSee('name="employee_code"', false)
            ->assertDontSee('name="employment_status"', false);
    }

    public function test_employee_profile_route_has_no_employee_identifier_to_manipulate(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id, 'full_name' => 'Own Profile']);
        $otherEmployee = Employee::factory()->create(['full_name' => 'Other Profile']);

        $this->actingAs($user)->get(route('employee.profile.show', ['employee' => $otherEmployee->id]))
            ->assertOk()
            ->assertSee('Own Profile')
            ->assertDontSee('Other Profile');
    }
}
