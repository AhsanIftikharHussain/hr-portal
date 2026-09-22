<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractDocumentAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_contracts_and_private_downloads(): void
    {
        $contract = EmployeeContract::factory()->create();

        $this->get(route('admin.contracts.index'))->assertRedirectToRoute('login');
        $this->get(route('admin.contracts.download', $contract))->assertRedirectToRoute('login');
    }

    public function test_employee_role_is_forbidden_from_contracts_and_documents(): void
    {
        $user = User::factory()->withRole(Role::EMPLOYEE)->create();
        $employee = Employee::factory()->create();
        $contract = EmployeeContract::factory()->for($employee)->create();
        $document = EmployeeDocument::factory()->for($employee)->create();

        $this->actingAs($user)->get(route('admin.contracts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.contracts.download', $contract))->assertForbidden();
        $this->actingAs($user)->get(route('admin.employee-documents.download', [$employee, $document]))->assertForbidden();
    }

    public function test_hr_admin_and_super_admin_can_access_contract_management(): void
    {
        $hrAdmin = User::factory()->withRole(Role::HR_ADMIN)->create();
        $superAdmin = User::factory()->withRole(Role::SUPER_ADMIN)->create();

        $this->actingAs($hrAdmin)->get(route('admin.contracts.index'))->assertOk();
        $this->actingAs($hrAdmin)->get(route('admin.contracts.create'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.contracts.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.contracts.create'))->assertOk();
    }

    public function test_document_id_manipulation_returns_not_found(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $requestedEmployee = Employee::factory()->create();
        $actualEmployee = Employee::factory()->create();
        $document = EmployeeDocument::factory()->for($actualEmployee)->create([
            'file_path' => "employees/{$actualEmployee->id}/documents/private.pdf",
        ]);
        Storage::disk('local')->put($document->file_path, 'private');

        $this->actingAs($user)
            ->get(route('admin.employee-documents.download', [$requestedEmployee, $document]))
            ->assertNotFound();
    }

    public function test_manipulated_storage_path_cannot_download_arbitrary_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $contract = EmployeeContract::factory()->for($employee)->create([
            'file_path' => '../secrets.txt',
        ]);
        Storage::disk('local')->put('secrets.txt', 'secret');

        $this->actingAs($user)->get(route('admin.contracts.download', $contract))->assertNotFound();
    }
}
