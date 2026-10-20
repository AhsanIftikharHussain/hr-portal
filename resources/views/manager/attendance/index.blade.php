<x-layouts.admin title="Department Attendance">
    @if ($department === null)
        <x-page-header title="Department Attendance" description="Department access is not configured for this manager account." />
        <x-alert type="error" class="mt-6">Please ask an HR administrator to link your account to the correct employee record.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header title="Department Attendance" :description="$department->name.' · Read-only attendance for '.$selectedDate->format('d F Y')" />
            <x-card>
                <form method="GET" action="{{ route('manager.attendance.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-form-input label="Attendance Date" name="date" type="date" :value="$selectedDate->toDateString()" />
                    <x-form-select label="Employee" name="employee_id" :value="request('employee_id')" :options="$employeeOptions->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' · '.$employee->employee_code])" placeholder="All department employees" />
                    <x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All recorded statuses" />
                    <div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('manager.attendance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Today</a></div>
                </form>
            </x-card>
            <x-card class="overflow-hidden p-0">
                @if ($employees->isEmpty())
                    <x-empty-state title="No employees found" description="No department employees match these filters." class="m-5" />
                @else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Designation</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Check In</th><th class="px-5 py-3">Check Out</th><th class="px-5 py-3">Hours</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">@foreach ($employees as $employee) @php($record = $employee->attendanceRecords->first())
                            <tr><td class="px-5 py-4"><span class="font-semibold">{{ $employee->full_name }}</span><span class="block font-mono text-xs text-slate-500">{{ $employee->employee_code }}</span></td><td class="px-5 py-4 text-slate-600">{{ $employee->jobTitle->name }}</td><td class="px-5 py-4">@if ($record)<x-attendance-status-badge :status="$record->status" />@else<span class="text-slate-500">Not recorded</span>@endif @if ($employee->approved_leave_exists)<span class="block text-xs text-blue-700">Approved leave</span>@endif</td><td class="px-5 py-4 text-slate-600">{{ $record?->check_in_at?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $record?->check_out_at?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $record?->workingDurationMinutes() !== null ? number_format($record->workingDurationMinutes() / 60, 1) : '—' }}</td></tr>
                        @endforeach</tbody>
                    </table></div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $employees->links() }}</div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.admin>
