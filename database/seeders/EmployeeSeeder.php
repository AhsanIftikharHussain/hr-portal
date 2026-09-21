<?php

namespace Database\Seeders;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::query()->get();
        $jobTitles = JobTitle::query()->get();

        foreach (range(1, 18) as $sequence) {
            $employeeCode = sprintf('DEMO-%04d', $sequence);

            Employee::query()->withTrashed()->updateOrCreate(
                ['employee_code' => $employeeCode],
                [
                    'full_name' => sprintf('Demo Employee %02d', $sequence),
                    'national_id' => sprintf('TEST-NIC-%04d', $sequence),
                    'personal_email' => sprintf('demo.employee.%02d@example.test', $sequence),
                    'official_email' => sprintf('demo.employee.%02d@company.example.test', $sequence),
                    'contact_number' => sprintf('+92 300 000%04d', $sequence),
                    'address' => sprintf('%d Fictional Street', $sequence),
                    'city' => 'Demo City',
                    'department_id' => $departments[($sequence - 1) % $departments->count()]->id,
                    'job_title_id' => $jobTitles[($sequence - 1) % $jobTitles->count()]->id,
                    'employment_type' => EmploymentType::Permanent,
                    'employment_status' => EmploymentStatus::Active,
                    'joining_date' => '2026-01-15',
                    'work_location' => 'Fictional Main Office',
                    'emergency_contact_name' => sprintf('Demo Contact %02d', $sequence),
                    'emergency_contact_relationship' => 'Test Contact',
                    'emergency_contact_number' => sprintf('+92 311 000%04d', $sequence),
                ],
            );
        }
    }
}
