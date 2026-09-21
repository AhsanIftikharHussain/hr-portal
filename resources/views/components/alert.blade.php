@props(['type' => 'info'])

<div @class([
    'rounded-lg border px-4 py-3 text-sm',
    'border-emerald-200 bg-emerald-50 text-emerald-800' => $type === 'success',
    'border-red-200 bg-red-50 text-red-800' => $type === 'error',
    'border-slate-200 bg-slate-50 text-slate-700' => ! in_array($type, ['success', 'error'], true),
]) role="status" {{ $attributes }}>
    {{ $slot }}
</div>
