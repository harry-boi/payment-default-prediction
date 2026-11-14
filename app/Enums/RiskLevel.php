<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low Risk',
            self::Medium => 'Medium Risk',
            self::High => 'High Risk',
        };
    }

    /**
     * Badge CSS classes for Tailwind.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-green-100 text-green-800 border-green-200',
            self::Medium => 'bg-amber-100 text-amber-800 border-amber-200',
            self::High => 'bg-red-100 text-red-800 border-red-200 font-semibold',
        };
    }

    /**
     * Determine risk level from an integer score (0 to 85).
     */
    public static function fromScore(int $score): self
    {
        $lowMax = (int) config('risk.classification.low.max', 28);
        $medMax = (int) config('risk.classification.medium.max', 56);

        if ($score <= $lowMax) {
            return self::Low;
        }

        if ($score <= $medMax) {
            return self::Medium;
        }

        return self::High;
    }
}
