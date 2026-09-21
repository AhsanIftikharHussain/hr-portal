<?php

namespace App\Http\Requests;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Gender;
use App\MaritalStatus;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->employee()) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $employee = $this->employee();

        return [
            'employee_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9][A-Za-z0-9\/_-]*$/', Rule::unique('employees')->ignore($employee)],
            'full_name' => ['required', 'string', 'max:150'],
            'national_id' => ['nullable', 'string', 'max:50', Rule::unique('employees')->ignore($employee)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'personal_email' => ['nullable', 'email:rfc', 'max:254'],
            'official_email' => ['nullable', 'email:rfc', 'max:254', Rule::unique('employees')->ignore($employee)],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{7,30}$/'],
            'alternate_contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{7,30}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', Rule::exists('departments', 'id')->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $employee->department_id))],
            'job_title_id' => ['required', Rule::exists('job_titles', 'id')->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $employee->job_title_id))],
            'reporting_manager_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at'), Rule::notIn([$employee->id])],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'joining_date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'work_location' => ['nullable', 'string', 'max:150'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{7,30}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'national_id' => 'NIC',
            'department_id' => 'department',
            'job_title_id' => 'designation',
            'reporting_manager_id' => 'reporting manager',
        ];
    }

    private function employee(): Employee
    {
        return $this->route('employee');
    }
}
