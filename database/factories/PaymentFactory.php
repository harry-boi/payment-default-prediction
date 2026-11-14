<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SalaryObligation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salary_obligation_id' => SalaryObligation::factory(),
            'amount_paid' => fn (array $attributes) => SalaryObligation::find($attributes['salary_obligation_id'])?->amount ?? 5000000,
            'paid_at' => now()->format('Y-m-d'),
            'status' => PaymentStatus::PaidOnTime,
            'delay_days' => 0,
            'reference' => 'PAY-'.strtoupper(fake()->bothify('##??####')),
        ];
    }

    /**
     * Indicate that the payment was on time.
     */
    public function onTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PaidOnTime,
            'delay_days' => 0,
        ]);
    }

    /**
     * Indicate that the payment was made late.
     */
    public function late(int $delayDays = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PaidLate,
            'delay_days' => $delayDays,
        ]);
    }
}
