@php($categoryOptions = $categories->mapWithKeys(fn ($category) => [$category->id => $category->name.($category->is_active ? '' : ' (Inactive)')]))
<div class="grid gap-5 sm:grid-cols-2">
    <x-form-select label="Category" name="policy_category_id" :options="$categoryOptions" :value="$policy->policy_category_id" required />
    <x-form-input label="Title" name="title" :value="$policy->title" maxlength="255" required />
    <div class="sm:col-span-2"><x-form-textarea label="Summary" name="summary" :value="$policy->summary" maxlength="2000" rows="3" /></div>
    <div class="sm:col-span-2"><x-form-textarea label="Policy Content" name="content" :value="$policy->content" maxlength="100000" rows="16" required /><p class="mt-1.5 text-xs text-slate-500">Plain text only. Line breaks are preserved and all content is safely escaped.</p></div>
    <x-form-input label="Effective Date" name="effective_date" type="date" :value="$policy->effective_date?->toDateString()" />
    @if ($policy->exists)
        <x-form-select label="Status" name="status" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" :value="$policy->status->value" required />
    @else
        <div><p class="text-sm font-medium text-slate-700">Initial Status</p><p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-700">Draft</p></div>
    @endif
</div>
