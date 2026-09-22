<x-layouts.admin title="Leave Types">
    <div class="flex flex-col gap-6">
        <x-page-header title="Leave Types" description="Configure the leave categories available when HR records a request.">
            <x-slot:actions><a href="{{ route('admin.leave-types.create') }}" class="inline-flex items-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-portal-700">Add Leave Type</a></x-slot:actions>
        </x-page-header>
        <x-card class="overflow-hidden p-0">
            @if ($leaveTypes->isEmpty())
                <x-empty-state title="No leave types" description="Add Annual Leave, Sick Leave, or another organization-approved type." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Policy Flags</th><th class="px-5 py-3">Requests</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($leaveTypes as $leaveType)
                                <tr><td class="px-5 py-4 font-medium text-slate-950">{{ $leaveType->name }}</td><td class="px-5 py-4 font-mono text-xs text-slate-600">{{ $leaveType->code }}</td><td class="px-5 py-4 text-slate-600">{{ $leaveType->is_paid ? 'Paid' : 'Unpaid' }} · {{ $leaveType->requires_attachment ? 'Evidence required' : 'No evidence flag' }}</td><td class="px-5 py-4 text-slate-600">{{ $leaveType->leave_requests_count }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $leaveType->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $leaveType->is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-3"><a href="{{ route('admin.leave-types.edit', $leaveType) }}" class="font-medium text-slate-700">Edit</a><form method="POST" action="{{ $leaveType->is_active ? route('admin.leave-types.deactivate', $leaveType) : route('admin.leave-types.reactivate', $leaveType) }}" onsubmit="return confirm('{{ $leaveType->is_active ? 'Deactivate this leave type? Existing leave history will remain.' : 'Reactivate this leave type?' }}')">@csrf @if (! $leaveType->is_active) @method('DELETE') @endif<button class="font-medium {{ $leaveType->is_active ? 'text-red-700' : 'text-emerald-700' }}">{{ $leaveType->is_active ? 'Deactivate' : 'Reactivate' }}</button></form></div></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $leaveTypes->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
