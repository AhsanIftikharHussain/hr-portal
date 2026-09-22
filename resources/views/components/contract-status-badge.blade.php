@props(['status'])

@php
    $classes = match ($status) {
        \App\ContractStatus::Active => 'bg-emerald-100 text-emerald-800',
        \App\ContractStatus::Upcoming => 'bg-blue-100 text-blue-800',
        \App\ContractStatus::Draft => 'bg-slate-100 text-slate-700',
        \App\ContractStatus::Expired => 'bg-amber-100 text-amber-800',
        \App\ContractStatus::Terminated => 'bg-red-100 text-red-800',
        \App\ContractStatus::Superseded => 'bg-violet-100 text-violet-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>{{ $status->label() }}</span>
