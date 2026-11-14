<?php

namespace App\Policies;

use App\Models\SalaryObligation;
use App\Models\User;

class SalaryObligationPolicy
{
    /**
     * Determine whether the user can view any salary obligations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the salary obligation.
     */
    public function view(User $user, SalaryObligation $salaryObligation): bool
    {
        return $salaryObligation->employee->user_id === $user->id;
    }

    /**
     * Determine whether the user can create salary obligations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the salary obligation.
     * Paid obligations are immutable.
     */
    public function update(User $user, SalaryObligation $salaryObligation): bool
    {
        return $salaryObligation->employee->user_id === $user->id && ! $salaryObligation->isPaid();
    }

    /**
     * Hard delete is prohibited to preserve financial history.
     */
    public function delete(User $user, SalaryObligation $salaryObligation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can record payment for this obligation.
     */
    public function pay(User $user, SalaryObligation $salaryObligation): bool
    {
        return $salaryObligation->employee->user_id === $user->id && ! $salaryObligation->isPaid();
    }
}
