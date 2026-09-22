<?php

namespace App\Http\Controllers\Admin;

use App\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MonthlyAttendanceController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', AttendanceRecord::class);
        $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        $month = $request->integer('month') ?: (int) now()->format('n');
        $year = $request->integer('year') ?: (int) now()->format('Y');
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        $summaries = AttendanceRecord::query()
            ->select('employee_id')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as present_count', [AttendanceStatus::Present->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absent_count', [AttendanceStatus::Absent->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as leave_count', [AttendanceStatus::Leave->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as half_day_count', [AttendanceStatus::HalfDay->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_count', [AttendanceStatus::Late->value])
            ->selectRaw('COUNT(*) as recorded_days_count')
            ->with(['employee:id,employee_code,full_name,department_id,deleted_at', 'employee.department:id,name'])
            ->whereDate('attendance_date', '>=', $periodStart)
            ->whereDate('attendance_date', '<=', $periodEnd)
            ->when($request->integer('department_id'), fn (Builder $query, int $departmentId) => $query
                ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('department_id', $departmentId)))
            ->groupBy('employee_id')
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendance.monthly', [
            'summaries' => $summaries,
            'month' => $month,
            'year' => $year,
            'periodStart' => $periodStart,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
