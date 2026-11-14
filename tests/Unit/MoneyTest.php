<?php

use App\Support\Money;

test('it converts naira string to kobo accurately without float multiplication', function () {
    expect(Money::toKobo('45000'))->toBe(4500000)
        ->and(Money::toKobo('45,000'))->toBe(4500000)
        ->and(Money::toKobo('45000.50'))->toBe(4500050)
        ->and(Money::toKobo('45,000.50'))->toBe(4500050)
        ->and(Money::toKobo('100.5'))->toBe(10050)
        ->and(Money::toKobo('0.05'))->toBe(5)
        ->and(Money::toKobo('0'))->toBe(0);
});

test('it rejects invalid currency string formats', function () {
    expect(fn () => Money::toKobo('abc'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::toKobo('45000.555'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::toKobo('-500'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::toKobo(''))->toThrow(InvalidArgumentException::class);
});

test('it formats integer kobo to naira with currency symbol and thousands separator', function () {
    expect(Money::format(4500050))->toBe('₦45,000.50')
        ->and(Money::format(4500000))->toBe('₦45,000.00')
        ->and(Money::format(5))->toBe('₦0.05')
        ->and(Money::format(0))->toBe('₦0.00')
        ->and(Money::format(100000000))->toBe('₦1,000,000.00');
});
