<x-layouts.admin title="Employees">
    <div class="flex flex-col gap-6">
        <x-page-header title="Employees" description="Manage employee records, employment details, and reporting relationships.">
            <x-slot:actions>
                <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Add Employee</a>
            </x-slot:actions>
        </x-page-header>

        <x-card>
            <form method="GET" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-slate-700">Search</label>
                    <input id="search" name="search" value="{{ request('search') }}" placeholder="Code, name, or email" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <x-form-select label="Department" name="department_id" :value="request('department_id')" :options="$departments->pluck('name', 'id')" placeholder="All departments" />
                <x-form-select label="Status" name="employment_status" :value="request('employment_status')" :options="collect($employmentStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" />
                <x-form-select label="Type" name="employment_type" :value="request('employment_type')" :options="collect($employmentTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" placeholder="All types" />
                <x-form-select label="Records" name="record_status" :value="request('record_status')" :options="['active' => 'Current', 'archived' => 'Archived', 'all' => 'All']" placeholder="Current" />
                <div class="flex gap-2 md:col-span-2 xl:col-span-6">
                    <x-button>Apply filters</x-button>
                    <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden p-0">
            @if ($employees->isEmpty())
                <x-empty-state title="No employees found" description="Adjust the filters or add the first employee record." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Code</th><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Designation</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Joining date</th><th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($employees as $employee)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $employee->employee_code }}</td>
                                    <td class="px-5 py-4 font-medium text-slate-950">{{ $employee->full_name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->department->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $employee->jobTitle->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $employee->employment_type->label() }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $employee->trashed() ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">{{ $employee->trashed() ? 'Archived' : $employee->employment_status->label() }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $employee->joining_date->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('admin.employees.show', $employee) }}" class="font-medium text-slate-700 hover:text-slate-950">View</a>
                                            @if (! $employee->trashed())
                                                <a href="{{ route('admin.employees.edit', $employee) }}" class="font-medium text-slate-700 hover:text-slate-950">Edit</a>
                                                <form method="POST" action="{{ route('admin.employees.archive', $employee) }}" onsubmit="return confirm('Archive this employee record? Employment status will not be changed.')">
                                                    @csrf
                                                    <button class="font-medium text-red-700 hover:text-red-900">Archive</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.employees.restore', $employee) }}" onsubmit="return confirm('Restore this employee record?')">
                                                    @csrf @method('DELETE')
                                                    <button class="font-medium text-emerald-700 hover:text-emerald-900">Restore</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $employees->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
