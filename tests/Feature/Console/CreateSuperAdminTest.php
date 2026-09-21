<?php

namespace Tests\Feature\Console;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreateSuperAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_creates_super_admin_with_valid_input(): void
    {
        $this->artisan('app:create-super-admin', [
            'email' => 'first.admin@example.test',
            '--name' => 'First Admin',
        ])
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->expectsOutput('Super Admin account created successfully.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'first.admin@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::SUPER_ADMIN));
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $this->artisan('app:create-super-admin', [
            'email' => 'existing@example.test',
            '--name' => 'Existing User',
        ])
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->expectsOutput('The email has already been taken.')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('email', 'existing@example.test')->count());
    }

    public function test_command_rejects_weak_password(): void
    {
        $this->artisan('app:create-super-admin', [
            'email' => 'first.admin@example.test',
            '--name' => 'First Admin',
        ])
            ->expectsQuestion('Password', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'first.admin@example.test']);
    }
}
