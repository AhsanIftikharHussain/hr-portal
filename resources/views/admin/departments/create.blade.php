<x-layouts.admin title="Add Department">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <x-page-header title="Add Department" description="Create a reusable department for employee assignments." />
        <form method="POST" action="{{ route('admin.departments.store') }}">@csrf
            <x-card title="Department Details"><x-form-input label="Department Name *" name="name" required /></x-card>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.departments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Create Department</x-button></div>
        </form>
    </div>
</x-layouts.admin>
