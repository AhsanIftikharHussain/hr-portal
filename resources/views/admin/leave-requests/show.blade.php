<x-layouts.admin :title="$leaveRequest->employee->full_name.' Leave Request'">
    <div class="mx-auto flex max-w-5xl flex-col gap-6">
        <x-page-header :title="$leaveRequest->employee->full_name" :description="$leaveRequest->employee->employee_code.' · '.$leaveRequest->leaveType->name">
            <x-slot:actions><a href="{{ route('admin.leave-requests.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Leave</a></x-slot:actions>
        </x-page-header>

        @error('status')<x-alert type="error">{{ $message }}</x-alert>@enderror

        <div class="grid gap-6 lg:grid-cols-3">
            <x-card title="Leave Details" class="lg:col-span-2">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt><dd class="mt-2"><x-leave-status-badge :status="$leaveRequest->status" /></dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Leave type</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->leaveType->name }} · {{ $leaveRequest->leaveType->is_paid ? 'Paid' : 'Unpaid' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Start date</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->start_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">End date</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->end_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Duration</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->duration_days }} inclusive calendar {{ Str::plural('day', $leaveRequest->duration_days) }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Requested</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->requested_at->format('d M Y, H:i') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reason</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $leaveRequest->reason }}</dd></div>
                    @if ($leaveRequest->reviewed_at)
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reviewed by</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->reviewer?->name ?? 'Deleted user' }}</dd></div>
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reviewed at</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->reviewed_at->format('d M Y, H:i') }}</dd></div>
                    @endif
                    @if ($leaveRequest->hr_comment)<div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">HR comment</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $leaveRequest->hr_comment }}</dd></div>@endif
                </dl>
            </x-card>

            <x-card title="Employee">
                <p class="font-semibold text-slate-950"><a href="{{ route('admin.employees.show', $leaveRequest->employee) }}" class="hover:underline">{{ $leaveRequest->employee->full_name }}</a></p>
                <p class="mt-1 font-mono text-xs text-slate-500">{{ $leaveRequest->employee->employee_code }}</p>
                <p class="mt-4 text-sm text-slate-700">{{ $leaveRequest->employee->jobTitle->name }}</p>
                <p class="text-sm text-slate-500">{{ $leaveRequest->employee->department->name }}</p>
                @if ($leaveRequest->employee->trashed())<p class="mt-4 text-sm font-medium text-amber-700">Employee record archived</p>@endif
            </x-card>
        </div>

        @if ($leaveRequest->status === \App\LeaveRequestStatus::Pending)
            <div class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('admin.leave-requests.approve', $leaveRequest) }}">@csrf<x-card title="Approve Request"><x-form-textarea label="HR Comment (optional)" name="hr_comment" rows="3" /><x-button class="mt-4 bg-emerald-700 hover:bg-emerald-600">Approve Leave</x-button></x-card></form>
                <form method="POST" action="{{ route('admin.leave-requests.reject', $leaveRequest) }}">@csrf<x-card title="Reject Request"><x-form-textarea label="Reason for rejection *" name="hr_comment" rows="3" required /><x-button class="mt-4 bg-red-700 hover:bg-red-600">Reject Leave</x-button></x-card></form>
            </div>
        @endif

        @if (in_array($leaveRequest->status, [\App\LeaveRequestStatus::Pending, \App\LeaveRequestStatus::Approved], true))
            <form method="POST" action="{{ route('admin.leave-requests.cancel', $leaveRequest) }}" onsubmit="return confirm('Cancel this leave request?')">@csrf<x-card title="Cancel Request"><p class="mb-4 text-sm text-slate-600">HR may cancel a pending or approved request. Rejected and cancelled requests are terminal.</p><x-form-textarea label="Cancellation reason *" name="hr_comment" rows="3" required /><x-button class="mt-4 bg-slate-700 hover:bg-slate-600">Cancel Leave Request</x-button></x-card></form>
        @endif
    </div>
</x-layouts.admin>
