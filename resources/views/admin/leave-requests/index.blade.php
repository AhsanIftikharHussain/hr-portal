<x-layouts.admin title="Leave Management">
    <div class="flex flex-col gap-6">
        <x-page-header title="Leave Management" description="Review employee leave requests and their current workflow status.">
            <x-slot:actions><a href="{{ route('admin.leave-requests.create') }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Add Leave Request</a></x-slot:actions>
        </x-page-header>

        <x-card>
            <form method="GET" action="{{ route('admin.leave-requests.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-form-input label="Search employee" name="search" :value="request('search')" placeholder="Name or employee code" />
                <x-form-select label="Employee" name="employee_id" :value="request('employee_id')" :options="$employees->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' · '.$employee->employee_code])" placeholder="All employees" />
                <x-form-select label="Department" name="department_id" :value="request('department_id')" :options="$departments->pluck('name', 'id')" placeholder="All departments" />
                <x-form-select label="Leave type" name="leave_type_id" :value="request('leave_type_id')" :options="$leaveTypes->pluck('name', 'id')" placeholder="All leave types" />
                <x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" />
                <x-form-input label="Overlaps from" name="date_from" type="date" :value="request('date_from')" />
                <x-form-input label="Overlaps through" name="date_to" type="date" :value="request('date_to')" />
                <div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('admin.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a></div>
            </form>
        </x-card>

        <x-card class="overflow-hidden p-0">
            @if ($leaveRequests->isEmpty())
                <x-empty-state title="No leave requests found" description="Create a leave request or adjust the current filters." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Leave Type</th><th class="px-5 py-3">Start Date</th><th class="px-5 py-3">End Date</th><th class="px-5 py-3">Duration</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Requested</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($leaveRequests as $leaveRequest)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4"><a href="{{ route('admin.employees.show', $leaveRequest->employee) }}" class="font-semibold text-slate-950 hover:underline">{{ $leaveRequest->employee->full_name }}</a><span class="mt-1 block font-mono text-xs text-slate-500">{{ $leaveRequest->employee->employee_code }} · {{ $leaveRequest->employee->department->name }}</span></td>
                                    <td class="px-5 py-4 text-slate-700">{{ $leaveRequest->leaveType->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->start_date->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->end_date->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->duration_days }} {{ Str::plural('day', $leaveRequest->duration_days) }}</td>
                                    <td class="px-5 py-4"><x-leave-status-badge :status="$leaveRequest->status" /></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->requested_at->format('d M Y') }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('admin.leave-requests.show', $leaveRequest) }}" class="font-semibold text-portal-900 hover:underline">Review</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $leaveRequests->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
