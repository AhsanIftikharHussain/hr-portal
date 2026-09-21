<x-layouts.admin title="Designations">
    <div class="flex flex-col gap-6">
        <x-page-header title="Designations" description="Maintain the controlled job-title list used by employee records.">
            <x-slot:actions><a href="{{ route('admin.job-titles.create') }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Add Designation</a></x-slot:actions>
        </x-page-header>
        <x-card class="overflow-hidden p-0">
            @if ($jobTitles->isEmpty())
                <x-empty-state title="No designations" description="Add the first designation for employee assignments." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Employees</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach ($jobTitles as $jobTitle)
                        <tr><td class="px-5 py-4 font-medium text-slate-950">{{ $jobTitle->name }}</td><td class="px-5 py-4 text-slate-600">{{ $jobTitle->employees_count }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $jobTitle->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $jobTitle->is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-3"><a href="{{ route('admin.job-titles.edit', $jobTitle) }}" class="font-medium text-slate-700">Edit</a><form method="POST" action="{{ $jobTitle->is_active ? route('admin.job-titles.deactivate', $jobTitle) : route('admin.job-titles.reactivate', $jobTitle) }}" onsubmit="return confirm('{{ $jobTitle->is_active ? 'Deactivate this designation? Existing assignments will remain.' : 'Reactivate this designation?' }}')">@csrf @if (! $jobTitle->is_active) @method('DELETE') @endif<button class="font-medium {{ $jobTitle->is_active ? 'text-red-700' : 'text-emerald-700' }}">{{ $jobTitle->is_active ? 'Deactivate' : 'Reactivate' }}</button></form></div></td></tr>
                    @endforeach
                    </tbody></table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $jobTitles->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
