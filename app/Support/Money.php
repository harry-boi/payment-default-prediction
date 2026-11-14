<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    /**
     * Convert a Naira amount string into integer Kobo.
     * Accepts "45000", "45,000", "45000.50", "45,000.50".
     * Validates with regex (up to two decimal places) and uses integer arithmetic.
     * Never multiplies a float.
     *
     * @throws InvalidArgumentException
     */
    public static function toKobo(string|int|float $naira): int
    {
        $raw = trim((string) $naira);
        $cleaned = str_replace(',', '', $raw);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $cleaned)) {
            throw new InvalidArgumentException("Invalid Naira amount format: [{$naira}]");
        }

        if (str_contains($cleaned, '.')) {
            [$nairaPart, $koboPart] = explode('.', $cleaned, 2);
            $koboPart = str_pad($koboPart, 2, '0', STR_PAD_RIGHT);

            return ((int) $nairaPart * 100) + (int) $koboPart;
        }

        return (int) $cleaned * 100;
    }

    /**
     * Format integer Kobo into a human-readable Naira currency string.
     * Example: 4500050 -> ₦45,000.50.
     */
    public static function format(int $kobo): string
    {
        $isNegative = $kobo < 0;
        $abs = abs($kobo);

        $naira = intdiv($abs, 100);
        $remainder = $abs % 100;

        $nairaFormatted = number_format($naira);
        $koboFormatted = str_pad((string) $remainder, 2, '0', STR_PAD_LEFT);

        return ($isNegative ? '-' : '').'₦'.$nairaFormatted.'.'.$koboFormatted;
    }
}
