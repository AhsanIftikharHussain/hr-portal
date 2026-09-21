<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(Role::SUPER_ADMIN) ? true : null;
        });

        Gate::define('access-admin', function (User $user): bool {
            return $user->hasRole(Role::HR_ADMIN);
        });

        Gate::define('hr-administration', function (User $user): bool {
            return $user->hasRole(Role::HR_ADMIN);
        });
    }
}
