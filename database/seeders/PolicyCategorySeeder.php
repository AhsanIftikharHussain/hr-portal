<?php

namespace Database\Seeders;

use App\Models\PolicyCategory;
use Illuminate\Database\Seeder;

class PolicyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'General Office Information',
            'Attendance',
            'Leave',
            'Code of Conduct',
            'IT / Information Security',
            'Work From Home',
            'Expenses',
            'Employee Benefits',
            'Health & Safety',
            'Other',
        ];

        foreach ($categories as $name) {
            PolicyCategory::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
