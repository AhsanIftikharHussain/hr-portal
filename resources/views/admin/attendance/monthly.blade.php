<x-layouts.admin title="Monthly Attendance">
    <div class="flex flex-col gap-6">
        <x-page-header title="Monthly Attendance" :description="'Summary of explicit attendance records for '.$periodStart->format('F Y').'.'">
            <x-slot:actions><a href="{{ route('admin.attendance.index', ['date' => $periodStart->toDateString()]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Daily Attendance</a></x-slot:actions>
        </x-page-header>

        <x-alert>Counts include explicit records only. Missing dates are not inferred as absent because work schedules, weekends, and holidays are not configured.</x-alert>

        <x-card><form method="GET" action="{{ route('admin.attendance.monthly') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><x-form-select label="Month" name="month" :value="$month" :options="collect(range(1, 12))->mapWithKeys(fn ($value) => [$value => now()->startOfYear()->addMonths($value - 1)->format('F')])" /><x-form-input label="Year" name="year" type="number" :value="$year" min="2000" max="2100" /><x-form-select label="Department" name="department_id" :value="request('department_id')" :options="$departments->pluck('name', 'id')" placeholder="All departments" /><div class="flex items-end"><x-button>View Summary</x-button></div></form></x-card>

        <x-card class="overflow-hidden p-0">
            @if ($summaries->isEmpty())
                <x-empty-state title="No recorded attendance" description="No explicit attendance records match this month and department." class="m-5" />
            @else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Department</th><th class="px-5 py-3 text-center">Recorded</th><th class="px-5 py-3 text-center">Present</th><th class="px-5 py-3 text-center">Absent</th><th class="px-5 py-3 text-center">Leave</th><th class="px-5 py-3 text-center">Half Day</th><th class="px-5 py-3 text-center">Late</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($summaries as $summary)<tr><td class="px-5 py-4"><a href="{{ route('admin.employees.show', ['employee' => $summary->employee, 'attendance_month' => $month, 'attendance_year' => $year]) }}" class="font-semibold text-slate-950 hover:underline">{{ $summary->employee->full_name }}</a><span class="mt-1 block font-mono text-xs text-slate-500">{{ $summary->employee->employee_code }}{{ $summary->employee->trashed() ? ' · Archived' : '' }}</span></td><td class="px-5 py-4 text-slate-600">{{ $summary->employee->department->name }}</td><td class="px-5 py-4 text-center font-semibold text-slate-900">{{ $summary->recorded_days_count }}</td><td class="px-5 py-4 text-center text-slate-700">{{ $summary->present_count }}</td><td class="px-5 py-4 text-center text-slate-700">{{ $summary->absent_count }}</td><td class="px-5 py-4 text-center text-slate-700">{{ $summary->leave_count }}</td><td class="px-5 py-4 text-center text-slate-700">{{ $summary->half_day_count }}</td><td class="px-5 py-4 text-center text-slate-700">{{ $summary->late_count }}</td></tr>@endforeach</tbody></table></div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $summaries->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
