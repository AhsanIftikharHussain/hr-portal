<x-layouts.admin title="Add Leave Request">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <x-page-header title="Add Leave Request" description="Record an employee leave request for HR review." />

        <x-alert>Duration is calculated as inclusive calendar days. Weekends and public holidays are not excluded because work schedules and holiday calendars are outside this phase.</x-alert>

        <form method="POST" action="{{ route('admin.leave-requests.store') }}">
            @csrf
            <x-card title="Request Details">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-select label="Employee *" name="employee_id" :value="request('employee_id')" :options="$employees->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' · '.$employee->employee_code])" required />
                    <x-form-select label="Leave Type *" name="leave_type_id" :options="$leaveTypes->pluck('name', 'id')" required />
                    <x-form-input label="Start Date *" name="start_date" type="date" required />
                    <x-form-input label="End Date *" name="end_date" type="date" required />
                    <x-form-textarea label="Reason *" name="reason" rows="5" required class="sm:col-span-2" />
                </div>
                <p class="mt-5 text-sm text-slate-500">A leave type may indicate that supporting evidence is normally required, but document upload is intentionally not available in Phase 3.</p>
            </x-card>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Create Leave Request</x-button></div>
        </form>
    </div>
</x-layouts.admin>
