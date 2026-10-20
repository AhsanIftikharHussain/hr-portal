<x-layouts.admin :title="$policy->title">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <x-page-header :title="$policy->title" :description="$policy->category->name"><x-slot:actions><a href="{{ route('employee.policies.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Back to Policies</a></x-slot:actions></x-page-header>
        <x-card>
            <div class="flex flex-wrap gap-3"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Published</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Effective {{ $policy->effective_date?->format('d M Y') ?? 'date not set' }}</span></div>
            @if ($policy->summary)<p class="mt-5 text-base text-slate-700">{{ $policy->summary }}</p>@endif
            <div class="mt-6 whitespace-pre-wrap border-t border-slate-200 pt-6 text-sm leading-7 text-slate-800">{{ $policy->content }}</div>
        </x-card>
    </div>
</x-layouts.admin>
