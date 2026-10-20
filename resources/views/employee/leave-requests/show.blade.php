<x-layouts.admin :title="$leaveRequest->leaveType->name.' Leave Request'">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <x-page-header :title="$leaveRequest->leaveType->name" :description="$leaveRequest->start_date->format('d M Y').' – '.$leaveRequest->end_date->format('d M Y')"><x-slot:actions><a href="{{ route('employee.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Back to My Leave</a></x-slot:actions></x-page-header>
        <x-card title="Leave Details">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt><dd class="mt-2"><x-leave-status-badge :status="$leaveRequest->status" /></dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Leave Type</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->leaveType->name }} · {{ $leaveRequest->leaveType->is_paid ? 'Paid' : 'Unpaid' }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Start Date</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->start_date->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">End Date</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->end_date->format('d M Y') }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Duration</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->duration_days }} inclusive calendar {{ Str::plural('day', $leaveRequest->duration_days) }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Submitted</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->requested_at->format('d M Y, H:i') }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reason</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $leaveRequest->reason }}</dd></div>
                @if ($leaveRequest->reviewed_at)<div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reviewed At</dt><dd class="mt-1 text-sm text-slate-900">{{ $leaveRequest->reviewed_at->format('d M Y, H:i') }}</dd></div>@endif
                @if ($leaveRequest->hr_comment)<div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Review Comment</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $leaveRequest->hr_comment }}</dd></div>@endif
            </dl>
        </x-card>
        <x-alert>Leave cancellation remains managed by HR under the current approved workflow. Contact HR if this request needs to be cancelled.</x-alert>
    </div>
</x-layouts.admin>
