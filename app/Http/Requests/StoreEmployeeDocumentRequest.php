<?php

namespace App\Http\Requests;

use App\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ! $employee->trashed()
            && ($this->user()?->can('create', EmployeeDocument::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(EmployeeDocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document_date' => ['nullable', 'date'],
            'document_file' => [
                'required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png',
                'extensions:pdf,doc,docx,jpg,jpeg,png', 'max:10240',
            ],
        ];
    }
}
