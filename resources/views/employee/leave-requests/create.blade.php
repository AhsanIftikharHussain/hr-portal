<x-layouts.admin title="Request Leave">
    @if ($employee === null)
        <x-page-header title="Request Leave" description="Your employee record is not available." />
        <x-alert type="error" class="mt-6">Your account has not yet been linked to an employee record. Please contact HR.</x-alert>
    @else
        <div class="mx-auto flex max-w-3xl flex-col gap-6">
            <x-page-header title="Request Leave" description="Submit a leave request for HR or manager review." />
            <x-alert>Duration is calculated as inclusive calendar days. Weekends and public holidays are not excluded.</x-alert>
            <form method="POST" action="{{ route('employee.leave-requests.store') }}">
                @csrf
                <x-card title="Request Details">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form-select label="Leave Type *" name="leave_type_id" :options="$leaveTypes->mapWithKeys(fn ($leaveType) => [$leaveType->id => $leaveType->name.($leaveType->requires_attachment ? ' · Supporting evidence required' : '')])" required />
                        <div class="hidden sm:block"></div>
                        <x-form-input label="Start Date *" name="start_date" type="date" required />
                        <x-form-input label="End Date *" name="end_date" type="date" required />
                        <x-form-textarea label="Reason *" name="reason" rows="5" required class="sm:col-span-2" />
                    </div>
                    <p class="mt-5 text-sm text-slate-500">Some leave types indicate that supporting evidence is normally required. Document upload is not available in this phase; HR may contact you if evidence is needed.</p>
                </x-card>
                <div class="mt-6 flex justify-end gap-3"><a href="{{ route('employee.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a><x-button>Submit Leave Request</x-button></div>
            </form>
        </div>
    @endif
</x-layouts.admin>
