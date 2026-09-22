<x-layouts.admin :title="'Edit '.$leaveType->name">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <x-page-header :title="'Edit '.$leaveType->name" description="Update the leave type label, stable code, and policy flags." />
        <form method="POST" action="{{ route('admin.leave-types.update', $leaveType) }}">@csrf @method('PUT') @include('admin.leave-types._form')<div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.leave-types.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Save Changes</x-button></div></form>
    </div>
</x-layouts.admin>
