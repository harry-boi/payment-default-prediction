<?php

namespace Database\Factories;

use App\Enums\RiskLevel;
use App\Models\RiskAssessment;
use App\Models\SalaryObligation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskAssessment>
 */
class RiskAssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 85);
        $level = RiskLevel::fromScore($score);

        return [
            'salary_obligation_id' => SalaryObligation::factory(),
            'score' => $score,
            'risk_level' => $level,
            'late_rate' => 0.00,
            'overdue_count' => 0,
            'outstanding_ratio' => 0.00,
            'due_proximity_points' => 0,
            'factors' => [
                'a_points' => 0,
                'b_points' => 0,
                'c_points' => 0,
                'd_points' => 0,
                'no_history' => true,
                'sentences' => [
                    'No history yet for this employee: 0 points',
                    'No other overdue obligations for this employer: 0 points',
                    'No outstanding overdue balance: 0 points',
                    'Due date is more than 7 days away: 0 points',
                ],
            ],
        ];
    }

    /**
     * Set specific risk level.
     */
    public function level(RiskLevel $level): static
    {
        $score = match ($level) {
            RiskLevel::Low => 15,
            RiskLevel::Medium => 40,
            RiskLevel::High => 70,
        };

        return $this->state(fn () => [
            'score' => $score,
            'risk_level' => $level,
        ]);
    }
}
