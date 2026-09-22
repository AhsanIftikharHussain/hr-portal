<?php

namespace App\Http\Controllers\Admin;

use App\EmployeeDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Http\Requests\UpdateEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\PrivateEmployeeFileStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmployeeDocumentController extends Controller
{
    public function __construct(private PrivateEmployeeFileStorage $privateFiles) {}

    public function create(Employee $employee): View
    {
        Gate::authorize('create', EmployeeDocument::class);
        abort_if($employee->trashed(), 403);

        return view('admin.documents.create', [
            'employee' => $employee,
            'document' => new EmployeeDocument,
            'documentTypes' => EmployeeDocumentType::cases(),
        ]);
    }

    public function store(StoreEmployeeDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $metadata = $this->privateFiles->store($request->file('document_file'), $employee, 'documents');

        try {
            EmployeeDocument::query()->create([
                ...$request->safe()->only(['document_type', 'title', 'description', 'document_date']),
                ...$metadata,
                'employee_id' => $employee->id,
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            $this->privateFiles->delete($metadata['file_path'], $employee, 'documents');

            throw $exception;
        }

        return redirect()->to(route('admin.employees.show', $employee).'#documents')
            ->with('status', 'Employee document uploaded.');
    }

    public function edit(Employee $employee, EmployeeDocument $employeeDocument): View
    {
        $this->ensureRelationship($employee, $employeeDocument);
        Gate::authorize('update', $employeeDocument);

        return view('admin.documents.edit', [
            'employee' => $employee,
            'document' => $employeeDocument,
            'documentTypes' => EmployeeDocumentType::cases(),
        ]);
    }

    public function update(
        UpdateEmployeeDocumentRequest $request,
        Employee $employee,
        EmployeeDocument $employeeDocument,
    ): RedirectResponse {
        $oldPath = $employeeDocument->file_path;
        $metadata = [];

        if ($request->hasFile('document_file')) {
            $metadata = $this->privateFiles->store($request->file('document_file'), $employee, 'documents');
        }

        try {
            $employeeDocument->update([
                ...$request->safe()->only(['document_type', 'title', 'description', 'document_date']),
                ...$metadata,
                ...($metadata === [] ? [] : ['uploaded_by' => $request->user()->id]),
            ]);
        } catch (Throwable $exception) {
            if ($metadata !== []) {
                $this->privateFiles->delete($metadata['file_path'], $employee, 'documents');
            }

            throw $exception;
        }

        if ($metadata !== []) {
            $this->privateFiles->delete($oldPath, $employee, 'documents');
        }

        return redirect()->to(route('admin.employees.show', $employee).'#documents')
            ->with('status', 'Employee document updated.');
    }

    public function download(Employee $employee, EmployeeDocument $employeeDocument): StreamedResponse
    {
        $this->ensureRelationship($employee, $employeeDocument);
        Gate::authorize('download', $employeeDocument);

        return $this->privateFiles->download(
            $employeeDocument->file_path,
            $employeeDocument->original_filename,
            $employeeDocument->mime_type,
            $employee,
            'documents',
        );
    }

    public function destroy(Employee $employee, EmployeeDocument $employeeDocument): RedirectResponse
    {
        $this->ensureRelationship($employee, $employeeDocument);
        Gate::authorize('delete', $employeeDocument);
        $employeeDocument->delete();

        return redirect()->to(route('admin.employees.show', $employee).'#documents')
            ->with('status', 'Employee document archived. The private file has been retained.');
    }

    private function ensureRelationship(Employee $employee, EmployeeDocument $employeeDocument): void
    {
        abort_unless($employeeDocument->employee_id === $employee->id, 404);
    }
}
