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

        <section aria-labelledby="attendance-metrics-heading">
            <div class="mb-3 flex items-end justify-between gap-4"><div><h2 id="attendance-metrics-heading" class="text-lg font-semibold text-slate-950">Attendance Today</h2><p class="mt-1 text-sm text-slate-500">Counts use explicit records only; missing records are not treated as absent.</p></div><a href="{{ route('admin.attendance.index', ['date' => $today->toDateString()]) }}" class="text-sm font-semibold text-portal-900 hover:underline">View attendance →</a></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('admin.attendance.index', ['date' => $today->toDateString(), 'status' => \App\AttendanceStatus::Present->value]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Present Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $attendanceMetrics['present'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View explicit present records →</p></a>
                <a href="{{ route('admin.attendance.index', ['date' => $today->toDateString(), 'status' => \App\AttendanceStatus::Absent->value]) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow"><p class="text-sm font-medium text-slate-500">Absent Today</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $attendanceMetrics['absent'] }}</p><p class="mt-2 text-xs font-medium text-slate-500 group-hover:text-slate-800">View explicit absent records →</p></a>
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
