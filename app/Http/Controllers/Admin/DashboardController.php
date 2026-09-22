<?php

namespace App\Http\Controllers\Admin;

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
        Gate::authorize('hr-administration');

        $statusCounts = Employee::query()
            ->toBase()
            ->selectRaw('employment_status, COUNT(*) as total')
            ->groupBy('employment_status')
            ->pluck('total', 'employment_status');

        $statusOverview = collect(EmploymentStatus::cases())->map(fn (EmploymentStatus $status): array => [
            'status' => $status,
            'count' => (int) $statusCounts->get($status->value, 0),
        ]);

        $metrics = [
            ['label' => 'Total Employees', 'count' => (int) $statusCounts->sum(), 'filter' => null],
            ['label' => 'Active Employees', 'count' => (int) $statusCounts->get(EmploymentStatus::Active->value, 0), 'filter' => EmploymentStatus::Active],
            ['label' => 'On Probation', 'count' => (int) $statusCounts->get(EmploymentStatus::OnProbation->value, 0), 'filter' => EmploymentStatus::OnProbation],
            ['label' => 'Notice Period', 'count' => (int) $statusCounts->get(EmploymentStatus::NoticePeriod->value, 0), 'filter' => EmploymentStatus::NoticePeriod],
        ];

        $departments = Department::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->whereHas('employees', fn (Builder $query) => $query->where('employment_status', EmploymentStatus::Active))
            ->withCount([
                'employees as active_employees_count' => fn (Builder $query) => $query->where('employment_status', EmploymentStatus::Active),
            ])
            ->orderByDesc('active_employees_count')
            ->orderBy('name')
            ->get();

        $recentEmployees = Employee::query()
            ->select(['id', 'employee_code', 'full_name', 'department_id', 'job_title_id', 'joining_date'])
            ->with(['department:id,name', 'jobTitle:id,name'])
            ->orderByDesc('joining_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $today = today();
        $leaveMetrics = [
            'pending' => LeaveRequest::query()
                ->where('status', LeaveRequestStatus::Pending)
                ->whereHas('employee', fn (Builder $query) => $query->whereNull('deleted_at'))
                ->count(),
            'on_leave_today' => LeaveRequest::query()
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->whereHas('employee', fn (Builder $query) => $query->whereNull('deleted_at'))
                ->distinct('employee_id')
                ->count('employee_id'),
        ];

        $attendanceCounts = AttendanceRecord::query()
            ->toBase()
            ->join('employees', 'employees.id', '=', 'attendance_records.employee_id')
            ->whereNull('employees.deleted_at')
            ->whereDate('attendance_records.attendance_date', $today)
            ->whereIn('attendance_records.status', [AttendanceStatus::Present->value, AttendanceStatus::Absent->value])
            ->selectRaw('attendance_records.status, COUNT(DISTINCT attendance_records.employee_id) as total')
            ->groupBy('attendance_records.status')
            ->pluck('total', 'status');

        $attendanceMetrics = [
            'present' => (int) $attendanceCounts->get(AttendanceStatus::Present->value, 0),
            'absent' => (int) $attendanceCounts->get(AttendanceStatus::Absent->value, 0),
        ];

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'statusOverview' => $statusOverview,
            'departments' => $departments,
            'recentEmployees' => $recentEmployees,
            'leaveMetrics' => $leaveMetrics,
            'today' => $today,
            'attendanceMetrics' => $attendanceMetrics,
        ]);
    }
}
