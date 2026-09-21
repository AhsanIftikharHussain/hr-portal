<x-layouts.admin :title="'Edit '.$department->name">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <x-page-header :title="'Edit '.$department->name" description="Rename this department without affecting employee assignments." />
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">@csrf @method('PUT')
            <x-card title="Department Details"><x-form-input label="Department Name *" name="name" :value="$department->name" required /></x-card>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.departments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Save Changes</x-button></div>
        </form>
    </div>
</x-layouts.admin>
