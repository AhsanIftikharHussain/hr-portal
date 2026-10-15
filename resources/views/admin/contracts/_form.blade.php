@php
    $typeOptions = collect($contractTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()]);
    $statusOptions = collect($contractStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $employeeOptions = $employees->mapWithKeys(fn ($employee) => [$employee->id => $employee->employee_code.' — '.$employee->full_name]);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    @if ($contract->exists)
        <div class="sm:col-span-2">
            <p class="text-sm font-medium text-slate-700">Employee</p>
            <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-900">{{ $contract->employee->employee_code }} — {{ $contract->employee->full_name }}</p>
        </div>
    @else
        <x-form-select label="Employee" name="employee_id" :options="$employeeOptions" :value="$contract->employee_id" required />
    @endif
    <x-form-select label="Contract Type" name="contract_type" :options="$typeOptions" :value="$contract->contract_type?->value" required />
    <x-form-input label="Start Date" name="start_date" type="date" :value="$contract->start_date?->toDateString()" required />
    <x-form-input label="End Date" name="end_date" type="date" :value="$contract->end_date?->toDateString()" />
    <x-form-select label="Status" name="status" :options="$statusOptions" :value="$contract->status?->value" required />
    <x-form-input label="Notice Period" name="notice_period" :value="$contract->notice_period" maxlength="100" placeholder="e.g. 30 days" />
    <x-form-textarea label="Notes" name="notes" :value="$contract->notes" maxlength="5000" rows="4" class="sm:col-span-2" />
    <div class="sm:col-span-2">
        @if ($contract->exists)
            <p class="block text-sm font-medium text-slate-700">Contract PDF</p>
            <p class="mt-1.5 text-sm text-slate-700">Current file: {{ $contract->original_filename }}</p>
            <p class="mt-1 text-xs text-slate-500">To upload a revised contract, create a new contract record. The existing private file will be retained.</p>
        @else
            <label for="contract_file" class="block text-sm font-medium text-slate-700">Contract PDF</label>
            <input id="contract_file" name="contract_file" type="file" accept="application/pdf,.pdf" required class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold" />
            <p class="mt-1.5 text-xs text-slate-500">PDF only, maximum 10 MB. Files are stored privately.</p>
            @error('contract_file')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        @endif
    </div>
</div>
