<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            Role::SUPER_ADMIN => 'Super Admin',
            Role::HR_ADMIN => 'HR Admin',
            Role::EMPLOYEE => 'Employee',
        ] as $slug => $name) {
            Role::query()->updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
