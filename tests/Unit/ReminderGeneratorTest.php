<?php

use App\Enums\ObligationStatus;
use App\Enums\ReminderType;
use App\Models\Employee;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\ReminderGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generator creates upcoming reminder for obligation due within reminder window', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Bassey',
    ]);

    // Obligation due in 5 days (Oct 30) - within 7 days window
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $generator = new ReminderGenerator;
    $reminder = $generator->generateForObligation($obligation, '2026-10-25');

    expect($reminder)->not->toBeNull()
        ->and($reminder->user_id)->toBe($user->id)
        ->and($reminder->type)->toBe(ReminderType::Upcoming)
        ->and($reminder->is_read)->toBeFalse()
        ->and($reminder->message)->toBe('Salary for John Bassey (₦50,000.00) is due on Oct 30, 2026.');
});

test('generator does not create reminder for obligation due beyond reminder window', function () {
    Carbon::setTestNow('2026-10-01');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Obligation due in 15 days (Oct 16) - beyond 7 days window
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-16',
        'status' => ObligationStatus::Upcoming,
    ]);

    $generator = new ReminderGenerator;
    $reminder = $generator->generateForObligation($obligation, '2026-10-01');

    expect($reminder)->toBeNull()
        ->and(Reminder::count())->toBe(0);
});

test('generator creates overdue reminder for unpaid obligation past due date', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Blessing Udoh',
    ]);

    // Due on Oct 20 (5 days ago)
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 8000000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Overdue,
    ]);

    $generator = new ReminderGenerator;
    $reminder = $generator->generateForObligation($obligation, '2026-10-25');

    expect($reminder)->not->toBeNull()
        ->and($reminder->type)->toBe(ReminderType::Overdue)
        ->and($reminder->message)->toBe('Salary for Blessing Udoh (₦80,000.00) was due on Oct 20, 2026 and is overdue.');
});

test('generator creates no duplicates for same obligation and type', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-28',
        'status' => ObligationStatus::Upcoming,
    ]);

    $generator = new ReminderGenerator;
    $first = $generator->generateForObligation($obligation, '2026-10-25');
    $second = $generator->generateForObligation($obligation, '2026-10-25');

    expect($first->id)->toBe($second->id)
        ->and(Reminder::where('salary_obligation_id', $obligation->id)->count())->toBe(1);
});

test('generator does not create reminders for paid obligations', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-28',
        'status' => ObligationStatus::Paid,
    ]);

    $generator = new ReminderGenerator;
    $reminder = $generator->generateForObligation($obligation);

    expect($reminder)->toBeNull()
        ->and(Reminder::count())->toBe(0);
});
