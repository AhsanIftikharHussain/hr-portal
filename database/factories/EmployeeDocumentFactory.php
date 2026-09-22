<?php

namespace Database\Factories;

use App\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type' => EmployeeDocumentType::CvResume,
            'title' => 'Curriculum Vitae',
            'description' => null,
            'document_date' => now()->subMonth()->toDateString(),
            'file_path' => 'employees/1/documents/example.pdf',
            'original_filename' => 'curriculum-vitae.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => User::factory(),
        ];
    }
}
