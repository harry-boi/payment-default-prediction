<?php

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;

test('test 8: record an on-time payment records Paid On Time and delay days 0', function () {
    Carbon::setTestNow('2026-10-30');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Emeka Okafor',
        'salary_amount' => 6000000, // ₦60,000 in kobo
        'salary_frequency' => SalaryFrequency::Monthly,
        'status' => EmployeeStatus::Active,
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 6000000,
        'due_date' => '2026-10-31',
        'frequency' => SalaryFrequency::Monthly,
        'status' => ObligationStatus::Upcoming,
        'payment_period' => 'October 2026',
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '60,000',
        'paid_at' => '2026-10-30',
        'reference' => 'GTB-TRF-00123',
    ]);

    $response->assertRedirect(route('payments.index'));
    $response->assertSessionHas('success');

    $obligation->refresh();
    expect($obligation->status)->toBe(ObligationStatus::Paid);

    $payment = Payment::where('salary_obligation_id', $obligation->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->amount_paid)->toBe(6000000)
        ->and($payment->status)->toBe(PaymentStatus::PaidOnTime)
        ->and($payment->delay_days)->toBe(0)
        ->and($payment->reference)->toBe('GTB-TRF-00123');

    // Next recurring obligation should be created automatically
    $nextObligation = SalaryObligation::where('employee_id', $employee->id)
        ->where('id', '!=', $obligation->id)
        ->first();

    expect($nextObligation)->not->toBeNull()
        ->and($nextObligation->amount)->toBe(6000000)
        ->and($nextObligation->due_date->format('Y-m-d'))->toBe('2026-11-30')
        ->and($nextObligation->frequency)->toBe(SalaryFrequency::Monthly)
        ->and($nextObligation->status)->toBe(ObligationStatus::Upcoming);
});

test('test 9: record a late payment records Paid Late and calculates delay days', function () {
    Carbon::setTestNow('2026-10-30');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Aisha Bello',
        'salary_amount' => 7500000, // ₦75,000
        'salary_frequency' => SalaryFrequency::Monthly,
        'status' => EmployeeStatus::Active,
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 7500000,
        'due_date' => '2026-10-25',
        'frequency' => SalaryFrequency::Monthly,
        'status' => ObligationStatus::Overdue,
        'payment_period' => 'October 2026',
    ]);

    // Paid on October 30 (5 days late)
    $response = $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '75000',
        'paid_at' => '2026-10-30',
        'reference' => 'CASH-REC-441',
    ]);

    $response->assertRedirect(route('payments.index'));

    $obligation->refresh();
    expect($obligation->status)->toBe(ObligationStatus::Paid);

    $payment = Payment::where('salary_obligation_id', $obligation->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::PaidLate)
        ->and($payment->delay_days)->toBe(5)
        ->and($payment->reference)->toBe('CASH-REC-441');
});

test('test 13: view payment history lists previous payments', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Bassey Udo']);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'status' => ObligationStatus::Paid,
    ]);

    $payment = Payment::factory()->create([
        'salary_obligation_id' => $obligation->id,
        'amount_paid' => 5000000,
        'paid_at' => '2026-10-20',
        'status' => PaymentStatus::PaidOnTime,
        'delay_days' => 0,
        'reference' => 'TEST-REF-99',
    ]);

    $response = $this->actingAs($user)->get(route('payments.index'));

    $response->assertStatus(200);
    $response->assertSee('Bassey Udo');
    $response->assertSee('₦50,000.00');
    $response->assertSee('Paid On Time');
    $response->assertSee('0 days (On Time)');
    $response->assertSee('TEST-REF-99');
});

