<x-layouts.admin title="My Attendance">
    @if ($employee === null)
        <x-page-header title="My Attendance" description="Your employee record is not available." />
        <x-alert type="error" class="mt-6">Your account has not yet been linked to an employee record. Please contact HR.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header title="My Attendance" description="Your recorded attendance history. This page is read-only." />
            <x-card><form method="GET" action="{{ route('employee.attendance.index') }}" class="grid gap-4 md:grid-cols-3"><x-form-input label="Month" name="month" type="month" :value="$month" /><x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" /><div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('employee.attendance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Current Month</a></div></form></x-card>
            <x-card class="overflow-hidden p-0">
                @if ($attendanceRecords->isEmpty())
                    <x-empty-state title="No attendance records" description="No attendance records match the selected month and status." class="m-5" />
                @else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Check In</th><th class="px-5 py-3">Check Out</th><th class="px-5 py-3">Working Hours</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($attendanceRecords as $record)<tr><td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $record->attendance_date->format('D, d M Y') }}</td><td class="px-5 py-4"><x-attendance-status-badge :status="$record->status" /></td><td class="px-5 py-4 text-slate-600">{{ $record->check_in_at?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $record->check_out_at?->format('H:i') ?? '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $record->workingDurationMinutes() !== null ? number_format($record->workingDurationMinutes() / 60, 1) : '—' }}</td></tr>@endforeach</tbody></table></div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $attendanceRecords->links() }}</div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.admin>
