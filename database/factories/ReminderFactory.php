<?php

namespace Database\Factories;

use App\Enums\ReminderType;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'salary_obligation_id' => SalaryObligation::factory(),
            'type' => ReminderType::Upcoming,
            'message' => 'Salary payment is due soon.',
            'is_read' => false,
        ];
    }

    /**
     * State for unread reminder.
     */
    public function unread(): static
    {
        return $this->state(fn () => [
            'is_read' => false,
        ]);
    }

    /**
     * State for read reminder.
     */
    public function read(): static
    {
        return $this->state(fn () => [
            'is_read' => true,
        ]);
    }

    /**
     * State for overdue alert.
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'type' => ReminderType::Overdue,
            'message' => 'Salary payment was due and is overdue.',
        ]);
    }

    /**
     * State for upcoming notification.
     */
    public function upcoming(): static
    {
        return $this->state(fn () => [
            'type' => ReminderType::Upcoming,
            'message' => 'Salary payment is due on scheduled date.',
        ]);
    }
}
