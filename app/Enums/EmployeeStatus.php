<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Get a human-readable display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
