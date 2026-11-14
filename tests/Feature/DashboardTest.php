<?php

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\ReminderType;
use App\Models\Employee;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;

test('dashboard loads without errors for user with no data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Payroll Dashboard');
    $response->assertSee('Active Employees');
    $response->assertSee('₦0.00');
    $response->assertSee('No upcoming salary obligations scheduled.');
    $response->assertSee('All caught up!');
});

test('dashboard card metrics accurately match underlying data', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();

    // 2 active employees, 1 inactive
    $emp1 = Employee::factory()->create(['user_id' => $user->id, 'name' => 'John Doe', 'status' => EmployeeStatus::Active]);
    $emp2 = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Jane Smith', 'status' => EmployeeStatus::Active]);
    Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::Inactive]);

    // Obligation 1: Due in 4 days (within 7-day window) -> ₦50,000
    SalaryObligation::factory()->create([
        'employee_id' => $emp1->id,
        'amount' => 5000000,
        'due_date' => '2026-10-29',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation 2: Due in 20 days (beyond 7-day window) -> ₦80,000
    SalaryObligation::factory()->create([
        'employee_id' => $emp2->id,
        'amount' => 8000000,
        'due_date' => '2026-11-14',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation 3: Overdue by 5 days -> ₦45,000
    SalaryObligation::factory()->create([
        'employee_id' => $emp1->id,
        'amount' => 4500000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Overdue,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    // Active employees card: 2
    $response->assertSee('Active Employees');
    // Amount due soon: ₦50,000.00 (only the one inside the 7-day window)
    $response->assertSee('₦50,000.00');
    // Upcoming obligations: 2
    $response->assertSee('Upcoming Payments');
    // Overdue obligations: 1
    $response->assertSee('Overdue Payments');
    $response->assertSee('₦45,000.00');
    $response->assertSee('John Doe');
    $response->assertSee('Jane Smith');
});

test('dashboard automatically executes status sync and reminder generation on load', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id, 'name' => 'AutoSync Worker']);

    // Obligation that was created as upcoming, but its due date has now passed (due on Oct 20)
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 6000000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Upcoming, // stale status
    ]);

    expect(Reminder::where('salary_obligation_id', $obligation->id)->count())->toBe(0);

    // Visiting the dashboard triggers sync and reminder generation automatically (Locked Decision #4)
    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertStatus(200);

    $obligation->refresh();
    expect($obligation->status)->toBe(ObligationStatus::Overdue)
        ->and(Reminder::where('salary_obligation_id', $obligation->id)->where('type', ReminderType::Overdue)->exists())->toBeTrue();
});

test('multi-tenant isolation: dashboard only counts and renders authenticated user records', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $empA = Employee::factory()->create(['user_id' => $userA->id, 'name' => 'Alpha Worker']);
    SalaryObligation::factory()->create([
        'employee_id' => $empA->id,
        'amount' => 9900000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $empB = Employee::factory()->create(['user_id' => $userB->id, 'name' => 'Beta Worker']);
    SalaryObligation::factory()->create([
        'employee_id' => $empB->id,
        'amount' => 3300000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $responseB = $this->actingAs($userB)->get(route('dashboard'));

    $responseB->assertStatus(200);
    $responseB->assertSee('Beta Worker');
    $responseB->assertSee('₦33,000.00');
    $responseB->assertDontSee('Alpha Worker');
    $responseB->assertDontSee('₦99,000.00');
});
