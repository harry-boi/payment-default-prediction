<?php

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;

test('authenticated user can view salary obligations list and filter by status and employee', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Grace Effiong']);

    $upcoming = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'status' => ObligationStatus::Upcoming,
    ]);

    $response = $this->actingAs($user)->get(route('salary-obligations.index'));
    $response->assertStatus(200);
    $response->assertSee('Grace Effiong');

    // Filter by status=upcoming
    $filterResponse = $this->actingAs($user)->get(route('salary-obligations.index', ['status' => 'upcoming']));
    $filterResponse->assertStatus(200);
    $filterResponse->assertSee('Grace Effiong');

    // Filter by non-existent employee
    $emptyResponse = $this->actingAs($user)->get(route('salary-obligations.index', ['employee_id' => 99999]));
    $emptyResponse->assertStatus(200);
    $emptyResponse->assertSee('No salary obligations found');
});

test('test 6: create a salary obligation appears in upcoming payments', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Ime Akpan',
        'salary_amount' => 8500000, // ₦85,000 in kobo
        'salary_frequency' => 'monthly',
        'status' => EmployeeStatus::Active,
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.store'), [
        'employee_id' => $employee->id,
        'amount' => '85,000',
        'due_date' => '2026-10-30',
        'payment_period' => 'October 2026',
        'notes' => 'Regular monthly salary',
    ]);

    $response->assertRedirect(route('salary-obligations.index'));
    $response->assertSessionHas('success');

    $obligation = SalaryObligation::where('employee_id', $employee->id)->first();
    expect($obligation)->not->toBeNull()
        ->and($obligation->amount)->toBe(8500000)
        ->and($obligation->due_date->format('Y-m-d'))->toBe('2026-10-30')
        ->and($obligation->status)->toBe(ObligationStatus::Upcoming)
        ->and($obligation->frequency)->toBe(SalaryFrequency::Monthly)
        ->and($obligation->payment_period)->toBe('October 2026');

    $indexResponse = $this->actingAs($user)->get(route('salary-obligations.index'));
    $indexResponse->assertSee('Ime Akpan');
    $indexResponse->assertSee('₦85,000.00');
    $indexResponse->assertSee('Upcoming');
});

test('test 7: create an obligation with an invalid amount returns validation error', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => EmployeeStatus::Active,
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.store'), [
        'employee_id' => $employee->id,
        'amount' => 'invalid-naira',
        'due_date' => '2026-11-01',
    ]);

    $response->assertSessionHasErrors('amount');
});

test('creating an obligation with a past due date marks status as overdue immediately', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => EmployeeStatus::Active,
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.store'), [
        'employee_id' => $employee->id,
        'amount' => '60000',
        'due_date' => '2026-10-20', // past date
    ]);

    $response->assertRedirect(route('salary-obligations.index'));

    $obligation = SalaryObligation::where('employee_id', $employee->id)->first();
    expect($obligation)->not->toBeNull()
        ->and($obligation->due_date->format('Y-m-d'))->toBe('2026-10-20')
        ->and($obligation->status)->toBe(ObligationStatus::Overdue);
});

test('cannot schedule obligation for an inactive employee', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => EmployeeStatus::Inactive,
    ]);

    $response = $this->actingAs($user)->post(route('salary-obligations.store'), [
        'employee_id' => $employee->id,
        'amount' => '50000',
        'due_date' => '2026-11-01',
    ]);

    $response->assertSessionHasErrors('employee_id');
});

test('user cannot view or access another users salary obligations', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeB = Employee::factory()->create(['user_id' => $userB->id]);
    $obligationB = SalaryObligation::factory()->create(['employee_id' => $employeeB->id]);

    $response = $this->actingAs($userA)->get(route('salary-obligations.show', $obligationB));
    $response->assertStatus(403);
});

test('creating employee with first payment date creates initial salary obligation', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('employees.store'), [
        'name' => 'Blessing Etim',
        'job_title' => 'Cashier',
        'salary_amount' => '55000',
        'salary_frequency' => 'monthly',
        'first_payment_date' => '2026-11-04',
    ]);

    $response->assertRedirect(route('employees.index'));

    $employee = Employee::where('name', 'Blessing Etim')->firstOrFail();
    $obligation = $employee->salaryObligations()->first();

    expect($obligation)->not->toBeNull()
        ->and($obligation->amount)->toBe(5500000)
        ->and($obligation->due_date->format('Y-m-d'))->toBe('2026-11-04')
        ->and($obligation->status)->toBe(ObligationStatus::Upcoming)
        ->and($obligation->frequency)->toBe(SalaryFrequency::Monthly)
        ->and($obligation->payment_period)->toBe('November 2026');
});
