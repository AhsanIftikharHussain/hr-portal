<?php

namespace Database\Factories;

use App\ContractStatus;
use App\ContractType;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
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
            'contract_type' => ContractType::FixedTerm,
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => ContractStatus::Active,
            'notice_period' => '30 days',
            'notes' => null,
            'file_path' => 'employees/1/contracts/example.pdf',
            'original_filename' => 'employment-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => User::factory(),
        ];
    }
}
