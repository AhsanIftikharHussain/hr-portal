<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

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
        Gate::before(function (User $user, string $ability): ?bool {
            if ($ability === 'forceDelete') {
                return null;
            }

            return $user->hasRole(Role::SUPER_ADMIN) ? true : null;
        });

        Gate::define('access-admin', function (User $user): bool {
            return $user->hasRole(Role::HR_ADMIN);
        });

        Gate::define('hr-administration', function (User $user): bool {
            return $user->hasRole(Role::HR_ADMIN);
        });

        Gate::define('access-manager', function (User $user): bool {
            return $user->hasRole(Role::DEPARTMENT_MANAGER);
        });

        Gate::define('access-employee', function (User $user): bool {
            return $user->hasRole(Role::EMPLOYEE);
        });

        View::composer('components.layouts.admin', function (IlluminateView $view): void {
            $view->with(
                'unreadNotificationCount',
                auth()->check() ? auth()->user()->unreadNotifications()->count() : 0,
            );
        });
    }
}
