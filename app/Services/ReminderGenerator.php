<?php

namespace App\Services;

use App\Enums\ObligationStatus;
use App\Enums\ReminderType;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReminderGenerator
{
    /**
     * Process unpaid salary obligations and generate upcoming / overdue reminders (Algorithm 3.8.6).
     *
     * Rules:
     * - Days remaining = due_date minus today
     * - If 0 to window_days, create an upcoming reminder
     * - If due_date is before today (days remaining < 0), create an overdue reminder
     * - Unique index on (salary_obligation_id, type) ensures no duplicates appear
     *
     * @return int Number of new reminders created
     */
    public function generateForObligation(SalaryObligation $obligation, CarbonInterface|string|null $referenceDate = null): ?Reminder
    {
        // Reminders are only generated for unpaid obligations
        if ($obligation->isPaid()) {
            return null;
        }

        $today = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : Carbon::now()->startOfDay();
        $dueDate = Carbon::parse($obligation->due_date)->startOfDay();
        $daysRemaining = (int) $today->diffInDays($dueDate, false);
        $windowDays = (int) config('reminders.window_days', 7);

        $type = null;
        $message = null;
        $dateFormatted = $obligation->due_date->format('M d, Y');
        $employeeName = $obligation->employee->name;
        $amountFormatted = $obligation->formatted_amount;

        if ($daysRemaining < 0) {
            $type = ReminderType::Overdue;
            $message = "Salary for {$employeeName} ({$amountFormatted}) was due on {$dateFormatted} and is overdue.";
        } elseif ($daysRemaining <= $windowDays) {
            $type = ReminderType::Upcoming;
            $message = "Salary for {$employeeName} ({$amountFormatted}) is due on {$dateFormatted}.";
        } else {
            // Due date is beyond the reminder window
            return null;
        }

        return Reminder::firstOrCreate(
            [
                'salary_obligation_id' => $obligation->id,
                'type' => $type,
            ],
            [
                'user_id' => $obligation->employee->user_id,
                'message' => $message,
                'is_read' => false,
            ]
        );
    }

    /**
     * Generate reminders for all open obligations belonging to a specific user.
     *
     * @return int Count of newly created reminders
     */
    public function generateForUser(int|User $user, CarbonInterface|string|null $referenceDate = null): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $unpaidObligations = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->with('employee')
            ->get();

        $createdCount = 0;
        foreach ($unpaidObligations as $obligation) {
            $reminder = $this->generateForObligation($obligation, $referenceDate);
            if ($reminder && $reminder->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Generate reminders for all open obligations in the entire system (used by daily scheduler).
     *
     * @return int Count of newly created reminders
     */
    public function generateAll(CarbonInterface|string|null $referenceDate = null): int
    {
        $unpaidObligations = SalaryObligation::whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->with('employee')
            ->get();

        $createdCount = 0;
        foreach ($unpaidObligations as $obligation) {
            $reminder = $this->generateForObligation($obligation, $referenceDate);
            if ($reminder && $reminder->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }
}
