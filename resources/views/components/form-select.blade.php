@props(['label', 'name', 'options' => [], 'value' => null, 'placeholder' => 'Select an option'])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
