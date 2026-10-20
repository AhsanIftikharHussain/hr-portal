<x-layouts.admin :title="$leaveRequest->employee->full_name.' Leave Request'">
    <div class="mx-auto flex max-w-5xl flex-col gap-6">
        <x-page-header :title="$leaveRequest->employee->full_name" :description="$leaveRequest->employee->employee_code.' · '.$leaveRequest->leaveType->name"><x-slot:actions><a href="{{ route('manager.leave-requests.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Back to Leave</a></x-slot:actions></x-page-header>
        @error('status')<x-alert type="error">{{ $message }}</x-alert>@enderror
        <div class="grid gap-6 lg:grid-cols-3">
            <x-card title="Leave Details" class="lg:col-span-2"><dl class="grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="mt-2"><x-leave-status-badge :status="$leaveRequest->status" /></dd></div><div><dt class="text-xs uppercase text-slate-500">Leave type</dt><dd class="mt-1 text-sm">{{ $leaveRequest->leaveType->name }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Start date</dt><dd class="mt-1 text-sm">{{ $leaveRequest->start_date->format('d M Y') }}</dd></div><div><dt class="text-xs uppercase text-slate-500">End date</dt><dd class="mt-1 text-sm">{{ $leaveRequest->end_date->format('d M Y') }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Duration</dt><dd class="mt-1 text-sm">{{ $leaveRequest->duration_days }} days</dd></div><div><dt class="text-xs uppercase text-slate-500">Requested</dt><dd class="mt-1 text-sm">{{ $leaveRequest->requested_at->format('d M Y, H:i') }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">Reason</dt><dd class="mt-1 whitespace-pre-line text-sm">{{ $leaveRequest->reason }}</dd></div>
                @if ($leaveRequest->reviewed_at)<div><dt class="text-xs uppercase text-slate-500">Reviewed by</dt><dd class="mt-1 text-sm">{{ $leaveRequest->reviewer?->name ?? 'Deleted user' }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Reviewed at</dt><dd class="mt-1 text-sm">{{ $leaveRequest->reviewed_at->format('d M Y, H:i') }}</dd></div>@endif
                @if ($leaveRequest->hr_comment)<div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">Review comment</dt><dd class="mt-1 whitespace-pre-line text-sm">{{ $leaveRequest->hr_comment }}</dd></div>@endif
            </dl></x-card>
            <x-card title="Employee"><p class="font-semibold">{{ $leaveRequest->employee->full_name }}</p><p class="font-mono text-xs text-slate-500">{{ $leaveRequest->employee->employee_code }}</p><p class="mt-4 text-sm">{{ $leaveRequest->employee->jobTitle->name }}</p><p class="text-sm text-slate-500">{{ $leaveRequest->employee->department->name }}</p></x-card>
        </div>
        @if ($leaveRequest->status === \App\LeaveRequestStatus::Pending)
            @can('update', $leaveRequest)
                <div class="grid gap-6 lg:grid-cols-2"><form method="POST" action="{{ route('manager.leave-requests.approve', $leaveRequest) }}">@csrf<x-card title="Approve Request"><x-form-textarea label="Review comment (optional)" name="hr_comment" rows="3" /><x-button class="mt-4 bg-emerald-700 hover:bg-emerald-600">Approve Leave</x-button></x-card></form><form method="POST" action="{{ route('manager.leave-requests.reject', $leaveRequest) }}">@csrf<x-card title="Reject Request"><x-form-textarea label="Reason for rejection *" name="hr_comment" rows="3" required /><x-button class="mt-4 bg-red-700 hover:bg-red-600">Reject Leave</x-button></x-card></form></div>
            @else
                <x-alert type="info">You cannot approve or reject your own leave request.</x-alert>
            @endcan
        @endif
    </div>
</x-layouts.admin>
