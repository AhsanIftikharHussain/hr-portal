<x-layouts.admin title="Contracts & Documents">
    <div class="flex flex-col gap-6">
        <x-page-header title="Contracts & Documents" description="Manage contract history, expiry, and private employee records.">
            <x-slot:actions><a href="{{ route('admin.contracts.create') }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Add Contract</a></x-slot:actions>
        </x-page-header>

        <x-card>
            <form method="GET" action="{{ route('admin.contracts.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-form-input label="Employee" name="search" :value="request('search')" placeholder="Name or code" />
                <x-form-select label="Department" name="department_id" :value="request('department_id')" :options="$departments->pluck('name', 'id')" placeholder="All departments" />
                <x-form-select label="Status" name="status" :value="request('status')" :options="collect($contractStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" placeholder="All statuses" />
                <x-form-select label="Contract Type" name="contract_type" :value="request('contract_type')" :options="collect($contractTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" placeholder="All types" />
                <x-form-select label="Expiry" name="expiry_window" :value="request('expiry_window')" :options="[30 => 'Next 30 days', 60 => 'Next 60 days', 90 => 'Next 90 days']" placeholder="Any expiry" />
                <div class="flex items-end gap-3 md:col-span-2 xl:col-span-5"><x-button>Filter</x-button><a href="{{ route('admin.contracts.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear</a></div>
            </form>
        </x-card>

        <x-card class="overflow-hidden p-0">
            @if ($contracts->isEmpty())
                <x-empty-state title="No contracts found" description="Add a contract or adjust the current filters." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Employee</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Start</th><th class="px-5 py-3">End</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">File</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($contracts as $contract)
                                <tr class="hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('admin.employees.show', $contract->employee) }}#contracts" class="font-semibold text-slate-950 hover:underline">{{ $contract->employee->full_name }}</a><span class="block font-mono text-xs text-slate-500">{{ $contract->employee->employee_code }}{{ $contract->employee->trashed() ? ' · Archived' : '' }}</span></td><td class="px-5 py-4 text-slate-700">{{ $contract->contract_type->label() }}</td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $contract->start_date->format('d M Y') }}</td><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $contract->end_date?->format('d M Y') ?? 'Open-ended' }}</td><td class="px-5 py-4"><x-contract-status-badge :status="$contract->status" /></td><td class="px-5 py-4 text-slate-600">{{ $contract->file_available ? 'Available' : 'Missing' }}</td><td class="px-5 py-4 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.contracts.download', $contract) }}" class="font-semibold text-portal-900 hover:underline">Download</a><a href="{{ route('admin.contracts.edit', $contract) }}" class="font-semibold text-slate-700 hover:underline">Edit</a></div></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $contracts->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
