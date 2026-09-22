<x-layouts.admin title="Add Leave Type">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <x-page-header title="Add Leave Type" description="Create a reusable category for employee leave requests." />
        <form method="POST" action="{{ route('admin.leave-types.store') }}">@csrf @include('admin.leave-types._form', ['leaveType' => null])<div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.leave-types.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Create Leave Type</x-button></div></form>
    </div>
</x-layouts.admin>
