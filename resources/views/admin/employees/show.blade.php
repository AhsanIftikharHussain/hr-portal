<x-layouts.admin :title="$employee->full_name">
    <div class="flex flex-col gap-6">
        <x-page-header :title="$employee->full_name" :description="$employee->employee_code.' · '.$employee->department->name.' · '.$employee->jobTitle->name">
            <x-slot:actions>
                <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                @if (! $employee->trashed())
                    <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Edit Employee</a>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($employee->trashed())
            <x-alert>This employee record is archived. Its employment status remains <strong>{{ $employee->employment_status->label() }}</strong>.</x-alert>
        @endif

        <div class="flex gap-2 overflow-x-auto border-b border-slate-200 pb-3 text-sm">
            <span class="rounded-lg bg-portal-900 px-3 py-2 font-medium text-white">Overview</span>
            <span class="rounded-lg bg-slate-100 px-3 py-2 font-medium text-slate-700">Employment</span>
            <span class="rounded-lg bg-slate-100 px-3 py-2 font-medium text-slate-700">Emergency Contact</span>
            <span class="rounded-lg bg-slate-100 px-3 py-2 font-medium text-slate-700">Leave History</span>
            @foreach (['Attendance', 'Contracts', 'Documents', 'Onboarding', 'Offboarding'] as $futureTab)
                <span class="cursor-not-allowed rounded-lg border border-dashed border-slate-300 px-3 py-2 text-slate-400" title="Planned for a future phase">{{ $futureTab }}</span>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-card title="Overview">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">NIC</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->national_id ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Date of birth</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->date_of_birth?->format('d M Y') ?? 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Gender</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->gender?->label() ?? 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Marital status</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->marital_status?->label() ?? 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Personal email</dt><dd class="mt-1 break-all text-sm text-slate-900">{{ $employee->personal_email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Official email</dt><dd class="mt-1 break-all text-sm text-slate-900">{{ $employee->official_email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact number</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Alternate contact</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->alternate_contact_number ?: 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Address</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900">{{ $employee->address ?: 'Not provided' }}{{ $employee->city ? ', '.$employee->city : '' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Employment">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->department->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Designation</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->jobTitle->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Employment type</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->employment_type->label() }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Employment status</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->employment_status->label() }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Joining date</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->joining_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Confirmation date</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->confirmation_date?->format('d M Y') ?? 'Not confirmed' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Reporting manager</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->reportingManager?->full_name ?? 'None assigned' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Work location</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->work_location ?: 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Portal account</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->user?->email ?? 'Not linked' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Emergency Contact" class="xl:col-span-2">
                <dl class="grid gap-5 sm:grid-cols-3">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Name</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->emergency_contact_name ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Relationship</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->emergency_contact_relationship ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact number</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->emergency_contact_number ?: 'Not provided' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Leave History" class="overflow-hidden p-0 xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4"><div><p class="text-sm font-semibold text-slate-950">{{ $approvedLeaveDays }} approved calendar {{ Str::plural('day', $approvedLeaveDays) }}</p><p class="mt-1 text-xs text-slate-500">Approved usage history only. No entitlement or remaining-balance policy has been configured.</p></div>@if (! $employee->trashed())<a href="{{ route('admin.leave-requests.create', ['employee_id' => $employee->id]) }}" class="text-sm font-semibold text-portal-900 hover:underline">Add leave request</a>@endif</div>
                @if ($leaveRequests->isEmpty())
                    <x-empty-state title="No leave history" description="Leave requests recorded for this employee will appear here." class="m-5" />
                @else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Type</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Duration</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Requested</th><th class="px-5 py-3 text-right">Details</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($leaveRequests as $leaveRequest)<tr><td class="px-5 py-4 font-medium text-slate-950">{{ $leaveRequest->leaveType->name }}</td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->start_date->format('d M Y') }} – {{ $leaveRequest->end_date->format('d M Y') }}</td><td class="px-5 py-4 text-slate-600">{{ $leaveRequest->duration_days }} {{ Str::plural('day', $leaveRequest->duration_days) }}</td><td class="px-5 py-4"><x-leave-status-badge :status="$leaveRequest->status" /></td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $leaveRequest->requested_at->format('d M Y') }}</td><td class="px-5 py-4 text-right"><a href="{{ route('admin.leave-requests.show', $leaveRequest) }}" class="font-semibold text-portal-900 hover:underline">View</a></td></tr>@endforeach</tbody></table></div>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.admin>
