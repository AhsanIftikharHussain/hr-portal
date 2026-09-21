<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->warn('Development users were not created outside the local environment.');

            return;
        }

        $users = [
            ['name' => 'Super Admin Test User', 'email' => 'super.admin@example.test', 'role' => Role::SUPER_ADMIN],
            ['name' => 'HR Admin Test User', 'email' => 'hr.admin@example.test', 'role' => Role::HR_ADMIN],
            ['name' => 'Employee Test User', 'email' => 'employee@example.test', 'role' => Role::EMPLOYEE],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                ['name' => $userData['name'], 'password' => Hash::make('LocalPassword123!')],
            );

            $role = Role::query()->where('slug', $userData['role'])->firstOrFail();
            $user->roles()->sync([$role->id]);
        }
    }
}
