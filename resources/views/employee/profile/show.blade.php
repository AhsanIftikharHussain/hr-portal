<x-layouts.admin title="My HR Profile">
    @if ($employee === null)
        <x-page-header title="My HR Profile" description="Your employee record is not available." />
        <x-alert type="error" class="mt-6">Your account has not yet been linked to an employee record. Please contact HR.</x-alert>
    @else
        <div class="mx-auto flex max-w-5xl flex-col gap-6">
            <x-page-header title="My HR Profile" description="Your HR-managed employment information is read-only." />
            <x-card title="Employment Details">
                <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Employee Code' => $employee->employee_code,
                        'Full Name' => $employee->full_name,
                        'Official Email' => $employee->official_email ?: 'Not provided',
                        'Personal Email' => $employee->personal_email ?: 'Not provided',
                        'Contact Number' => $employee->contact_number ?: 'Not provided',
                        'Department' => $employee->department->name,
                        'Designation' => $employee->jobTitle->name,
                        'Reporting Manager' => $employee->reportingManager?->full_name ?? 'Not assigned',
                        'Employment Type' => $employee->employment_type->label(),
                        'Employment Status' => $employee->employment_status->label(),
                        'Joining Date' => $employee->joining_date->format('d M Y'),
                        'Confirmation Date' => $employee->confirmation_date?->format('d M Y') ?? 'Not confirmed',
                        'Work Location' => $employee->work_location ?: 'Not provided',
                    ] as $label => $value)
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </x-card>
            <x-alert>To change HR-managed employment details, please contact HR. Account name, login email, and password remain available under My Account &amp; Password.</x-alert>
        </div>
    @endif
</x-layouts.admin>
