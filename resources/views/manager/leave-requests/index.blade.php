<x-layouts.admin title="Department Leave">
    @if ($department === null)
        <x-page-header title="Department Leave" description="Department access is not configured for this manager account." />
        <x-alert type="error" class="mt-6">Please ask an HR administrator to link your account to the correct employee record.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header title="Department Leave" :description="$department->name.' leave requests'" />
            <x-card><form method="GET" action="{{ route('manager.leave-requests.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-form-select label="Employee" name="employee_id" :value="request('employee_id')" :options="$employees->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' · '.$employee->employee_code])" placeholder="All department employees" />
                <x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" />
                <x-form-input label="From" name="date_from" type="date" :value="request('date_from')" />
                <x-form-input label="To" name="date_to" type="date" :value="request('date_to')" />
                <div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('manager.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear</a></div>
            </form></x-card>
            <x-card class="overflow-hidden p-0">
                @if ($leaveRequests->isEmpty())<x-empty-state title="No leave requests found" description="No department requests match these filters." class="m-5" />@else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Leave Type</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Duration</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach ($leaveRequests as $leaveRequest)<tr><td class="px-5 py-4"><span class="font-semibold">{{ $leaveRequest->employee->full_name }}</span><span class="block font-mono text-xs text-slate-500">{{ $leaveRequest->employee->employee_code }}</span></td><td class="px-5 py-4">{{ $leaveRequest->leaveType->name }}</td><td class="px-5 py-4">{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td><td class="px-5 py-4">{{ $leaveRequest->duration_days }} days</td><td class="px-5 py-4"><x-leave-status-badge :status="$leaveRequest->status" /></td><td class="px-5 py-4 text-right"><a class="font-semibold text-portal-900 hover:underline" href="{{ route('manager.leave-requests.show', $leaveRequest) }}">Review</a></td></tr>@endforeach
                    </tbody></table></div><div class="border-t border-slate-200 px-5 py-4">{{ $leaveRequests->links() }}</div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.admin>