test('recording a payment creates next recurring obligation with correct recurrence rules', function () {
    $user = User::factory()->create();

    // 1. Weekly recurrence
    $empWeekly = Employee::factory()->create([
        'user_id' => $user->id,
        'salary_frequency' => SalaryFrequency::Weekly,
        'salary_amount' => 1500000,
    ]);
    $obWeekly = SalaryObligation::factory()->create([
        'employee_id' => $empWeekly->id,
        'amount' => 1500000,
        'due_date' => '2026-10-07',
        'frequency' => SalaryFrequency::Weekly,
    ]);
    $this->actingAs($user)->post(route('salary-obligations.payments.store', $obWeekly), [
        'amount_paid' => '15000',
        'paid_at' => '2026-10-07',
    ]);
    $nextWeekly = SalaryObligation::where('employee_id', $empWeekly->id)
        ->where('id', '!=', $obWeekly->id)
        ->first();
    expect($nextWeekly->due_date->format('Y-m-d'))->toBe('2026-10-14');

    // 2. Bi-weekly recurrence
    $empBiWeekly = Employee::factory()->create([
        'user_id' => $user->id,
        'salary_frequency' => SalaryFrequency::Biweekly,
        'salary_amount' => 3000000,
    ]);
    $obBiWeekly = SalaryObligation::factory()->create([
        'employee_id' => $empBiWeekly->id,
        'amount' => 3000000,
        'due_date' => '2026-10-01',
        'frequency' => SalaryFrequency::Biweekly,
    ]);
    $this->actingAs($user)->post(route('salary-obligations.payments.store', $obBiWeekly), [
        'amount_paid' => '30000',
        'paid_at' => '2026-10-01',
    ]);
    $nextBiWeekly = SalaryObligation::where('employee_id', $empBiWeekly->id)
        ->where('id', '!=', $obBiWeekly->id)
        ->first();
    expect($nextBiWeekly->due_date->format('Y-m-d'))->toBe('2026-10-15');

    // 3. Monthly recurrence with month overflow safety (e.g. Jan 31 -> Feb 28)
    $empMonthly = Employee::factory()->create([
        'user_id' => $user->id,
        'salary_frequency' => SalaryFrequency::Monthly,
        'salary_amount' => 5000000,
    ]);
    $obMonthly = SalaryObligation::factory()->create([
        'employee_id' => $empMonthly->id,
        'amount' => 5000000,
        'due_date' => '2026-01-31',
        'frequency' => SalaryFrequency::Monthly,
    ]);
    $this->actingAs($user)->post(route('salary-obligations.payments.store', $obMonthly), [
        'amount_paid' => '50000',
        'paid_at' => '2026-01-31',
    ]);
    $nextMonthly = SalaryObligation::where('employee_id', $empMonthly->id)
        ->where('id', '!=', $obMonthly->id)
        ->first();
    expect($nextMonthly->due_date->format('Y-m-d'))->toBe('2026-02-28');
});

test('recording a payment does not create next obligation when employee is inactive', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => EmployeeStatus::Inactive,
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 4500000,
        'due_date' => '2026-10-31',
        'status' => ObligationStatus::Upcoming,
    ]);

    $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '45000',
        'paid_at' => '2026-10-31',
    ]);

    expect(SalaryObligation::where('employee_id', $employee->id)->count())->toBe(1);
});

test('double payment on an already paid obligation is rejected', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 4000000,
        'status' => ObligationStatus::Paid,
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '40000',
        'paid_at' => '2026-10-31',
    ]);

    $response->assertSessionHasErrors('salary_obligation');
    expect(Payment::where('salary_obligation_id', $obligation->id)->count())->toBe(0);
});

test('payment amount must equal obligation amount exactly', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000, // ₦50,000
        'status' => ObligationStatus::Upcoming,
    ]);

    // Send partial amount ₦30,000
    $response = $this->actingAs($user)->post(route('salary-obligations.payments.store', $obligation), [
        'amount_paid' => '30000',
        'paid_at' => '2026-10-31',
    ]);

    $response->assertSessionHasErrors('amount_paid');
    expect(Payment::where('salary_obligation_id', $obligation->id)->count())->toBe(0);
});

test('user cannot view or record payments for another user obligations', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeA = Employee::factory()->create(['user_id' => $userA->id, 'name' => 'Secret Worker']);
    $obligationA = SalaryObligation::factory()->create([
        'employee_id' => $employeeA->id,
        'amount' => 9000000,
    ]);

    // User B attempts to access payment form of User A
    $formResponse = $this->actingAs($userB)->get(route('salary-obligations.payments.create', $obligationA));
    $formResponse->assertStatus(403);

    // User B attempts to record payment for User A
    $postResponse = $this->actingAs($userB)->post(route('salary-obligations.payments.store', $obligationA), [
        'amount_paid' => '90000',
        'paid_at' => '2026-10-31',
    ]);
    $postResponse->assertStatus(403);

    // User B views payment history and cannot see User A's data
    Payment::factory()->create([
        'salary_obligation_id' => $obligationA->id,
        'amount_paid' => 9000000,
    ]);

    $historyResponse = $this->actingAs($userB)->get(route('payments.index'));
    $historyResponse->assertStatus(200);
    $historyResponse->assertDontSee('Secret Worker');
});
