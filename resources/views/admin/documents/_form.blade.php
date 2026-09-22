@php($typeOptions = collect($documentTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()]))

<div class="grid gap-5 sm:grid-cols-2">
    <x-form-select label="Document Type" name="document_type" :options="$typeOptions" :value="$document->document_type?->value" required />
    <x-form-input label="Title" name="title" :value="$document->title" maxlength="255" required />
    <x-form-input label="Document Date" name="document_date" type="date" :value="$document->document_date?->toDateString()" />
    <div>
        <label for="document_file" class="block text-sm font-medium text-slate-700">File{{ $document->exists ? ' (optional replacement)' : '' }}</label>
        <input id="document_file" name="document_file" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" @required(! $document->exists) class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold" />
        <p class="mt-1.5 text-xs text-slate-500">PDF, DOC, DOCX, JPG, JPEG, or PNG; maximum 10 MB.</p>
        @if ($document->exists)<p class="mt-1 text-xs text-slate-500">Current file: {{ $document->original_filename }}. Uploading a replacement removes the superseded physical file.</p>@endif
        @error('document_file')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2"><x-form-textarea label="Description" name="description" :value="$document->description" maxlength="5000" rows="4" /></div>
</div>
