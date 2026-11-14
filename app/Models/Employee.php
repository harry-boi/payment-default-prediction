<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryFrequency;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'job_title',
        'salary_amount',
        'salary_frequency',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_amount' => 'integer',
            'salary_frequency' => SalaryFrequency::class,
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * The income payer who owns this employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The salary obligations created for this employee.
     */
    public function salaryObligations(): HasMany
    {
        return $this->hasMany(SalaryObligation::class);
    }

    /**
     * Format salary amount in Naira with currency symbol.
     */
    public function getFormattedSalaryAttribute(): string
    {
        return Money::format($this->salary_amount);
    }

    /**
     * Get the salary amount expressed in Naira for input fields.
     */
    public function getSalaryInNairaAttribute(): string
    {
        $naira = intdiv($this->salary_amount, 100);
        $kobo = $this->salary_amount % 100;

        return $kobo > 0
            ? sprintf('%d.%02d', $naira, $kobo)
            : (string) $naira;
    }

    /**
     * Determine whether the employee is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }
}
