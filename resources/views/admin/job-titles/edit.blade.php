<x-layouts.admin :title="'Edit '.$jobTitle->name">
    <div class="mx-auto flex max-w-2xl flex-col gap-6"><x-page-header :title="'Edit '.$jobTitle->name" description="Rename this designation without affecting employee assignments." />
        <form method="POST" action="{{ route('admin.job-titles.update', $jobTitle) }}">@csrf @method('PUT')<x-card title="Designation Details"><x-form-input label="Designation Name *" name="name" :value="$jobTitle->name" required /></x-card><div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.job-titles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Save Changes</x-button></div></form>
    </div>
</x-layouts.admin>
