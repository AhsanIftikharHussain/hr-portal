<x-layouts.admin title="Employee Dashboard">
    @if ($employee === null)
        <x-page-header title="Employee Dashboard" description="Your employee self-service account is not fully configured." />
        <x-alert type="error" class="mt-6">Your account has not yet been linked to an employee record. Please contact HR.</x-alert>
    @else
        <div class="flex flex-col gap-6">
            <x-page-header :title="'Welcome, '.$employee->full_name" :description="'Your employee overview for '.today()->format('d F Y').'.'" />

            <x-card title="Employee Summary">
                <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Employee Code</dt><dd class="mt-1 font-mono text-sm text-slate-900">{{ $employee->employee_code }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->department->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Designation</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->jobTitle->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Employment Status</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->employment_status->label() }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Joining Date</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->joining_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reporting Manager</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->reportingManager?->full_name ?? 'Not assigned' }}</dd></div>
                </dl>
            </x-card>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-card><p class="text-sm font-medium text-slate-500">Pending Leave</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $pendingLeaveCount }}</p></x-card>
                <x-card><p class="text-sm font-medium text-slate-500">Approved Leave {{ now()->year }}</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $approvedLeaveThisYearCount }}</p></x-card>
                <x-card><p class="text-sm font-medium text-slate-500">Attendance Today</p><div class="mt-2">@if ($todayAttendance)<x-attendance-status-badge :status="$todayAttendance->status" />@else<span class="text-lg font-semibold text-slate-700">Not Recorded</span>@endif</div></x-card>
                <x-card><p class="text-sm font-medium text-slate-500">Upcoming Holiday</p><p class="mt-2 text-base font-bold text-slate-950">{{ $upcomingHoliday?->name ?? 'None scheduled' }}</p>@if ($upcomingHoliday)<p class="mt-1 text-xs text-slate-500">{{ $upcomingHoliday->holiday_date->format('d M Y') }}</p>@endif</x-card>
                <x-card><p class="text-sm font-medium text-slate-500">Unread Notifications</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $unreadNotificationCount }}</p></x-card>
            </div>

            <x-card title="Recent Leave Requests">
                @forelse ($recentLeaveRequests as $leaveRequest)
                    <a href="{{ route('employee.leave-requests.show', $leaveRequest) }}" class="flex flex-col gap-2 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <span><span class="block font-semibold text-slate-900">{{ $leaveRequest->leaveType->name }}</span><span class="text-sm text-slate-500">{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</span></span>
                        <x-leave-status-badge :status="$leaveRequest->status" />
                    </a>
                @empty
                    <p class="text-sm text-slate-500">You have not submitted any leave requests.</p>
                @endforelse
            </x-card>
        </div>
    @endif
</x-layouts.admin>
