<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalaryFrequency;
use App\Models\Payment;
use App\Models\SalaryObligation;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class PaymentRecorder
{
    /**
     * Record a payment against a salary obligation (Algorithm 3.8.5).
     *
     * @param  array{paid_at: string, amount_paid?: int|string, reference?: ?string}  $data
     *
     * @throws InvalidArgumentException
     */
    public function record(SalaryObligation $obligation, array $data): Payment
    {
        return DB::transaction(function () use ($obligation, $data) {
            // 1. Reject the request if the obligation is already paid
            if ($obligation->isPaid()) {
                throw new InvalidArgumentException('This salary obligation has already been paid.');
            }

            // 2. Require amount_paid to equal the obligation amount
            $amountPaid = isset($data['amount_paid'])
                ? (is_int($data['amount_paid']) ? $data['amount_paid'] : Money::toKobo((string) $data['amount_paid']))
                : $obligation->amount;

            if ($amountPaid !== $obligation->amount) {
                throw new InvalidArgumentException('The payment amount must equal the obligation amount exactly.');
            }

            // 3. Determine status and delay days
            $paidAt = Carbon::parse($data['paid_at'])->startOfDay();
            $dueDate = Carbon::parse($obligation->due_date)->startOfDay();

            if ($paidAt->lessThanOrEqualTo($dueDate)) {
                $status = PaymentStatus::PaidOnTime;
                $delayDays = 0;
            } else {
                $status = PaymentStatus::PaidLate;
                $delayDays = (int) $dueDate->diffInDays($paidAt);
            }

            // 4. Save the payment and set the obligation status to paid
            $payment = Payment::create([
                'salary_obligation_id' => $obligation->id,
                'amount_paid' => $amountPaid,
                'paid_at' => $paidAt->format('Y-m-d'),
                'status' => $status,
                'delay_days' => $delayDays,
                'reference' => $data['reference'] ?? null,
            ]);

            $obligation->update(['status' => ObligationStatus::Paid]);

            // 5. Mark that obligation's reminders as read
            if (Schema::hasTable('reminders')) {
                $obligation->reminders()->update(['is_read' => true]);
            }

            // 6. If the employee is active, create the next obligation
            $employee = $obligation->employee;
            if ($employee && $employee->status === EmployeeStatus::Active) {
                $nextDueDate = match ($obligation->frequency) {
                    SalaryFrequency::Weekly => $dueDate->copy()->addWeek(),
                    SalaryFrequency::Biweekly => $dueDate->copy()->addWeeks(2),
                    SalaryFrequency::Monthly => $dueDate->copy()->addMonthNoOverflow(),
                };

                $nextPeriod = match ($obligation->frequency) {
                    SalaryFrequency::Monthly => $nextDueDate->format('F Y'),
                    SalaryFrequency::Weekly => 'Week '.$nextDueDate->isoWeek().' '.$nextDueDate->format('Y'),
                    SalaryFrequency::Biweekly => 'Bi-weekly '.$nextDueDate->format('M d, Y'),
                };

                $nextStatus = ObligationStatusSync::determineStatus($nextDueDate);

                SalaryObligation::create([
                    'employee_id' => $employee->id,
                    'amount' => $obligation->amount,
                    'due_date' => $nextDueDate->format('Y-m-d'),
                    'frequency' => $obligation->frequency,
                    'status' => $nextStatus,
                    'notes' => $obligation->notes,
                    'payment_period' => $nextPeriod,
                ]);
            }

            // 7. Run a risk assessment for every open obligation of this employer
            if (class_exists(RiskAssessmentService::class) && Schema::hasTable('risk_assessments')) {
                try {
                    app(RiskAssessmentService::class)->assessOpenObligationsForUser($employee->user_id);
                } catch (\Throwable $e) {
                    // Safe fallback if risk assessment engine is under construction
                }
            }

            return $payment;
        });
    }
}
