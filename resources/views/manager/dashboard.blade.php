<x-layouts.admin title="Department Dashboard">
    @if ($department === null)
        <x-page-header title="Department Dashboard" description="Your manager account is not linked to an active employee and department." />
        <x-alert type="error" class="mt-6">Department access is not configured. Please ask an HR administrator to link your account to the correct employee record.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header :title="$department->name.' Dashboard'" :description="'Department overview for '.$today->format('d F Y').'.'" />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ([
                    'Department Employees' => $metrics['total_employees'],
                    'Present Today' => $metrics['present'],
                    'Absent Today' => $metrics['absent'],
                    'Not Recorded' => $metrics['not_recorded'],
                    'Pending Leave' => $metrics['pending_leave'],
                    'On Leave Today' => $metrics['on_leave_today'],
                ] as $label => $count)
                    <x-card><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $count }}</p></x-card>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Pending Leave Requests">
                    @forelse ($pendingLeaveRequests as $leaveRequest)
                        <a href="{{ route('manager.leave-requests.show', $leaveRequest) }}" class="flex justify-between gap-4 border-b border-slate-100 py-3 last:border-0">
                            <span><span class="block font-semibold text-slate-900">{{ $leaveRequest->employee->full_name }}</span><span class="text-sm text-slate-500">{{ $leaveRequest->leaveType->name }}</span></span>
                            <span class="text-sm text-slate-600">{{ $leaveRequest->start_date->format('d M') }} – {{ $leaveRequest->end_date->format('d M') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No pending department leave requests.</p>
                    @endforelse
                </x-card>

                <x-card title="Probation Ending Soon">
                    @forelse ($probationEndingSoon as $employee)
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-3 last:border-0">
                            <span><span class="block font-semibold text-slate-900">{{ $employee->full_name }}</span><span class="text-sm text-slate-500">{{ $employee->jobTitle->name }}</span></span>
                            <span class="text-sm text-slate-600">{{ $employee->confirmation_date->format('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No probation confirmations are due in the next 30 days.</p>
                    @endforelse
                </x-card>
            </div>
        </div>
    @endif
</x-layouts.admin>
