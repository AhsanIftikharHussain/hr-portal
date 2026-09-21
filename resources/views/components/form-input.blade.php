@props(['label', 'name', 'type' => 'text', 'value' => null, 'autocomplete' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? null : old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200']) }}
    >
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
