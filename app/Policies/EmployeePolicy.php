<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $employee->user_id === $user->id;
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $employee->user_id === $user->id;
    }

    /**
     * Determine whether the user can toggle the active status of the employee.
     */
    public function toggleStatus(User $user, Employee $employee): bool
    {
        return $employee->user_id === $user->id;
    }

    /**
     * Hard delete is prohibited to preserve historical records.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return false;
    }

    /**
     * Restore is not allowed.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return false;
    }

    /**
     * Force delete is not allowed.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }
}
