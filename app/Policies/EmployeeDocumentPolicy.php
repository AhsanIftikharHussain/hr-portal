<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;

class EmployeeDocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmployeeDocument $employeeDocument): bool
    {
        return false;
    }

    public function download(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->hasRole(Role::HR_ADMIN);
    }
}
