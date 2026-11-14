<?php

use App\Enums\ObligationStatus;
use App\Models\Employee;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\ObligationStatusSync;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it determines upcoming if due date is today or in the future', function () {
    Carbon::setTestNow('2026-10-28');

    expect(ObligationStatusSync::determineStatus('2026-10-28'))->toBe(ObligationStatus::Upcoming)
        ->and(ObligationStatusSync::determineStatus('2026-10-29'))->toBe(ObligationStatus::Upcoming)
        ->and(ObligationStatusSync::determineStatus('2026-11-04'))->toBe(ObligationStatus::Upcoming);
});

test('it determines overdue if due date has already passed', function () {
    Carbon::setTestNow('2026-10-28');

    expect(ObligationStatusSync::determineStatus('2026-10-27'))->toBe(ObligationStatus::Overdue)
        ->and(ObligationStatusSync::determineStatus('2026-10-20'))->toBe(ObligationStatus::Overdue);
});

test('syncForUser updates unpaid past-due obligations to overdue and leaves paid obligations unchanged', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Obligation 1: Past due date, status upcoming -> should become overdue
    $pastObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation 2: Future due date, status upcoming -> stays upcoming
    $futureObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation 3: Past due date, but already PAID -> must remain PAID
    $paidObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
        'status' => ObligationStatus::Paid,
    ]);

    ObligationStatusSync::syncForUser($user);

    expect($pastObligation->fresh()->status)->toBe(ObligationStatus::Overdue)
        ->and($futureObligation->fresh()->status)->toBe(ObligationStatus::Upcoming)
        ->and($paidObligation->fresh()->status)->toBe(ObligationStatus::Paid);
});

test('syncForUser scopes updates strictly to the given user', function () {
    Carbon::setTestNow('2026-10-28');

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeA = Employee::factory()->create(['user_id' => $userA->id]);
    $employeeB = Employee::factory()->create(['user_id' => $userB->id]);

    $obligationA = SalaryObligation::factory()->create([
        'employee_id' => $employeeA->id,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Upcoming,
    ]);

    $obligationB = SalaryObligation::factory()->create([
        'employee_id' => $employeeB->id,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Only sync for User A
    ObligationStatusSync::syncForUser($userA);

    expect($obligationA->fresh()->status)->toBe(ObligationStatus::Overdue)
        ->and($obligationB->fresh()->status)->toBe(ObligationStatus::Upcoming);
});
