<?php

namespace App\Services;

use App\Enums\ObligationStatus;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ObligationStatusSync
{
    /**
     * Determine the status of an unpaid obligation based on its due date relative to today.
     * Algorithm 3.8.4:
     * - A new obligation is upcoming if its due date is today or later, and overdue if its due date has already passed.
     */
    public static function determineStatus(CarbonInterface|string $dueDate, ?CarbonInterface $today = null): ObligationStatus
    {
        $todayDate = $today ? Carbon::parse($today)->startOfDay() : Carbon::now()->startOfDay();
        $due = Carbon::parse($dueDate)->startOfDay();

        return $due->lt($todayDate) ? ObligationStatus::Overdue : ObligationStatus::Upcoming;
    }

    /**
     * Sync unpaid obligation statuses for a single user's employees.
     * Paid obligations are final and never change.
     *
     * @return int Number of obligations updated
     */
    public static function syncForUser(User $user, ?CarbonInterface $today = null): int
    {
        $todayDate = $today ? Carbon::parse($today)->startOfDay() : Carbon::now()->startOfDay();

        // 1. Unpaid obligations due before today become overdue
        $markedOverdue = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Upcoming)
            ->whereDate('due_date', '<', $todayDate)
            ->update(['status' => ObligationStatus::Overdue]);

        // 2. Unpaid obligations due today or later become upcoming
        $markedUpcoming = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Overdue)
            ->whereDate('due_date', '>=', $todayDate)
            ->update(['status' => ObligationStatus::Upcoming]);

        return $markedOverdue + $markedUpcoming;
    }

    /**
     * Sync unpaid obligation statuses for all users (used by scheduled bills:process-daily).
     *
     * @return int Number of obligations updated
     */
    public static function syncAll(?CarbonInterface $today = null): int
    {
        $todayDate = $today ? Carbon::parse($today)->startOfDay() : Carbon::now()->startOfDay();

        $markedOverdue = SalaryObligation::where('status', ObligationStatus::Upcoming)
            ->whereDate('due_date', '<', $todayDate)
            ->update(['status' => ObligationStatus::Overdue]);

        $markedUpcoming = SalaryObligation::where('status', ObligationStatus::Overdue)
            ->whereDate('due_date', '>=', $todayDate)
            ->update(['status' => ObligationStatus::Upcoming]);

        return $markedOverdue + $markedUpcoming;
    }
}
