<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\LeaveDurationCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'leave_type_id' => ['nullable', 'integer', Rule::exists('leave_types', 'id')],
            'status' => ['nullable', Rule::enum(LeaveRequestStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $leaveRequests = LeaveRequest::query()
            ->with(['employee:id,employee_code,full_name,department_id,deleted_at', 'employee.department:id,name', 'leaveType:id,name'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery
                    ->where('full_name', 'like', $search)
                    ->orWhere('employee_code', 'like', $search));
            })
            ->when($request->integer('employee_id'), fn (Builder $query, int $employeeId) => $query->where('employee_id', $employeeId))
            ->when($request->integer('department_id'), fn (Builder $query, int $departmentId) => $query
                ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('department_id', $departmentId)))
            ->when($request->integer('leave_type_id'), fn (Builder $query, int $leaveTypeId) => $query->where('leave_type_id', $leaveTypeId))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->date('date_from'), fn (Builder $query, $dateFrom) => $query->whereDate('end_date', '>=', $dateFrom))
            ->when($request->date('date_to'), fn (Builder $query, $dateTo) => $query->whereDate('start_date', '<=', $dateTo))
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.leave-requests.index', [
            'leaveRequests' => $leaveRequests,
            'employees' => Employee::query()->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => LeaveRequestStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LeaveRequest::class);

        return view('admin.leave-requests.create', [
            'employees' => Employee::query()->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'requires_attachment']),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request, LeaveDurationCalculator $durationCalculator): RedirectResponse
    {
        $startDate = CarbonImmutable::parse($request->string('start_date')->toString());
        $endDate = CarbonImmutable::parse($request->string('end_date')->toString());

        $leaveRequest = LeaveRequest::query()->create([
            ...$request->safe()->only(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason']),
            'duration_days' => $durationCalculator->calendarDays($startDate, $endDate),
            'status' => LeaveRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        return redirect()->route('admin.leave-requests.show', $leaveRequest)
            ->with('status', 'Leave request created successfully.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        Gate::authorize('view', $leaveRequest);
        $leaveRequest->load(['employee.department', 'employee.jobTitle', 'leaveType', 'reviewer']);

        return view('admin.leave-requests.show', ['leaveRequest' => $leaveRequest]);
    }
}
