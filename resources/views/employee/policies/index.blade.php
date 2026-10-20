<x-layouts.admin title="Office Policies">
    <div class="flex flex-col gap-6">
        <x-page-header title="Office Policies" description="Published company policies available to employees." />
        <x-card><form method="GET" action="{{ route('employee.policies.index') }}" class="grid gap-4 md:grid-cols-3"><x-form-input label="Search" name="search" :value="request('search')" placeholder="Title or summary" /><x-form-select label="Category" name="policy_category_id" :value="request('policy_category_id')" :options="$categories->pluck('name', 'id')" placeholder="All categories" /><div class="flex items-end gap-3"><x-button>Filter</x-button><a href="{{ route('employee.policies.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear</a></div></form></x-card>
        <div class="grid gap-5 lg:grid-cols-2">
            @forelse ($policies as $policy)
                <x-card>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $policy->category->name }}</p>
                    <h2 class="mt-2 text-lg font-bold text-slate-950"><a href="{{ route('employee.policies.show', $policy) }}" class="hover:underline">{{ $policy->title }}</a></h2>
                    @if ($policy->summary)<p class="mt-2 text-sm leading-6 text-slate-600">{{ $policy->summary }}</p>@endif
                    <div class="mt-4 flex items-center justify-between gap-4"><span class="text-xs text-slate-500">Effective {{ $policy->effective_date?->format('d M Y') ?? 'date not set' }}</span><a href="{{ route('employee.policies.show', $policy) }}" class="text-sm font-semibold text-portal-900">Read policy</a></div>
                </x-card>
            @empty
                <x-empty-state title="No published policies found" description="No published policies match the current filters." class="lg:col-span-2" />
            @endforelse
        </div>
        {{ $policies->links() }}
    </div>
</x-layouts.admin>
