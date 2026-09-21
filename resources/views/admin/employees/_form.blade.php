<div class="flex flex-col gap-6">
    <x-card title="Personal Information">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <x-form-input label="Employee Name *" name="full_name" :value="$employee->full_name" required />
            <x-form-input label="NIC" name="national_id" :value="$employee->national_id" />
            <x-form-input label="Date of Birth" name="date_of_birth" type="date" :value="$employee->date_of_birth?->format('Y-m-d')" />
            <x-form-select label="Gender" name="gender" :value="$employee->gender?->value" :options="collect($genders)->mapWithKeys(fn ($gender) => [$gender->value => $gender->label()])" />
            <x-form-select label="Marital Status" name="marital_status" :value="$employee->marital_status?->value" :options="collect($maritalStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" />
        </div>
    </x-card>

    <x-card title="Contact Information">
        <div class="grid gap-5 md:grid-cols-2">
            <x-form-input label="Personal Email" name="personal_email" type="email" :value="$employee->personal_email" />
            <x-form-input label="Official Email" name="official_email" type="email" :value="$employee->official_email" />
            <x-form-input label="Contact Number" name="contact_number" :value="$employee->contact_number" />
            <x-form-input label="Alternate Contact Number" name="alternate_contact_number" :value="$employee->alternate_contact_number" />
            <x-form-textarea label="Address" name="address" :value="$employee->address" rows="3" class="md:col-span-2" />
            <x-form-input label="City" name="city" :value="$employee->city" />
        </div>
    </x-card>

    <x-card title="Employment Information">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <x-form-input label="Employee Code *" name="employee_code" :value="$employee->employee_code" required />
            <x-form-select label="Department *" name="department_id" :value="$employee->department_id" :options="$departments->mapWithKeys(fn ($department) => [$department->id => $department->name.($department->is_active ? '' : ' (Inactive)')])" required />
            <x-form-select label="Designation *" name="job_title_id" :value="$employee->job_title_id" :options="$jobTitles->mapWithKeys(fn ($jobTitle) => [$jobTitle->id => $jobTitle->name.($jobTitle->is_active ? '' : ' (Inactive)')])" required />
            <x-form-select label="Reporting Manager" name="reporting_manager_id" :value="$employee->reporting_manager_id" :options="$managers->mapWithKeys(fn ($manager) => [$manager->id => $manager->full_name.' · '.$manager->employee_code])" placeholder="No reporting manager" />
            <x-form-select label="Employment Type *" name="employment_type" :value="$employee->employment_type?->value" :options="collect($employmentTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])" required />
            <x-form-select label="Employment Status *" name="employment_status" :value="$employee->employment_status?->value" :options="collect($employmentStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" required />
            <x-form-input label="Joining Date *" name="joining_date" type="date" :value="$employee->joining_date?->format('Y-m-d')" required />
            <x-form-input label="Confirmation Date" name="confirmation_date" type="date" :value="$employee->confirmation_date?->format('Y-m-d')" />
            <x-form-input label="Work Location" name="work_location" :value="$employee->work_location" />
        </div>
    </x-card>

    <x-card title="Emergency Contact">
        <div class="grid gap-5 md:grid-cols-3">
            <x-form-input label="Name" name="emergency_contact_name" :value="$employee->emergency_contact_name" />
            <x-form-input label="Relationship" name="emergency_contact_relationship" :value="$employee->emergency_contact_relationship" />
            <x-form-input label="Contact Number" name="emergency_contact_number" :value="$employee->emergency_contact_number" />
        </div>
    </x-card>

    <div class="flex flex-wrap justify-end gap-3">
        <a href="{{ $cancelUrl }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</div>
