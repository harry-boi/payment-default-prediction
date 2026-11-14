<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'salary_obligation_id',
        'amount_paid',
        'paid_at',
        'status',
        'delay_days',
        'reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_paid' => 'integer',
            'paid_at' => 'date',
            'status' => PaymentStatus::class,
            'delay_days' => 'integer',
        ];
    }

    /**
     * The salary obligation this payment satisfies.
     */
    public function salaryObligation(): BelongsTo
    {
        return $this->belongsTo(SalaryObligation::class);
    }

    /**
     * The employee who received this payment.
     */
    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            SalaryObligation::class,
            'id', // Foreign key on salary_obligations table...
            'id', // Foreign key on employees table...
            'salary_obligation_id', // Local key on payments table...
            'employee_id' // Local key on salary_obligations table...
        );
    }

    /**
     * Format amount paid in Naira with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return Money::format($this->amount_paid);
    }

    /**
     * Check whether this payment was made on time.
     */
    public function isOnTime(): bool
    {
        return $this->status === PaymentStatus::PaidOnTime;
    }

    /**
     * Check whether this payment was made late.
     */
    public function isLate(): bool
    {
        return $this->status === PaymentStatus::PaidLate;
    }
}
