<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LeaveType::query()->firstOrCreate(
            ['code' => 'annual-leave'],
            ['name' => 'Annual Leave', 'is_active' => true, 'requires_attachment' => false, 'is_paid' => true],
        );

        LeaveType::query()->firstOrCreate(
            ['code' => 'sick-leave'],
            ['name' => 'Sick Leave', 'is_active' => true, 'requires_attachment' => false, 'is_paid' => true],
        );
    }
}
