<?php

namespace App\Models;

use App\Enums\RiskLevel;
use Database\Factories\RiskAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RiskAssessment extends Model
{
    /** @use HasFactory<RiskAssessmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'salary_obligation_id',
        'score',
        'risk_level',
        'late_rate',
        'overdue_count',
        'outstanding_ratio',
        'due_proximity_points',
        'factors',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'risk_level' => RiskLevel::class,
            'late_rate' => 'float',
            'overdue_count' => 'integer',
            'outstanding_ratio' => 'float',
            'due_proximity_points' => 'integer',
            'factors' => 'array',
        ];
    }

    /**
     * The salary obligation evaluated by this assessment.
     */
    public function salaryObligation(): BelongsTo
    {
        return $this->belongsTo(SalaryObligation::class);
    }

    /**
     * The employee associated with this assessment.
     */
    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            SalaryObligation::class,
            'id', // Foreign key on salary_obligations table...
            'id', // Foreign key on employees table...
            'salary_obligation_id', // Local key on risk_assessments table...
            'employee_id' // Local key on salary_obligations table...
        );
    }

    /**
     * Check whether this assessment is classified as Low risk.
     */
    public function isLow(): bool
    {
        return $this->risk_level === RiskLevel::Low;
    }

    /**
     * Check whether this assessment is classified as Medium risk.
     */
    public function isMedium(): bool
    {
        return $this->risk_level === RiskLevel::Medium;
    }

    /**
     * Check whether this assessment is classified as High risk.
     */
    public function isHigh(): bool
    {
        return $this->risk_level === RiskLevel::High;
    }
}
