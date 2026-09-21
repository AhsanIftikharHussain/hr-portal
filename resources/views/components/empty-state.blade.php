@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center']) }}>
    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ $description }}</p>
</div>
