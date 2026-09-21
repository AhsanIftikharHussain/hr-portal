<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Department::class);

        return view('admin.departments.index', [
            'departments' => Department::query()->withCount('employees')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('admin.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $name = $request->string('name')->trim()->toString();
        Department::query()->create(['name' => $name, 'is_active' => true]);

        return redirect()->route('admin.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        Gate::authorize('update', $department);

        return view('admin.departments.edit', ['department' => $department]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $name = $request->string('name')->trim()->toString();
        $department->update(['name' => $name]);

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }
}
