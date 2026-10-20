<x-layouts.admin title="My Leave">
    @if ($employee === null)
        <x-page-header title="My Leave" description="Your employee record is not available." />
        <x-alert type="error" class="mt-6">Your account has not yet been linked to an employee record. Please contact HR.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header title="My Leave" description="View and submit your personal leave requests.">
                <x-slot:actions><a href="{{ route('employee.leave-requests.create') }}" class="rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white">Request Leave</a></x-slot:actions>
            </x-page-header>
            <x-card><form method="GET" action="{{ route('employee.leave-requests.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"><x-form-select label="Leave Type" name="leave_type_id" :value="request('leave_type_id')" :options="$leaveTypes->pluck('name', 'id')" placeholder="All leave types" /><x-form-select label="Status" name="status" :value="request('status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" /><x-form-input label="From" name="date_from" type="date" :value="request('date_from')" /><x-form-input label="To" name="date_to" type="date" :value="request('date_to')" /><div class="flex items-end gap-3 md:col-span-2 xl:col-span-4"><x-button>Filter</x-button><a href="{{ route('employee.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear</a></div></form></x-card>
            <x-card class="overflow-hidden p-0">
                @if ($leaveRequests->isEmpty())
                    <x-empty-state title="No leave requests found" description="No personal leave requests match these filters." class="m-5" />
                @else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Leave Type</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Duration</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($leaveRequests as $leaveRequest)<tr><td class="px-5 py-4 font-semibold">{{ $leaveRequest->leaveType->name }}</td><td class="whitespace-nowrap px-5 py-4">{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td><td class="px-5 py-4">{{ $leaveRequest->duration_days }} {{ Str::plural('day', $leaveRequest->duration_days) }}</td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->requested_at->format('d M Y') }}</td><td class="px-5 py-4"><x-leave-status-badge :status="$leaveRequest->status" /></td><td class="px-5 py-4 text-right"><a href="{{ route('employee.leave-requests.show', $leaveRequest) }}" class="font-semibold text-portal-900 hover:underline">View</a></td></tr>@endforeach</tbody></table></div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $leaveRequests->links() }}</div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.admin>
