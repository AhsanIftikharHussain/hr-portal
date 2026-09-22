@props(['status'])

@php
    $classes = match ($status) {
        \App\AttendanceStatus::Present => 'bg-emerald-100 text-emerald-800',
        \App\AttendanceStatus::Absent => 'bg-red-100 text-red-800',
        \App\AttendanceStatus::Leave => 'bg-blue-100 text-blue-800',
        \App\AttendanceStatus::HalfDay => 'bg-violet-100 text-violet-800',
        \App\AttendanceStatus::Late => 'bg-amber-100 text-amber-800',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold '.$classes]) }}>{{ $status->label() }}</span>
