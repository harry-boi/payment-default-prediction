<?php

namespace App\Enums;

enum SalaryFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    /**
     * Get a human-readable display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Biweekly => 'Biweekly',
            self::Monthly => 'Monthly',
        };
    }
}
