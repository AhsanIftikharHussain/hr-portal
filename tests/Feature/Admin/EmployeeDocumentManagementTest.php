<?php

namespace Tests\Feature\Admin;

use App\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_admin_uploads_valid_document_with_private_metadata(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.employee-documents.store', $employee), [
            'document_type' => EmployeeDocumentType::CvResume->value,
            'title' => 'Updated CV',
            'description' => 'Submitted to HR.',
            'document_date' => '2026-09-20',
            'document_file' => UploadedFile::fake()->create('candidate-cv.pdf', 100, 'application/pdf'),
            'file_path' => '../../public/malware.php',
            'uploaded_by' => 999999,
        ]);

        $document = EmployeeDocument::query()->sole();
        $response->assertRedirect(route('admin.employees.show', $employee).'#documents');
        $this->assertSame($employee->id, $document->employee_id);
        $this->assertSame($user->id, $document->uploaded_by);
        $this->assertSame('candidate-cv.pdf', $document->original_filename);
        $this->assertSame('2026-09-20', $document->document_date->toDateString());
        $this->assertStringStartsWith("employees/{$employee->id}/documents/", $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_invalid_and_oversized_document_files_are_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $payload = [
            'document_type' => EmployeeDocumentType::Other->value,
            'title' => 'Unsafe upload',
        ];

        $this->actingAs($user)->post(route('admin.employee-documents.store', $employee), $payload + [
            'document_file' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors(['document_file']);

        $this->actingAs($user)->post(route('admin.employee-documents.store', $employee), $payload + [
            'document_file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors(['document_file']);

        $this->assertDatabaseCount('employee_documents', 0);
    }

    public function test_archived_employee_cannot_receive_new_document(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->archived()->create();

        $this->actingAs($user)->post(route('admin.employee-documents.store', $employee), [
            'document_type' => EmployeeDocumentType::Other->value,
            'title' => 'Archived employee file',
            'document_file' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'),
        ])->assertNotFound();
    }

    public function test_secure_document_download_uses_original_filename(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $path = "employees/{$employee->id}/documents/degree.pdf";
        Storage::disk('local')->put($path, 'qualification');
        $document = EmployeeDocument::factory()->for($employee)->create([
            'file_path' => $path,
            'original_filename' => 'qualification-degree.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('admin.employee-documents.download', [$employee, $document]))
            ->assertOk()
            ->assertDownload('qualification-degree.pdf');
    }

    public function test_employee_profile_shows_metadata_but_not_internal_storage_path(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $document = EmployeeDocument::factory()->for($employee)->create([
            'document_type' => EmployeeDocumentType::QualificationDegree,
            'title' => 'University Degree',
            'document_date' => '2020-06-15',
            'file_path' => "employees/{$employee->id}/documents/private-key.pdf",
            'original_filename' => 'degree.pdf',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Qualification / Degree')
            ->assertSee('University Degree')
            ->assertSee('15 Jun 2020')
            ->assertSee($user->name)
            ->assertDontSee($document->file_path);
    }

    public function test_document_metadata_and_file_can_be_replaced_safely(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $oldPath = "employees/{$employee->id}/documents/old.pdf";
        Storage::disk('local')->put($oldPath, 'old');
        $document = EmployeeDocument::factory()->for($employee)->create(['file_path' => $oldPath]);

        $this->actingAs($user)->put(route('admin.employee-documents.update', [$employee, $document]), [
            'document_type' => EmployeeDocumentType::EmploymentLetter->value,
            'title' => 'Employment Verification',
            'description' => 'Replacement supplied by HR.',
            'document_date' => '2026-09-22',
            'document_file' => UploadedFile::fake()->create('employment-letter.pdf', 80, 'application/pdf'),
            'employee_id' => Employee::factory()->create()->id,
        ])->assertRedirect(route('admin.employees.show', $employee).'#documents');

        $document->refresh();
        $this->assertSame($employee->id, $document->employee_id);
        $this->assertSame('Employment Verification', $document->title);
        $this->assertSame('employment-letter.pdf', $document->original_filename);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_archiving_document_retains_private_file_and_soft_deletes_metadata(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        $path = "employees/{$employee->id}/documents/retained.pdf";
        Storage::disk('local')->put($path, 'retained');
        $document = EmployeeDocument::factory()->for($employee)->create(['file_path' => $path]);

        $this->actingAs($user)
            ->delete(route('admin.employee-documents.destroy', [$employee, $document]))
            ->assertRedirect(route('admin.employees.show', $employee).'#documents');

        $this->assertSoftDeleted($document);
        Storage::disk('local')->assertExists($path);
    }

    public function test_document_title_and_description_are_escaped_on_employee_profile(): void
    {
        $user = User::factory()->withRole(Role::HR_ADMIN)->create();
        $employee = Employee::factory()->create();
        EmployeeDocument::factory()->for($employee)->create([
            'title' => '<script>alert("document")</script>',
            'description' => '<img src=x onerror=alert(1)>',
        ]);

        $this->actingAs($user)->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("document")</script>', false);
    }
}
