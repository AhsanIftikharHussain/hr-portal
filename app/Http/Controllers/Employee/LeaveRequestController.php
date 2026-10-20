<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeLeaveRequestRequest;
use App\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\LeaveDurationCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee()->first();

        if ($employee === null) {
            return view('employee.leave-requests.index', ['employee' => null]);
        }

        $request->validate([
            'status' => ['nullable', Rule::enum(LeaveRequestStatus::class)],
            'leave_type_id' => ['nullable', 'integer', Rule::exists('leave_types', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $leaveRequests = $employee->leaveRequests()
            ->with('leaveType:id,name')
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->integer('leave_type_id'), fn (Builder $query, int $leaveTypeId) => $query->where('leave_type_id', $leaveTypeId))
            ->when($request->date('date_from'), fn (Builder $query, $dateFrom) => $query->whereDate('end_date', '>=', $dateFrom))
            ->when($request->date('date_to'), fn (Builder $query, $dateTo) => $query->whereDate('start_date', '<=', $dateTo))
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('employee.leave-requests.index', [
            'employee' => $employee,
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => LeaveRequestStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee()->first();

        return view('employee.leave-requests.create', [
            'employee' => $employee,
            'leaveTypes' => $employee === null
                ? collect()
                : LeaveType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'requires_attachment']),
        ]);
    }

    public function store(StoreEmployeeLeaveRequestRequest $request, LeaveDurationCalculator $durationCalculator): RedirectResponse
    {
        $employee = $request->user()->employee()->firstOrFail();
        $startDate = CarbonImmutable::parse($request->string('start_date')->toString());
        $endDate = CarbonImmutable::parse($request->string('end_date')->toString());

        $employee->leaveRequests()->create([
            ...$request->safe()->only(['leave_type_id', 'start_date', 'end_date', 'reason']),
            'duration_days' => $durationCalculator->calendarDays($startDate, $endDate),
            'status' => LeaveRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        return redirect()->route('employee.leave-requests.index')
            ->with('success', 'Leave request submitted successfully.');
    }

    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        $employee = $request->user()->employee()->firstOrFail();
        $ownLeaveRequest = $employee->leaveRequests()
            ->with(['leaveType:id,name,is_paid', 'reviewer:id,name'])
            ->whereKey($leaveRequest->id)
            ->firstOrFail();

        return view('employee.leave-requests.show', ['leaveRequest' => $ownLeaveRequest]);
    }
}
