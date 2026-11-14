<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PaidOnTime = 'paid_on_time';
    case PaidLate = 'paid_late';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::PaidOnTime => 'Paid On Time',
            self::PaidLate => 'Paid Late',
        };
    }

    /**
     * Badge CSS classes for styling.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PaidOnTime => 'bg-green-100 text-green-800 border-green-200',
            self::PaidLate => 'bg-red-100 text-red-800 border-red-200 font-semibold',
        };
    }
}
