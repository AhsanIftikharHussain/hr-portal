<x-layouts.admin title="Attendance">
    <div class="flex flex-col gap-6">
        <x-page-header title="Daily Attendance" :description="'Explicit attendance records for '.$selectedDate->format('d F Y').'. Missing records are not treated as absent.'">
            <x-slot:actions><a href="{{ route('admin.attendance.monthly', ['month' => $selectedDate->month, 'year' => $selectedDate->year]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Monthly Summary</a><a href="{{ route('admin.attendance.create', ['date' => $selectedDate->toDateString()]) }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Record Attendance</a></x-slot:actions>
        </x-page-header>

        <x-card>
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-form-input label="Attendance Date" name="date" type="date" :value="$selectedDate->toDateString()" required />
                <x-form-input label="Search employee" name="search" :value="request('search')" placeholder="Name or employee code" />
                <x-form-select label="Department" name="department_id" :value="request('department_id')" :options="$departments->pluck('name', 'id')" placeholder="All departments" />
                <x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All recorded statuses" />
                <div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('admin.attendance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Today</a></div>
            </form>
        </x-card>

        <x-card class="overflow-hidden p-0">
            @if ($employees->isEmpty())
                <x-empty-state title="No employees found" description="No current employees match the selected attendance filters." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Designation</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Check In</th><th class="px-5 py-3">Check Out</th><th class="px-5 py-3">Source</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($employees as $employee)
                                @php($attendanceRecord = $employee->attendanceRecords->first())
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4"><a href="{{ route('admin.employees.show', $employee) }}" class="font-semibold text-slate-950 hover:underline">{{ $employee->full_name }}</a><span class="mt-1 block font-mono text-xs text-slate-500">{{ $employee->employee_code }}</span></td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->department->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->jobTitle->name }}</td>
                                    <td class="px-5 py-4">@if ($attendanceRecord)<x-attendance-status-badge :status="$attendanceRecord->status" />@else<span class="text-sm font-medium text-slate-500">Not recorded</span>@endif @if ($employee->approved_leave_exists)<span class="mt-1 block text-xs font-medium text-blue-700">Approved leave covers this date</span>@endif</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendanceRecord?->check_in_at?->format('H:i') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attendanceRecord?->check_out_at?->format('H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-slate-600">@if ($attendanceRecord){{ $attendanceRecord->source->label() }}@if ($attendanceRecord->original_source)<span class="block text-xs text-slate-400">Originally {{ $attendanceRecord->original_source->label() }}</span>@endif @else—@endif</td>
                                    <td class="px-5 py-4 text-right">@if ($attendanceRecord)<a href="{{ route('admin.attendance.edit', $attendanceRecord) }}" class="font-semibold text-portal-900 hover:underline">Correct</a>@else<a href="{{ route('admin.attendance.create', ['employee_id' => $employee->id, 'date' => $selectedDate->toDateString()]) }}" class="font-semibold text-portal-900 hover:underline">Record</a>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $employees->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
