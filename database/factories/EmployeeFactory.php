<?php

namespace Database\Factories;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Gender;
use App\MaritalStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'national_id' => 'TEST-NIC-'.fake()->unique()->numerify('######'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'personal_email' => fake()->unique()->userName().'@example.test',
            'official_email' => fake()->unique()->userName().'@company.example.test',
            'contact_number' => '+92 300 '.fake()->numerify('#######'),
            'alternate_contact_number' => null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'department_id' => Department::factory(),
            'job_title_id' => JobTitle::factory(),
            'reporting_manager_id' => null,
            'user_id' => null,
            'employment_type' => EmploymentType::Permanent,
            'employment_status' => EmploymentStatus::Active,
            'joining_date' => fake()->dateTimeBetween('-8 years', '-1 month')->format('Y-m-d'),
            'confirmation_date' => null,
            'work_location' => 'Fictional Main Office',
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_relationship' => fake()->randomElement(['Parent', 'Sibling', 'Spouse', 'Friend']),
            'emergency_contact_number' => '+92 311 '.fake()->numerify('#######'),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['deleted_at' => now()]);
    }
}
