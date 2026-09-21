<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ArchivedEmployeeController extends Controller
{
    public function store(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);
        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('status', 'Employee archived. The record remains available for retention and review.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('restore', $employee);
        $employee->restore();

        return redirect()->route('admin.employees.show', $employee)
            ->with('status', 'Employee restored successfully.');
    }
}
