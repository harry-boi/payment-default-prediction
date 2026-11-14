<?php

namespace App\Services;

use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\RiskLevel;
use App\Models\Payment;
use App\Models\RiskAssessment;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RiskAssessmentService
{
    /**
     * Calculate and record payment default risk for an open salary obligation (Algorithm 3.8.7).
     * Rule 1: Paid obligations keep their last assessment and are not re-assessed.
     * Rule 4: A new row is added only when the result differs from the latest row for that obligation.
     */
    public function assess(SalaryObligation $obligation, CarbonInterface|string|null $referenceDate = null, bool $force = false): ?RiskAssessment
    {
        // 1. Paid obligations are immutable; return latest assessment if present
        if ($obligation->isPaid()) {
            return $obligation->latestRiskAssessment;
        }

        $today = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : Carbon::now()->startOfDay();
        $employee = $obligation->employee;
        $userId = $employee->user_id;
        $currentAmount = (int) $obligation->amount;

        // --- Component A: Historical late-payment rate (Employee-level) ---
        $completedPayments = Payment::whereHas('salaryObligation', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->get();

        $completedCount = $completedPayments->count();
        if ($completedCount === 0) {
            $noHistory = true;
            $lateRate = 0.00;
            $aPoints = (int) config('risk.component_a.no_history_points', 0);
            $aSentence = 'No history yet for this employee: 0 points';
        } else {
            $noHistory = false;
            $lateCount = $completedPayments->where('status', PaymentStatus::PaidLate)->count();
            $lateRate = round(($lateCount / $completedCount) * 100, 2);

            $tier1 = config('risk.component_a.tier_1.threshold', 10);
            $tier2 = config('risk.component_a.tier_2.threshold', 30);
            $tier3 = config('risk.component_a.tier_3.threshold', 50);

            if ($lateRate <= $tier1) {
                $aPoints = (int) config('risk.component_a.tier_1.points', 0);
            } elseif ($lateRate <= $tier2) {
                $aPoints = (int) config('risk.component_a.tier_2.points', 10);
            } elseif ($lateRate <= $tier3) {
                $aPoints = (int) config('risk.component_a.tier_3.points', 20);
            } else {
                $aPoints = (int) config('risk.component_a.tier_4.points', 30);
            }

            $aSentence = "Employee paid late in {$lateCount} of {$completedCount} payments ({$lateRate}%): {$aPoints} points";
        }

        // --- Component B: Currently overdue obligations (Employer-level, excluding this obligation) ---
        $otherOverdueQuery = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->where('status', ObligationStatus::Overdue)
            ->where('id', '!=', $obligation->id);

        $overdueCount = $otherOverdueQuery->count();

        if ($overdueCount === 0) {
            $bPoints = (int) config('risk.component_b.zero.points', 0);
            $bSentence = 'No other overdue obligations for this employer: 0 points';
        } elseif ($overdueCount === 1) {
            $bPoints = (int) config('risk.component_b.one.points', 15);
            $bSentence = 'Employer has 1 other overdue obligation: 15 points';
        } else {
            $bPoints = (int) config('risk.component_b.multiple.points', 25);
            $bSentence = "Employer has {$overdueCount} other overdue obligations: 25 points";
        }

        // --- Component C: Outstanding ratio (Employer-level, integer arithmetic) ---
        $outstandingAmount = (int) $otherOverdueQuery->sum('amount'); // in kobo

        if ($outstandingAmount === 0 || $currentAmount === 0) {
            $outstandingRatio = 0.00;
            $cPoints = (int) config('risk.component_c.zero.points', 0);
            $cSentence = 'No outstanding overdue balance: 0 points';
        } else {
            $outstandingRatio = round(($outstandingAmount / $currentAmount) * 100, 2);

            // Integer arithmetic for comparisons: (outstanding * 100) vs (amount * threshold)
            if (($outstandingAmount * 100) < ($currentAmount * 25)) {
                $cPoints = (int) config('risk.component_c.tier_1.points', 5);
            } elseif (($outstandingAmount * 100) <= ($currentAmount * 50)) {
                $cPoints = (int) config('risk.component_c.tier_2.points', 10);
            } else {
                $cPoints = (int) config('risk.component_c.tier_3.points', 15);
            }

            $cSentence = "Outstanding overdue ratio is {$outstandingRatio}% (".Money::format($outstandingAmount).'): '."{$cPoints} points";
        }

        // --- Component D: Due proximity (Obligation-level) ---
        $dueDate = Carbon::parse($obligation->due_date)->startOfDay();
        $daysToDue = (int) $today->diffInDays($dueDate, false);

        if ($daysToDue > 7) {
            $dPoints = (int) config('risk.component_d.distant.points', 0);
            $dSentence = "Due date is {$daysToDue} days away: 0 points";
        } elseif ($daysToDue >= 4) {
            $dPoints = (int) config('risk.component_d.approaching.points', 5);
            $dSentence = "Due date is approaching in {$daysToDue} days: 5 points";
        } elseif ($daysToDue >= 1) {
            $dPoints = (int) config('risk.component_d.imminent.points', 10);
            $daysWord = $daysToDue === 1 ? 'day' : 'days';
            $dSentence = "Due date is imminent in {$daysToDue} {$daysWord}: 10 points";
        } else {
            $dPoints = (int) config('risk.component_d.due_or_overdue.points', 15);
            $dSentence = $daysToDue === 0
                ? 'Obligation is due today: 15 points'
                : 'Obligation is overdue by '.abs($daysToDue).' days: 15 points';
        }

        // --- Calculate Total Score & Classification ---
        $totalScore = $aPoints + $bPoints + $cPoints + $dPoints;
        $riskLevel = RiskLevel::fromScore($totalScore);

        $factors = [
            'a_points' => $aPoints,
            'b_points' => $bPoints,
            'c_points' => $cPoints,
            'd_points' => $dPoints,
            'no_history' => $noHistory,
            'sentences' => [
                $aSentence,
                $bSentence,
                $cSentence,
                $dSentence,
            ],
        ];

        // --- Rule 4: Save only if result differs from latest row or forced ---
        $latest = $obligation->relationLoaded('latestRiskAssessment') && $obligation->latestRiskAssessment !== null
            ? $obligation->latestRiskAssessment
            : $obligation->latestRiskAssessment()->first();

        if ($latest && ! $force) {
            $isSame = $latest->score === $totalScore
                && $latest->risk_level === $riskLevel
                && abs($latest->late_rate - $lateRate) < 0.001
                && $latest->overdue_count === $overdueCount
                && abs($latest->outstanding_ratio - $outstandingRatio) < 0.001
                && $latest->due_proximity_points === $dPoints;

            if ($isSame) {
                return $latest;
            }
        }

        $assessment = RiskAssessment::create([
            'salary_obligation_id' => $obligation->id,
            'score' => $totalScore,
            'risk_level' => $riskLevel,
            'late_rate' => $lateRate,
            'overdue_count' => $overdueCount,
            'outstanding_ratio' => $outstandingRatio,
            'due_proximity_points' => $dPoints,
            'factors' => $factors,
        ]);

        $obligation->setRelation('latestRiskAssessment', $assessment);

        return $assessment;
    }

    /**
     * Assess all open obligations for a specific user (Locked Decision #4).
     */
    public function assessOpenObligationsForUser(int|User $user, CarbonInterface|string|null $referenceDate = null): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $openObligations = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->get();

        $count = 0;
        foreach ($openObligations as $obligation) {
            $this->assess($obligation, $referenceDate);
            $count++;
        }

        return $count;
    }

    /**
     * Assess all open obligations across all users (for daily scheduled processing).
     */
    public function assessAllOpen(CarbonInterface|string|null $referenceDate = null): int
    {
        $openObligations = SalaryObligation::whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->get();

        $count = 0;
        foreach ($openObligations as $obligation) {
            $this->assess($obligation, $referenceDate);
            $count++;
        }

        return $count;
    }
}
