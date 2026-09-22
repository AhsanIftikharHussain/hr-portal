@props(['status'])

@php
    $classes = match ($status) {
        \App\LeaveRequestStatus::Approved => 'bg-emerald-100 text-emerald-800',
        \App\LeaveRequestStatus::Rejected => 'bg-red-100 text-red-800',
        \App\LeaveRequestStatus::Cancelled => 'bg-slate-200 text-slate-700',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold '.$classes]) }}>{{ $status->label() }}</span>
