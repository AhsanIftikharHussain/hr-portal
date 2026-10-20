<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::HR_ADMIN) || $user->managedDepartmentId() !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole(Role::HR_ADMIN) || $this->managesEmployee($user, $leaveRequest);
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
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasRole(Role::HR_ADMIN)) {
            return true;
        }

        return $this->managesEmployee($user, $leaveRequest)
            && $user->employee()->value('id') !== $leaveRequest->employee_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    private function managesEmployee(User $user, LeaveRequest $leaveRequest): bool
    {
        $departmentId = $user->managedDepartmentId();

        return $departmentId !== null
            && $leaveRequest->employee()
                ->where('department_id', $departmentId)
                ->whereNull('employees.deleted_at')
                ->exists();
    }
}
