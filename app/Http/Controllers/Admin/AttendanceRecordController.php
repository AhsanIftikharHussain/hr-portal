<?php

namespace App\Http\Controllers\Admin;

use App\AttendanceSource;
use App\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Http\Requests\UpdateAttendanceRecordRequest;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttendanceRecordController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AttendanceRecord::class);
        $request->validate([
            'date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $selectedDate = $request->filled('date')
            ? CarbonImmutable::parse($request->string('date')->toString())
            : CarbonImmutable::today();

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'jobTitle:id,name',
                'attendanceRecords' => fn ($query) => $query
                    ->whereDate('attendance_date', $selectedDate),
            ])
            ->withExists([
                'leaveRequests as approved_leave_exists' => fn (Builder $query) => $query
                    ->where('status', LeaveRequestStatus::Approved)
                    ->whereDate('start_date', '<=', $selectedDate)
                    ->whereDate('end_date', '>=', $selectedDate),
            ])
            ->when($request->integer('department_id'), fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->filled('status'), fn (Builder $query) => $query->whereHas(
                'attendanceRecords',
                fn (Builder $attendanceQuery) => $attendanceQuery
                    ->whereDate('attendance_date', $selectedDate)
                    ->where('status', $request->string('status')),
            ))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->where(function (Builder $employeeQuery) use ($search): void {
                    $employeeQuery->where('full_name', 'like', $search)
                        ->orWhere('employee_code', 'like', $search);
                });
            })
            ->orderBy('full_name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendance.index', [
            'employees' => $employees,
            'selectedDate' => $selectedDate,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', AttendanceRecord::class);
        $request->validate([
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'date' => ['nullable', 'date'],
        ]);

        return view('admin.attendance.create', [
            'attendanceRecord' => new AttendanceRecord([
                'employee_id' => $request->integer('employee_id') ?: null,
                'attendance_date' => $request->date('date') ?? today(),
            ]),
            'employees' => Employee::query()->orderBy('full_name')->get(['id', 'employee_code', 'full_name']),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function store(StoreAttendanceRecordRequest $request): RedirectResponse
    {
        try {
            $attendanceRecord = AttendanceRecord::query()->create([
                ...$request->safe()->only(['employee_id', 'attendance_date', 'status', 'check_in_at', 'check_out_at', 'notes']),
                'source' => AttendanceSource::HrManual,
                'recorded_by' => $request->user()->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'attendance_date' => 'An attendance record already exists for this employee and date.',
            ]);
        }

        return redirect()->route('admin.attendance.index', ['date' => $attendanceRecord->attendance_date->toDateString()])
            ->with('status', 'Attendance record created.');
    }

    public function edit(AttendanceRecord $attendanceRecord): View
    {
        Gate::authorize('update', $attendanceRecord);
        $attendanceRecord->load(['employee', 'recorder', 'corrector']);

        return view('admin.attendance.edit', [
            'attendanceRecord' => $attendanceRecord,
            'employees' => Employee::query()
                ->withTrashed()
                ->where(function (Builder $query) use ($attendanceRecord): void {
                    $query->whereNull('deleted_at')->orWhereKey($attendanceRecord->employee_id);
                })
                ->orderBy('full_name')
                ->get(['id', 'employee_code', 'full_name', 'deleted_at']),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        try {
            $attendanceRecord->update([
                ...$request->safe()->only(['employee_id', 'attendance_date', 'status', 'check_in_at', 'check_out_at', 'notes']),
                'original_source' => $attendanceRecord->original_source ?? $attendanceRecord->source,
                'source' => AttendanceSource::HrCorrection,
                'corrected_by' => $request->user()->id,
                'corrected_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'attendance_date' => 'An attendance record already exists for this employee and date.',
            ]);
        }

        return redirect()->route('admin.attendance.index', ['date' => $attendanceRecord->attendance_date->toDateString()])
            ->with('status', 'Attendance record corrected.');
    }
}
