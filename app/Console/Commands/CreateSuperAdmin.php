<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-super-admin {email? : The administrator email address} {--name= : The administrator name}')]
#[Description('Securely create the first Super Admin account')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Name')));
        $email = mb_strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email address'))));
        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $role = Role::query()->firstOrCreate(
                ['slug' => Role::SUPER_ADMIN],
                ['name' => 'Super Admin'],
            );

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $user->roles()->attach($role);
        });

        $this->info('Super Admin account created successfully.');

        return self::SUCCESS;
    }
}
