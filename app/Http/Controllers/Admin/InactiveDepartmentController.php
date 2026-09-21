<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InactiveDepartmentController extends Controller
{
    public function store(Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);
        $department->update(['is_active' => false]);

        return back()->with('status', 'Department deactivated. Existing employee assignments were preserved.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);
        $department->update(['is_active' => true]);

        return back()->with('status', 'Department reactivated.');
    }
}
