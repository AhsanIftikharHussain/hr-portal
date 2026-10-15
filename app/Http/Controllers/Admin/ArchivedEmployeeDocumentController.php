<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ArchivedEmployeeDocumentController extends Controller
{
    public function destroy(Employee $employee, EmployeeDocument $employeeDocument): RedirectResponse
    {
        abort_unless($employeeDocument->employee_id === $employee->id, 404);
        Gate::authorize('restore', $employeeDocument);
        $employeeDocument->restore();

        return redirect()->to(route('admin.employees.show', $employee).'#documents')
            ->with('status', 'Employee document restored successfully.');
    }
}
