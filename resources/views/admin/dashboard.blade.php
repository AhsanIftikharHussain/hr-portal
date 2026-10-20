<x-layouts.admin title="Dashboard">
    <div class="flex flex-col gap-8">
        <x-page-header title="HR Dashboard" description="A current overview of employees, workforce status, and recent joiners." />

        <section aria-labelledby="workforce-metrics-heading">
            <h2 id="workforce-metrics-heading" class="sr-only">Workforce metrics</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metrics as $metric)
                    <a
                        href="{{ route('admin.employees.index', $metric['filter'] ? ['employment_status' => $metric['filter']->value] : []) }}"
                        class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"
                    >
                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $metric['count'] }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View employees →</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="quick-actions-heading">
            <div class="mb-3"><h2 id="quick-actions-heading" class="text-lg font-semibold text-slate-950">Quick Actions</h2><p class="mt-1 text-sm text-slate-500">Open an existing HR workflow.</p></div>
            <div class="flex flex-wrap gap-3">
                @can('create', \App\Models\Employee::class)
                    <a href="{{ route('admin.employees.create') }}" class="rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-portal-800">Add Employee</a>
                @endcan
                @can('create', \App\Models\AttendanceRecord::class)
                    <a href="{{ route('admin.attendance.create') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Mark Attendance</a>
                @endcan
                @can('viewAny', \App\Models\LeaveRequest::class)
                    <a href="{{ route('admin.leave-requests.index', ['status' => \App\LeaveRequestStatus::Pending->value]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Approve Leave</a>
                @endcan
            </div>
        </section>

        <section aria-labelledby="contract-metrics-heading">
            <div class="mb-3 flex items-end justify-between gap-4"><div><h2 id="contract-metrics-heading" class="text-lg font-semibold text-slate-950">Contract Expiry</h2><p class="mt-1 text-sm text-slate-500">Active or upcoming contracts ending within the next 30 days.</p></div><a href="{{ route('admin.contracts.index') }}" class="text-sm font-semibold text-portal-900 hover:underline">View all contracts →</a></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('admin.contracts.index', ['expiry_window' => 30]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Contracts Expiring Soon</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $contractsExpiringSoon }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View next 30 days →</p></a>
            </div>
        </section>

        <section aria-labelledby="holiday-overview-heading">
            <div class="mb-3 flex items-end justify-between gap-4"><div><h2 id="holiday-overview-heading" class="text-lg font-semibold text-slate-950">Upcoming Holidays</h2><p class="mt-1 text-sm text-slate-500">The next active holidays on the HR calendar.</p></div><a href="{{ route('admin.holidays.calendar') }}" class="text-sm font-semibold text-portal-900 hover:underline">View calendar →</a></div>
            <x-card>
                @if ($upcomingHolidays->isEmpty())
                    <x-empty-state title="No upcoming holidays" description="Add or activate future holidays to show them here." />
                @else
                    <div class="divide-y divide-slate-100">@foreach ($upcomingHolidays as $holiday)<a href="{{ route('admin.holidays.calendar', ['year' => $holiday->holiday_date->year]) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"><span><span class="block text-sm font-semibold text-slate-900">{{ $holiday->name }}</span><span class="block text-xs text-slate-500">{{ $holiday->type->label() }} · {{ $holiday->day_portion->label() }}</span></span><span class="whitespace-nowrap text-sm font-medium text-slate-700">{{ $holiday->holiday_date->format('d M Y') }}</span></a>@endforeach</div>
                @endif
            </x-card>
        </section>

        <section aria-labelledby="attendance-metrics-heading">
            <div class="mb-3 flex items-end justify-between gap-4"><div><h2 id="attendance-metrics-heading" class="text-lg font-semibold text-slate-950">Attendance Today</h2><p class="mt-1 text-sm text-slate-500">Counts use explicit records only; missing records are not treated as absent.</p></div><a href="{{ route('admin.attendance.index', ['date' => $today->toDateString()]) }}" class="text-sm font-semibold text-portal-900 hover:underline">View attendance →</a></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('admin.attendance.index', ['date' => $today->toDateString(), 'status' => \App\AttendanceStatus::Present->value]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Present Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $attendanceMetrics['present'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View explicit present records →</p></a>
                <a href="{{ route('admin.attendance.index', ['date' => $today->toDateString(), 'status' => \App\AttendanceStatus::Absent->value]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Absent Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $attendanceMetrics['absent'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View explicit absent records →</p></a>
                <a href="{{ route('admin.attendance.index', ['date' => $today->toDateString()]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Not Recorded Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $attendanceMetrics['not_recorded'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">No explicit record; not classified as absent →</p></a>
            </div>
        </section>

        <section aria-labelledby="leave-metrics-heading">
            <div class="mb-3 flex items-end justify-between gap-4"><div><h2 id="leave-metrics-heading" class="text-lg font-semibold text-slate-950">Leave Overview</h2><p class="mt-1 text-sm text-slate-500">Live counts from approved and pending leave requests.</p></div><a href="{{ route('admin.leave-requests.index') }}" class="text-sm font-semibold text-portal-900 hover:underline">View all leave →</a></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('admin.leave-requests.index', ['status' => \App\LeaveRequestStatus::Pending->value]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Pending Leave Requests</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $leaveMetrics['pending'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">Review requests →</p></a>
                <a href="{{ route('admin.leave-requests.index', ['status' => \App\LeaveRequestStatus::Approved->value, 'date_from' => $today->toDateString(), 'date_to' => $today->toDateString()]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">On Leave Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $leaveMetrics['on_leave_today'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View approved date ranges →</p></a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-card title="Pending Leave Requests">
                @if ($pendingLeaveRequests->isEmpty())
                    <x-empty-state title="No pending leave requests" description="New requests awaiting HR review will appear here." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($pendingLeaveRequests as $leaveRequest)
                            <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $leaveRequest->employee->full_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $leaveRequest->leaveType->name }} · {{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }} · {{ $leaveRequest->duration_days }} {{ Str::plural('day', $leaveRequest->duration_days) }}</p>
                                </div>
                                @can('view', $leaveRequest)
                                    <a href="{{ route('admin.leave-requests.show', $leaveRequest) }}" class="whitespace-nowrap text-sm font-semibold text-portal-900 hover:underline">Review / Approve</a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('admin.leave-requests.index', ['status' => \App\LeaveRequestStatus::Pending->value]) }}" class="mt-4 inline-block text-sm font-semibold text-portal-900 hover:underline">View all pending requests →</a>
                @endif
            </x-card>

            <x-card title="Probation Ending Soon">
                @if ($probationEndingSoon->isEmpty())
                    <x-empty-state title="No probation confirmations due in the next 30 days" description="Employees on probation with an upcoming confirmation date will appear here." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($probationEndingSoon as $item)
                            <a href="{{ route('admin.employees.show', $item['employee']) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ $item['employee']->full_name }}</span><span class="block truncate text-xs text-slate-500">{{ $item['employee']->jobTitle->name }} · {{ $item['employee']->department->name }}</span></span>
                                <span class="whitespace-nowrap text-right"><span class="block text-sm font-medium text-slate-700">{{ $item['employee']->confirmation_date->format('d M Y') }}</span><span class="block text-xs text-slate-500">{{ $item['daysRemaining'] === 0 ? 'Due today' : $item['daysRemaining'].' '.Str::plural('day', $item['daysRemaining']).' remaining' }}</span></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-card title="Birthdays This Month">
                @if ($birthdaysThisMonth->isEmpty())
                    <x-empty-state title="No birthdays this month" description="Current employees with birthdays this month will appear here." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($birthdaysThisMonth as $employee)
                            <a href="{{ route('admin.employees.show', $employee) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ $employee->full_name }}</span><span class="block truncate text-xs text-slate-500">{{ $employee->department->name }}</span></span><span class="whitespace-nowrap text-sm font-medium text-slate-700">{{ $employee->date_of_birth->format('d M') }}</span></a>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Work Anniversaries This Month">
                @if ($workAnniversariesThisMonth->isEmpty())
                    <x-empty-state title="No work anniversaries this month" description="Completed service anniversaries for current employees will appear here." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($workAnniversariesThisMonth as $item)
                            <a href="{{ route('admin.employees.show', $item['employee']) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ $item['employee']->full_name }}</span><span class="block truncate text-xs text-slate-500">{{ $item['employee']->department->name }}</span></span><span class="whitespace-nowrap text-right"><span class="block text-sm font-medium text-slate-700">{{ $item['employee']->joining_date->format('d M') }}</span><span class="block text-xs text-slate-500">{{ $item['completedYears'] }} {{ Str::plural('year', $item['completedYears']) }}</span></span></a>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-card title="Employment Status Overview">
                <div class="divide-y divide-slate-100">
                    @foreach ($statusOverview as $item)
                        <a href="{{ route('admin.employees.index', ['employment_status' => $item['status']->value]) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0 hover:text-slate-950">
                            <span class="text-sm font-medium text-slate-700">{{ $item['status']->label() }}</span>
                            <span class="min-w-9 rounded-full bg-slate-100 px-2.5 py-1 text-center text-xs font-semibold text-slate-700">{{ $item['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Active Employees by Department">
                @if ($departments->isEmpty())
                    <x-empty-state title="No active department counts" description="Active employees will appear here after they are assigned to active departments." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($departments as $department)
                            <a href="{{ route('admin.employees.index', ['department_id' => $department->id, 'employment_status' => \App\EmploymentStatus::Active->value]) }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0 hover:text-slate-950">
                                <span class="min-w-0 truncate text-sm font-medium text-slate-700">{{ $department->name }}</span>
                                <span class="whitespace-nowrap text-sm font-semibold text-slate-950">{{ $department->active_employees_count }} {{ Str::plural('employee', $department->active_employees_count) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <x-card title="Recently Joined Employees" class="overflow-hidden p-0">
            @if ($recentEmployees->isEmpty())
                <x-empty-state title="No employees yet" description="Recently joined employees will appear after employee records are created." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Employee</th>
                                <th class="px-5 py-3">Code</th>
                                <th class="px-5 py-3">Designation</th>
                                <th class="px-5 py-3">Department</th>
                                <th class="px-5 py-3">Joining Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($recentEmployees as $employee)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4"><a href="{{ route('admin.employees.show', $employee) }}" class="font-semibold text-slate-950 hover:underline">{{ $employee->full_name }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-600">{{ $employee->employee_code }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->jobTitle->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->department->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $employee->joining_date->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
