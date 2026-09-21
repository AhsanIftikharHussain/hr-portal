@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    @if ($title)
        <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
    @endif
    <div @class(['mt-4' => $title])>{{ $slot }}</div>
</section>
