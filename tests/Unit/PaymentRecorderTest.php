<?php

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('recorder rejects already paid obligation', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'status' => ObligationStatus::Paid,
    ]);

    $recorder = new PaymentRecorder;

    expect(fn () => $recorder->record($obligation, [
        'paid_at' => '2026-10-31',
    ]))->toThrow(InvalidArgumentException::class, 'This salary obligation has already been paid.');
});

test('recorder rejects mismatched amount', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'status' => ObligationStatus::Upcoming,
    ]);

    $recorder = new PaymentRecorder;

    expect(fn () => $recorder->record($obligation, [
        'amount_paid' => 4000000,
        'paid_at' => '2026-10-31',
    ]))->toThrow(InvalidArgumentException::class, 'The payment amount must equal the obligation amount exactly.');
});

test('recorder correctly marks on-time payment when paid before or on due date', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'salary_frequency' => SalaryFrequency::Monthly,
        'status' => EmployeeStatus::Active,
    ]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $recorder = new PaymentRecorder;
    $payment = $recorder->record($obligation, [
        'paid_at' => '2026-10-28',
    ]);

    expect($payment->status)->toBe(PaymentStatus::PaidOnTime)
        ->and($payment->delay_days)->toBe(0)
        ->and($obligation->fresh()->status)->toBe(ObligationStatus::Paid);
});

test('recorder correctly marks late payment and calculates delay days', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'salary_frequency' => SalaryFrequency::Monthly,
        'status' => EmployeeStatus::Active,
    ]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Overdue,
    ]);

    $recorder = new PaymentRecorder;
    $payment = $recorder->record($obligation, [
        'paid_at' => '2026-10-27',
    ]);

    expect($payment->status)->toBe(PaymentStatus::PaidLate)
        ->and($payment->delay_days)->toBe(7)
        ->and($obligation->fresh()->status)->toBe(ObligationStatus::Paid);
});
