<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Development', 'Design', 'HR', 'Accounts', 'Management'] as $name) {
            Department::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
