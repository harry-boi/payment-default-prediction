<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'salary_amount' => fake()->numberBetween(30000, 150000) * 100, // stored in kobo
            'salary_frequency' => fake()->randomElement([
                SalaryFrequency::Weekly,
                SalaryFrequency::Biweekly,
                SalaryFrequency::Monthly,
            ]),
            'status' => EmployeeStatus::Active,
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Inactive,
        ]);
    }
}
