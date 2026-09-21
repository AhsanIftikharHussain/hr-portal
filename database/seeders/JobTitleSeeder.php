<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Software Engineer', 'Product Designer', 'HR Officer', 'Accountant', 'General Manager'] as $name) {
            JobTitle::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
