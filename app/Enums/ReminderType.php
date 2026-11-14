<?php

namespace App\Enums;

enum ReminderType: string
{
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming Reminder',
            self::Overdue => 'Overdue Alert',
        };
    }

    /**
     * Badge CSS classes.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Upcoming => 'bg-amber-100 text-amber-800 border-amber-200',
            self::Overdue => 'bg-red-100 text-red-800 border-red-200 font-semibold',
        };
    }
}
