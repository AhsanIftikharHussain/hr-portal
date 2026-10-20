<?php

namespace App\Http\Controllers\Manager;

use App\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->user()->managedDepartmentId();

        if ($departmentId === null) {
            return view('manager.attendance.index', ['department' => null]);
        }

        $request->validate([
            'date' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where(
                fn ($query) => $query->where('department_id', $departmentId)->whereNull('deleted_at'),
            )],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
        ]);

        $selectedDate = $request->filled('date')
            ? CarbonImmutable::parse($request->string('date')->toString())
            : CarbonImmutable::today();

        $employees = Employee::query()
            ->where('department_id', $departmentId)
            ->with([
                'jobTitle:id,name',
                'attendanceRecords' => fn ($query) => $query->whereDate('attendance_date', $selectedDate),
            ])
            ->withExists([
                'leaveRequests as approved_leave_exists' => fn (Builder $query) => $query
                    ->where('status', LeaveRequestStatus::Approved)
                    ->whereDate('start_date', '<=', $selectedDate)
                    ->whereDate('end_date', '>=', $selectedDate),
            ])
            ->when($request->integer('employee_id'), fn (Builder $query, int $employeeId) => $query->whereKey($employeeId))
            ->when($request->filled('status'), fn (Builder $query) => $query->whereHas(
                'attendanceRecords',
                fn (Builder $attendanceQuery) => $attendanceQuery
                    ->whereDate('attendance_date', $selectedDate)
                    ->where('status', $request->string('status')),
            ))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('manager.attendance.index', [
            'department' => Department::query()->find($departmentId, ['id', 'name']),
            'employees' => $employees,
            'employeeOptions' => Employee::query()->where('department_id', $departmentId)->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'selectedDate' => $selectedDate,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
