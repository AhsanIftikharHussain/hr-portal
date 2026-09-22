<?php

namespace App\Http\Controllers\Admin;

use App\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
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

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'statusOverview' => $statusOverview,
            'departments' => $departments,
            'recentEmployees' => $recentEmployees,
        ]);
    }
}
