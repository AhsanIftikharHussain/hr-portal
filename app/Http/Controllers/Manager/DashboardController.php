<?php

namespace App\Http\Controllers\Manager;

use App\AttendanceStatus;
use App\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('access-manager');

        $departmentId = request()->user()->managedDepartmentId();
        $department = $departmentId === null ? null : Department::query()->find($departmentId, ['id', 'name']);

        if ($department === null) {
            return view('manager.dashboard', ['department' => null]);
        }

        $today = today();
        $employeeQuery = Employee::query()->where('department_id', $department->id);

        $attendanceCounts = AttendanceRecord::query()
            ->toBase()
            ->join('employees', 'employees.id', '=', 'attendance_records.employee_id')
            ->whereNull('employees.deleted_at')
            ->where('employees.department_id', $department->id)
            ->whereDate('attendance_records.attendance_date', $today)
            ->whereIn('attendance_records.status', [AttendanceStatus::Present->value, AttendanceStatus::Absent->value])
            ->selectRaw('attendance_records.status, COUNT(DISTINCT attendance_records.employee_id) as total')
            ->groupBy('attendance_records.status')
            ->pluck('total', 'status');

        $metrics = [
            'total_employees' => (clone $employeeQuery)->count(),
            'present' => (int) $attendanceCounts->get(AttendanceStatus::Present->value, 0),
            'absent' => (int) $attendanceCounts->get(AttendanceStatus::Absent->value, 0),
            'not_recorded' => (clone $employeeQuery)
                ->where('employment_status', EmploymentStatus::Active)
                ->whereDoesntHave('attendanceRecords', fn (Builder $query) => $query->whereDate('attendance_date', $today))
                ->count(),
            'pending_leave' => LeaveRequest::query()
                ->where('status', LeaveRequestStatus::Pending)
                ->whereHas('employee', fn (Builder $query) => $query->where('department_id', $department->id)->whereNull('deleted_at'))
                ->count(),
            'on_leave_today' => LeaveRequest::query()
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->whereHas('employee', fn (Builder $query) => $query->where('department_id', $department->id)->whereNull('deleted_at'))
                ->distinct('employee_id')
                ->count('employee_id'),
        ];

        $pendingLeaveRequests = LeaveRequest::query()
            ->with(['employee:id,employee_code,full_name,department_id', 'leaveType:id,name'])
            ->where('status', LeaveRequestStatus::Pending)
            ->whereHas('employee', fn (Builder $query) => $query->where('department_id', $department->id)->whereNull('deleted_at'))
            ->orderBy('requested_at')
            ->limit(5)
            ->get();

        $probationEndingSoon = (clone $employeeQuery)
            ->with('jobTitle:id,name')
            ->where('employment_status', EmploymentStatus::OnProbation)
            ->whereBetween('confirmation_date', [$today, $today->copy()->addDays(30)])
            ->orderBy('confirmation_date')
            ->limit(5)
            ->get();

        return view('manager.dashboard', compact('department', 'metrics', 'pendingLeaveRequests', 'probationEndingSoon', 'today'));
    }
}
