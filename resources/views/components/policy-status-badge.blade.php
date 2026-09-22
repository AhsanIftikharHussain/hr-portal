@props(['status'])
@php($classes = match ($status) { \App\PolicyStatus::Draft => 'bg-slate-100 text-slate-700', \App\PolicyStatus::Published => 'bg-emerald-100 text-emerald-800', \App\PolicyStatus::Archived => 'bg-amber-100 text-amber-800' })
<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>{{ $status->label() }}</span>
