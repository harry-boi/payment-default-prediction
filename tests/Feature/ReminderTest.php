<?php

use App\Enums\ObligationStatus;
use App\Enums\ReminderType;
use App\Models\Employee;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

test('test 10: view upcoming reminders obligations inside the window appear', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Kufre Daniel',
    ]);

    // Due in 3 days (Oct 28) - inside the 7 days window
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 6500000,
        'due_date' => '2026-10-28',
        'status' => ObligationStatus::Upcoming,
    ]);

    $response = $this->actingAs($user)->get(route('reminders.index'));

    $response->assertStatus(200);
    $response->assertSee('Salary for Kufre Daniel (₦65,000.00) is due on Oct 28, 2026.');
    $response->assertSee('Upcoming Due Date');
    $response->assertSee('Pay Now');

    expect(Reminder::where('salary_obligation_id', $obligation->id)->where('type', ReminderType::Upcoming)->exists())->toBeTrue();
});

test('test 11: view overdue reminders unpaid obligations after the due date appear', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Mfoniso Moses',
    ]);

    // Due on Oct 20 (5 days past due)
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 9000000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Overdue,
    ]);

    $response = $this->actingAs($user)->get(route('reminders.index'));

    $response->assertStatus(200);
    $response->assertSee('Salary for Mfoniso Moses (₦90,000.00) was due on Oct 20, 2026 and is overdue.');
    $response->assertSee('Overdue Alert');

    expect(Reminder::where('salary_obligation_id', $obligation->id)->where('type', ReminderType::Overdue)->exists())->toBeTrue();
});

test('user can mark a reminder as read', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create(['employee_id' => $employee->id]);

    $reminder = Reminder::factory()->create([
        'user_id' => $user->id,
        'salary_obligation_id' => $obligation->id,
        'is_read' => false,
    ]);

    $response = $this->actingAs($user)->patch(route('reminders.read', $reminder));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($reminder->fresh()->is_read)->toBeTrue();
});

test('user can mark all reminders as read', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation1 = SalaryObligation::factory()->create(['employee_id' => $employee->id]);
    $obligation2 = SalaryObligation::factory()->create(['employee_id' => $employee->id]);

    Reminder::factory()->create([
        'user_id' => $user->id,
        'salary_obligation_id' => $obligation1->id,
        'is_read' => false,
    ]);
    Reminder::factory()->create([
        'user_id' => $user->id,
        'salary_obligation_id' => $obligation2->id,
        'is_read' => false,
    ]);

    $response = $this->actingAs($user)->post(route('reminders.read-all'));

    $response->assertRedirect();
    expect($user->reminders()->where('is_read', false)->count())->toBe(0);
});

test('bills:process-daily artisan command runs sync, reminder generator, and risk refresh with date option', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Scheduler Worker']);

    // Obligation due on 2026-11-05
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 4000000,
        'due_date' => '2026-11-05',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Run command pretending today is 2026-11-02 (within 3 days of due date)
    $exitCode = Artisan::call('bills:process-daily', ['--date' => '2026-11-02']);

    expect($exitCode)->toBe(0)
        ->and(Reminder::where('salary_obligation_id', $obligation->id)->where('type', ReminderType::Upcoming)->exists())->toBeTrue();

    // Run again with same date - no duplicates created
    Artisan::call('bills:process-daily', ['--date' => '2026-11-02']);
    expect(Reminder::where('salary_obligation_id', $obligation->id)->count())->toBe(1);

    // Now run pretending today is 2026-11-10 (obligation is now overdue)
    Artisan::call('bills:process-daily', ['--date' => '2026-11-10']);

    $obligation->refresh();
    expect($obligation->status)->toBe(ObligationStatus::Overdue)
        ->and(Reminder::where('salary_obligation_id', $obligation->id)->where('type', ReminderType::Overdue)->exists())->toBeTrue();
});

test('paying an obligation marks its reminders as read', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $reminder = Reminder::factory()->create([
        'user_id' => $user->id,
        'salary_obligation_id' => $obligation->id,
        'type' => ReminderType::Upcoming,
        'is_read' => false,
    ]);

    $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '50000',
        'paid_at' => '2026-10-30',
    ]);

    expect($reminder->fresh()->is_read)->toBeTrue();
});

test('user cannot view or mark another user reminders as read', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeA = Employee::factory()->create(['user_id' => $userA->id, 'name' => 'User A Worker']);
    $obligationA = SalaryObligation::factory()->create(['employee_id' => $employeeA->id]);

    $reminderA = Reminder::factory()->create([
        'user_id' => $userA->id,
        'salary_obligation_id' => $obligationA->id,
        'message' => 'Confidential salary notice for User A',
        'is_read' => false,
    ]);

    // User B attempts to mark User A's reminder as read
    $patchResponse = $this->actingAs($userB)->patch(route('reminders.read', $reminderA));
    $patchResponse->assertStatus(403);

    // User B views their reminders list and cannot see User A's reminder
    $indexResponse = $this->actingAs($userB)->get(route('reminders.index'));
    $indexResponse->assertStatus(200);
    $indexResponse->assertDontSee('Confidential salary notice for User A');
});
