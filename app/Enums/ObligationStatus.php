<?php

namespace App\Enums;

enum ObligationStatus: string
{
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';
    case Paid = 'paid';

    /**
     * Get a human-readable display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Overdue => 'Overdue',
            self::Paid => 'Paid',
        };
    }
}
