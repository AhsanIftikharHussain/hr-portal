@props([
    'label',
    'name',
    'value' => null,
    'contentFormat' => \App\PolicyContentFormat::PlainText,
    'required' => false,
])

@php
    $fieldValue = old($name, $value ?? '');
    $format = old($name) !== null
        ? \App\PolicyContentFormat::Html->value
        : ($contentFormat instanceof \App\PolicyContentFormat ? $contentFormat->value : $contentFormat);
@endphp

<div data-rich-text-editor data-content-format="{{ $format }}">
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif
    </label>

    <div class="rich-text-editor mt-1.5">
        <div data-rich-text-toolbar class="rich-text-toolbar hidden" role="toolbar" aria-label="Policy formatting tools">
            <label class="sr-only" for="{{ $name }}-block-type">Text style</label>
            <select id="{{ $name }}-block-type" data-editor-block-type class="rich-text-select" title="Text style">
                <option value="paragraph">Paragraph</option>
                <option value="heading-1">Heading 1</option>
                <option value="heading-2">Heading 2</option>
                <option value="heading-3">Heading 3</option>
            </select>

            <span class="rich-text-divider" aria-hidden="true"></span>
            <button type="button" data-editor-command="bold" class="rich-text-button font-bold" title="Bold (Ctrl+B)" aria-label="Bold" aria-pressed="false">B</button>
            <button type="button" data-editor-command="italic" class="rich-text-button italic" title="Italic (Ctrl+I)" aria-label="Italic" aria-pressed="false">I</button>
            <button type="button" data-editor-command="underline" class="rich-text-button underline" title="Underline (Ctrl+U)" aria-label="Underline" aria-pressed="false">U</button>

            <span class="rich-text-divider" aria-hidden="true"></span>
            <button type="button" data-editor-command="bullet-list" class="rich-text-button rich-text-button-wide" title="Bullet list" aria-label="Bullet list" aria-pressed="false">• List</button>
            <button type="button" data-editor-command="ordered-list" class="rich-text-button rich-text-button-wide" title="Numbered list" aria-label="Numbered list" aria-pressed="false">1. List</button>
            <button type="button" data-editor-command="blockquote" class="rich-text-button rich-text-button-wide" title="Quote" aria-label="Quote" aria-pressed="false">Quote</button>

            <span class="rich-text-divider" aria-hidden="true"></span>
            <button type="button" data-editor-command="link" class="rich-text-button rich-text-button-wide" title="Add or edit link" aria-label="Add or edit link" aria-pressed="false">Link</button>
            <button type="button" data-editor-command="horizontal-rule" class="rich-text-button rich-text-button-wide" title="Insert divider" aria-label="Insert divider">Divider</button>
            <button type="button" data-editor-command="clear-formatting" class="rich-text-button rich-text-button-wide" title="Clear formatting" aria-label="Clear formatting">Clear</button>

            <span class="rich-text-divider" aria-hidden="true"></span>
            <button type="button" data-editor-command="undo" class="rich-text-button rich-text-button-wide" title="Undo (Ctrl+Z)" aria-label="Undo">Undo</button>
            <button type="button" data-editor-command="redo" class="rich-text-button rich-text-button-wide" title="Redo (Ctrl+Shift+Z)" aria-label="Redo">Redo</button>
        </div>

        <div data-rich-text-surface class="policy-content hidden" aria-label="{{ $label }}"></div>
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            maxlength="100000"
            rows="16"
            data-rich-text-input
            class="block min-h-80 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >{{ $fieldValue }}</textarea>
    </div>

    <p class="mt-1.5 text-xs text-slate-500">Use the toolbar to add headings, lists, emphasis, links and document structure. Pasted formatting is cleaned automatically.</p>
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
