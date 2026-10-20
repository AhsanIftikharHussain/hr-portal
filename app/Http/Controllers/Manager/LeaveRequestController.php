<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->user()->managedDepartmentId();

        if ($departmentId === null) {
            return view('manager.leave-requests.index', ['department' => null]);
        }

        $request->validate([
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where(
                fn ($query) => $query->where('department_id', $departmentId)->whereNull('deleted_at'),
            )],
            'status' => ['nullable', Rule::enum(LeaveRequestStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $leaveRequests = LeaveRequest::query()
            ->with(['employee:id,employee_code,full_name,department_id,deleted_at', 'leaveType:id,name'])
            ->whereHas('employee', fn (Builder $query) => $query->where('department_id', $departmentId)->whereNull('deleted_at'))
            ->when($request->integer('employee_id'), fn (Builder $query, int $employeeId) => $query->where('employee_id', $employeeId))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->date('date_from'), fn (Builder $query, $dateFrom) => $query->whereDate('end_date', '>=', $dateFrom))
            ->when($request->date('date_to'), fn (Builder $query, $dateTo) => $query->whereDate('start_date', '<=', $dateTo))
            ->orderByDesc('requested_at')
            ->paginate(15)
            ->withQueryString();

        return view('manager.leave-requests.index', [
            'department' => Department::query()->find($departmentId, ['id', 'name']),
            'leaveRequests' => $leaveRequests,
            'employees' => Employee::query()->where('department_id', $departmentId)->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'statuses' => LeaveRequestStatus::cases(),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        Gate::authorize('view', $leaveRequest);
        $leaveRequest->load(['employee.department', 'employee.jobTitle', 'leaveType', 'reviewer']);

        return view('manager.leave-requests.show', ['leaveRequest' => $leaveRequest]);
    }
}
