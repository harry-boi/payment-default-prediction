<?php

namespace App\Models;

use App\Enums\ObligationStatus;
use App\Enums\SalaryFrequency;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalaryObligation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'amount',
        'due_date',
        'frequency',
        'status',
        'notes',
        'payment_period',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
            'frequency' => SalaryFrequency::class,
            'status' => ObligationStatus::class,
        ];
    }

    /**
     * The employee who owns this obligation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Payments made against this obligation.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * In-application reminders generated for this obligation.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Risk assessments calculated for this obligation.
     */
    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * The latest risk assessment recorded for this obligation.
     */
    public function latestRiskAssessment(): HasOne
    {
        return $this->hasOne(RiskAssessment::class)->latestOfMany();
    }

    /**
     * Format amount in Naira with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return Money::format($this->amount);
    }

    /**
     * Get amount in Naira as a numeric string for input forms.
     */
    public function getAmountInNairaAttribute(): string
    {
        $naira = intdiv($this->amount, 100);
        $kobo = $this->amount % 100;

        return $kobo > 0
            ? sprintf('%d.%02d', $naira, $kobo)
            : (string) $naira;
    }

    /**
     * Check if obligation is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === ObligationStatus::Paid;
    }

    /**
     * Check if obligation is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === ObligationStatus::Overdue;
    }

    /**
     * Check if obligation is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->status === ObligationStatus::Upcoming;
    }

    /**
     * Calculate remaining days until due date from a reference date (defaults to today).
     * Positive = days remaining in future; Negative = days past due.
     */
    public function daysRemaining(?CarbonInterface $referenceDate = null): int
    {
        $ref = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : Carbon::now()->startOfDay();
        $due = Carbon::parse($this->due_date)->startOfDay();

        return (int) $ref->diffInDays($due, false);
    }
}
