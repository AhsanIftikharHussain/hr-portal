<x-card title="Attendance Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form-select label="Employee *" name="employee_id" :value="$attendanceRecord->employee_id" :options="$employees->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' · '.$employee->employee_code.($employee->trashed() ? ' · Archived' : '')])" required />
        <x-form-input label="Attendance Date *" name="attendance_date" type="date" :value="$attendanceRecord->attendance_date?->format('Y-m-d')" required />
        <x-form-select label="Status *" name="status" :value="$attendanceRecord->status?->value" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" required />
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Times use the application timezone: <strong>{{ config('app.timezone') }}</strong>.</div>
        <x-form-input label="Check In" name="check_in_at" type="datetime-local" :value="$attendanceRecord->check_in_at?->format('Y-m-d\TH:i')" />
        <x-form-input label="Check Out" name="check_out_at" type="datetime-local" :value="$attendanceRecord->check_out_at?->format('Y-m-d\TH:i')" />
        <div class="sm:col-span-2"><x-form-textarea label="Notes" name="notes" :value="$attendanceRecord->notes" rows="4" /></div>
    </div>
    <p class="mt-5 text-sm text-slate-500">Times are optional. Absent and Leave records must not include times. Working duration is raw elapsed time only; breaks, schedules, overtime, and payroll rules are not applied.</p>
</x-card>
