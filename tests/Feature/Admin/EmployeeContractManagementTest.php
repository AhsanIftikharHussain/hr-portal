<?php

namespace Tests\Feature\Admin;

use App\ContractStatus;
use App\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeContractManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_admin_uploads_contract_with_server_controlled_private_metadata(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.contracts.store'), [
            'employee_id' => $employee->id,
            'contract_type' => ContractType::FixedTerm->value,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => ContractStatus::Active->value,
            'notice_period' => '30 days',
            'notes' => 'Signed fixed-term agreement.',
            'contract_file' => UploadedFile::fake()->create('signed-contract.pdf', 100, 'application/pdf'),
            'file_path' => '../client-controlled.pdf',
            'uploaded_by' => 999999,
        ]);

        $contract = EmployeeContract::query()->sole();
        $response->assertRedirectToRoute('admin.contracts.index');
        $this->assertSame($employee->id, $contract->employee_id);
        $this->assertSame($user->id, $contract->uploaded_by);
        $this->assertSame('signed-contract.pdf', $contract->original_filename);
        $this->assertStringStartsWith("employees/{$employee->id}/contracts/", $contract->file_path);
        $this->assertStringNotContainsString('client-controlled', $contract->file_path);
        Storage::disk('local')->assertExists($contract->file_path);
    }

    public function test_contract_validation_rejects_missing_fields_invalid_dates_and_non_pdf_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.contracts.store'), [
            'employee_id' => $employee->id,
            'contract_type' => 'unknown',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
            'status' => 'invalid',
            'contract_file' => UploadedFile::fake()->create('malware.php', 5, 'application/x-php'),
        ])->assertSessionHasErrors(['contract_type', 'end_date', 'status', 'contract_file']);

        $this->assertDatabaseCount('employee_contracts', 0);
    }

    public function test_archived_employee_cannot_receive_a_new_contract(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->archived()->create();

        $this->actingAs($user)->post(route('admin.contracts.store'), [
            'employee_id' => $employee->id,
            'contract_type' => ContractType::Permanent->value,
            'start_date' => '2026-01-01',
            'status' => ContractStatus::Active->value,
            'contract_file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['employee_id']);
    }

    public function test_multiple_contracts_preserve_employee_history(): void
    {
        $employee = Employee::factory()->create();
        EmployeeContract::factory()->for($employee)->create([
            'status' => ContractStatus::Superseded,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        EmployeeContract::factory()->for($employee)->create([
            'status' => ContractStatus::Active,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertSame(2, $employee->contracts()->count());
        $this->assertSame(
            [ContractStatus::Superseded, ContractStatus::Active],
            $employee->contracts()->oldest('start_date')->pluck('status')->all(),
        );
    }

    public function test_contract_filters_by_employee_department_type_status_and_expiry_window(): void
    {
        $this->travelTo('2026-09-22 09:00:00');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $department = Department::factory()->create();
        $matchingEmployee = Employee::factory()->for($department)->create(['full_name' => 'Matching Contract Employee']);
        $otherEmployee = Employee::factory()->create(['full_name' => 'Other Contract Employee']);
        EmployeeContract::factory()->for($matchingEmployee)->create([
            'contract_type' => ContractType::FixedTerm,
            'status' => ContractStatus::Active,
            'end_date' => '2026-10-10',
        ]);
        EmployeeContract::factory()->for($otherEmployee)->create([
            'contract_type' => ContractType::Permanent,
            'status' => ContractStatus::Expired,
            'end_date' => '2026-09-01',
        ]);

        $this->actingAs($user)->get(route('admin.contracts.index', [
            'search' => 'Matching Contract',
            'department_id' => $department->id,
            'contract_type' => ContractType::FixedTerm->value,
            'status' => ContractStatus::Active->value,
            'expiry_window' => 30,
        ]))->assertOk()
            ->assertSee('Matching Contract Employee')
            ->assertDontSee('Other Contract Employee');
    }

    public function test_authorized_download_returns_private_contract_without_exposing_path(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $path = "employees/{$employee->id}/contracts/contract.pdf";
        Storage::disk('local')->put($path, 'private contract');
        $contract = EmployeeContract::factory()->for($employee)->create([
            'file_path' => $path,
            'original_filename' => 'signed-contract.pdf',
        ]);

        $response = $this->actingAs($user)->get(route('admin.contracts.download', $contract));

        $response->assertOk()->assertDownload('signed-contract.pdf');
        $this->assertStringNotContainsString($path, $response->headers->get('content-disposition', ''));
    }

    public function test_replacing_contract_file_removes_old_private_file_and_preserves_record(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $oldPath = "employees/{$employee->id}/contracts/old.pdf";
        Storage::disk('local')->put($oldPath, 'old');
        $contract = EmployeeContract::factory()->for($employee)->create(['file_path' => $oldPath]);

        $this->actingAs($user)->put(route('admin.contracts.update', $contract), [
            'contract_type' => ContractType::Permanent->value,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'status' => ContractStatus::Active->value,
            'notice_period' => '60 days',
            'notes' => 'Updated metadata.',
            'contract_file' => UploadedFile::fake()->create('replacement.pdf', 120, 'application/pdf'),
            'employee_id' => Employee::factory()->create()->id,
        ])->assertRedirectToRoute('admin.contracts.index');

        $contract->refresh();
        $this->assertSame($employee->id, $contract->employee_id);
        $this->assertSame('replacement.pdf', $contract->original_filename);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($contract->file_path);
        $this->assertSame(1, EmployeeContract::query()->count());
    }

    public function test_archived_employee_profile_retains_contract_history(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        EmployeeContract::factory()->for($employee)->create(['contract_type' => ContractType::Consultancy]);
        $employee->delete();

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Consultancy')
            ->assertSee('Contract History');
    }
}
