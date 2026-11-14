<?php

namespace Database\Factories;

use App\Enums\ObligationStatus;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\SalaryObligation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryObligation>
 */
class SalaryObligationFactory extends Factory
{
    protected $model = SalaryObligation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = Carbon::now()->addDays(fake()->numberBetween(1, 14));

        return [
            'employee_id' => Employee::factory(),
            'amount' => fake()->numberBetween(30000, 150000) * 100, // kobo
            'due_date' => $dueDate->toDateString(),
            'frequency' => SalaryFrequency::Monthly,
            'status' => ObligationStatus::Upcoming,
            'notes' => fake()->optional()->sentence(),
            'payment_period' => $dueDate->format('F Y'),
        ];
    }

    /**
     * Indicate that the obligation is overdue.
     */
    public function overdue(): static
    {
        $dueDate = Carbon::now()->subDays(fake()->numberBetween(1, 10));

        return $this->state(fn (array $attributes) => [
            'due_date' => $dueDate->toDateString(),
            'status' => ObligationStatus::Overdue,
            'payment_period' => $dueDate->format('F Y'),
        ]);
    }

    /**
     * Indicate that the obligation is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObligationStatus::Paid,
        ]);
    }
}
