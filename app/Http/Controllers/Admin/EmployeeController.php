<?php

namespace App\Http\Controllers\Admin;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\LeaveRequestStatus;
use App\MaritalStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Support\EmployeeCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['department:id,name', 'jobTitle:id,name'])
            ->when($request->string('record_status')->toString() === 'all', fn (Builder $query) => $query->withTrashed())
            ->when($request->string('record_status')->toString() === 'archived', fn (Builder $query) => $query->onlyTrashed())
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('employee_code', 'like', $search)
                        ->orWhere('full_name', 'like', $search)
                        ->orWhere('official_email', 'like', $search)
                        ->orWhere('personal_email', 'like', $search);
                });
            })
            ->when($request->integer('department_id'), fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->filled('employment_status'), fn (Builder $query) => $query->where('employment_status', $request->string('employment_status')))
            ->when($request->filled('employment_type'), fn (Builder $query) => $query->where('employment_type', $request->string('employment_type')))
            ->orderBy('full_name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'employmentStatuses' => EmploymentStatus::cases(),
            'employmentTypes' => EmploymentType::cases(),
        ]);
    }

    public function create(EmployeeCodeGenerator $employeeCodeGenerator): View
    {
        Gate::authorize('create', Employee::class);

        return view('admin.employees.create', $this->formData() + [
            'employee' => new Employee(['employee_code' => $employeeCodeGenerator->next()]),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->create($request->validated());

        return redirect()->route('admin.employees.show', $employee)
            ->with('status', 'Employee created successfully.');
    }

    public function show(Request $request, Employee $employee): View
    {
        Gate::authorize('view', $employee);
        $request->validate([
            'attendance_month' => ['nullable', 'integer', 'between:1,12'],
            'attendance_year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $employee->load(['department', 'jobTitle', 'reportingManager', 'user']);

        $leaveRequests = $employee->leaveRequests()
            ->with('leaveType:id,name')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $approvedLeaveDays = $employee->leaveRequests()
            ->where('status', LeaveRequestStatus::Approved)
            ->sum('duration_days');

        $attendanceRecords = $employee->attendanceRecords()
            ->when($request->integer('attendance_month'), fn (Builder $query, int $month) => $query->whereMonth('attendance_date', $month))
            ->when($request->integer('attendance_year'), fn (Builder $query, int $year) => $query->whereYear('attendance_date', $year))
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'attendance_page')
            ->withQueryString();

        return view('admin.employees.show', [
            'employee' => $employee,
            'leaveRequests' => $leaveRequests,
            'approvedLeaveDays' => $approvedLeaveDays,
            'attendanceRecords' => $attendanceRecords,
            'attendanceMonths' => collect(range(1, 12))->mapWithKeys(fn (int $month): array => [
                $month => now()->startOfYear()->addMonths($month - 1)->format('F'),
            ]),
        ]);
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('admin.employees.edit', $this->formData($employee) + ['employee' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('admin.employees.show', $employee)
            ->with('status', 'Employee updated successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(?Employee $employee = null): array
    {
        return [
            'departments' => Department::query()
                ->where('is_active', true)
                ->when($employee, fn (Builder $query) => $query->orWhereKey($employee->department_id))
                ->orderBy('name')->get(['id', 'name', 'is_active']),
            'jobTitles' => JobTitle::query()
                ->where('is_active', true)
                ->when($employee, fn (Builder $query) => $query->orWhereKey($employee->job_title_id))
                ->orderBy('name')->get(['id', 'name', 'is_active']),
            'managers' => Employee::query()
                ->when($employee, fn (Builder $query) => $query->whereKeyNot($employee->id))
                ->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'employmentTypes' => EmploymentType::cases(),
            'employmentStatuses' => EmploymentStatus::cases(),
            'genders' => Gender::cases(),
            'maritalStatuses' => MaritalStatus::cases(),
        ];
    }
}
