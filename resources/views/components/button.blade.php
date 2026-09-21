@props(['type' => 'submit'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-portal-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
