<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_is_forbidden_from_admin_and_manager_surfaces(): void
    {
        $user = $this->employeeUser();

        foreach ([
            route('admin.dashboard'),
            route('admin.employees.index'),
            route('admin.users.index'),
            route('admin.attendance.index'),
            route('admin.leave-requests.index'),
            route('admin.contracts.index'),
            route('admin.departments.index'),
            route('admin.job-titles.index'),
            route('manager.dashboard'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_employee_cannot_access_contract_or_document_downloads(): void
    {
        Storage::fake('local');
        $user = $this->employeeUser();
        $employee = $user->employee;
        $contract = EmployeeContract::factory()->create(['employee_id' => $employee->id]);
        $document = EmployeeDocument::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->get(route('admin.contracts.download', $contract))->assertForbidden();
        $this->actingAs($user)->get(route('admin.employee-documents.download', [$employee, $document]))->assertForbidden();
    }

    public function test_employee_cannot_mark_another_users_notification_as_read(): void
    {
        $user = $this->employeeUser();
        $otherUser = User::factory()->withRole(Role::EMPLOYEE)->create();
        $notification = $otherUser->notifications()->create([
            'id' => str()->uuid()->toString(),
            'type' => 'test-notification',
            'data' => ['title' => 'Private notification'],
        ]);

        $this->actingAs($user)->post(route('notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    private function employeeUser(): User
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        Employee::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
