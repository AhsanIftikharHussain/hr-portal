<?php

namespace App\Http\Controllers\Admin;

use App\AttendanceStatus;
use App\ContractStatus;
use App\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Holiday;
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

        $pendingLeaveRequests = LeaveRequest::query()
            ->select(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'duration_days', 'requested_at'])
            ->with(['employee:id,full_name,deleted_at', 'leaveType:id,name'])
            ->where('status', LeaveRequestStatus::Pending)
            ->whereHas('employee', fn (Builder $query) => $query->whereNull('deleted_at'))
            ->orderBy('requested_at')
            ->orderBy('id')
            ->limit(5)
            ->get();

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
            'not_recorded' => Employee::query()
                ->where('employment_status', EmploymentStatus::Active)
                ->whereDoesntHave(
                    'attendanceRecords',
                    fn (Builder $query) => $query->whereDate('attendance_date', $today),
                )
                ->count(),
        ];

        $currentEmploymentStatuses = [
            EmploymentStatus::Active,
            EmploymentStatus::OnProbation,
            EmploymentStatus::NoticePeriod,
        ];

        $birthdaysThisMonth = Employee::query()
            ->select(['id', 'full_name', 'department_id', 'date_of_birth'])
            ->with('department:id,name')
            ->whereIn('employment_status', $currentEmploymentStatuses)
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $today->month)
            ->get()
            ->sortBy(fn (Employee $employee): string => $employee->date_of_birth->format('d').'-'.$employee->full_name)
            ->values();

        $workAnniversariesThisMonth = Employee::query()
            ->select(['id', 'full_name', 'department_id', 'joining_date'])
            ->with('department:id,name')
            ->whereIn('employment_status', $currentEmploymentStatuses)
            ->whereMonth('joining_date', $today->month)
            ->whereYear('joining_date', '<', $today->year)
            ->get()
            ->sortBy(fn (Employee $employee): string => $employee->joining_date->format('d').'-'.$employee->full_name)
            ->values()
            ->map(fn (Employee $employee): array => [
                'employee' => $employee,
                'completedYears' => $today->year - $employee->joining_date->year,
            ]);

        $probationEndingSoon = Employee::query()
            ->select(['id', 'full_name', 'department_id', 'job_title_id', 'confirmation_date'])
            ->with(['department:id,name', 'jobTitle:id,name'])
            ->where('employment_status', EmploymentStatus::OnProbation)
            ->whereBetween('confirmation_date', [$today, $today->copy()->addDays(30)])
            ->orderBy('confirmation_date')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn (Employee $employee): array => [
                'employee' => $employee,
                'daysRemaining' => (int) $today->diffInDays($employee->confirmation_date),
            ]);

        $contractsExpiringSoon = EmployeeContract::query()
            ->whereIn('status', ContractStatus::expiringStatuses())
            ->whereBetween('end_date', [$today, $today->copy()->addDays(30)])
            ->whereHas('employee', fn (Builder $query) => $query->whereNull('employees.deleted_at'))
            ->count();

        $upcomingHolidays = Holiday::query()
            ->where('is_active', true)
            ->whereDate('holiday_date', '>=', $today)
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->limit(3)
            ->get(['id', 'name', 'holiday_date', 'type', 'day_portion']);

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'statusOverview' => $statusOverview,
            'departments' => $departments,
            'recentEmployees' => $recentEmployees,
            'leaveMetrics' => $leaveMetrics,
            'pendingLeaveRequests' => $pendingLeaveRequests,
            'today' => $today,
            'attendanceMetrics' => $attendanceMetrics,
            'birthdaysThisMonth' => $birthdaysThisMonth,
            'workAnniversariesThisMonth' => $workAnniversariesThisMonth,
            'probationEndingSoon' => $probationEndingSoon,
            'contractsExpiringSoon' => $contractsExpiringSoon,
            'upcomingHolidays' => $upcomingHolidays,
        ]);
    }
}
