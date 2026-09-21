@props(['href' => null, 'active' => false, 'disabled' => false])

@if ($disabled)
    <span {{ $attributes->merge(['class' => 'flex cursor-not-allowed items-center justify-between rounded-lg px-3 py-2.5 text-sm text-slate-400']) }}>
        <span>{{ $slot }}</span>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500">Soon</span>
    </span>
@else
    <a href="{{ $href }}" @class([
        'rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
        'bg-portal-100 text-portal-900' => $active,
        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
    ]) {{ $attributes }}>
        {{ $slot }}
    </a>
@endif
