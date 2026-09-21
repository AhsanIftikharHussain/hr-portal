<x-layouts.admin title="Add Designation">
    <div class="mx-auto flex max-w-2xl flex-col gap-6"><x-page-header title="Add Designation" description="Create a reusable designation for employee assignments." />
        <form method="POST" action="{{ route('admin.job-titles.store') }}">@csrf<x-card title="Designation Details"><x-form-input label="Designation Name *" name="name" required /></x-card><div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.job-titles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Create Designation</x-button></div></form>
    </div>
</x-layouts.admin>
