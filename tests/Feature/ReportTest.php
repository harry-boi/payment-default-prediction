<?php

use App\Enums\ObligationStatus;
use App\Enums\RiskLevel;
use App\Models\Employee;
use App\Models\RiskAssessment;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;

test('test 14: view a report selected records and totals are shown', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Nsikan Obot',
    ]);

    // Obligation 1: ₦50,000 Upcoming
    SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation 2: ₦75,000 Upcoming
    SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 7500000,
        'due_date' => '2026-11-05',
        'status' => ObligationStatus::Upcoming,
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', ['type' => 'upcoming']));

    $response->assertStatus(200);
    $response->assertSee('Upcoming Salary Obligations');
    $response->assertSee('Nsikan Obot');
    $response->assertSee('₦50,000.00');
    $response->assertSee('₦75,000.00');

    // Totals at bottom: count 2, total ₦125,000.00
    $response->assertSee('2 obligations');
    $response->assertSee('₦125,000.00');
});

test('report filters by the four required types: upcoming, paid, overdue, high_risk', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // 1. Upcoming (due in future: Oct 30)
    SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 10000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
        'notes' => 'Unique-Upcoming-Note',
    ]);

    // 2. Paid
    SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 20000000,
        'due_date' => '2026-10-15',
        'status' => ObligationStatus::Paid,
        'notes' => 'Unique-Paid-Note',
    ]);

    // 3. Overdue (due in past: Oct 20)
    SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 30000000,
        'due_date' => '2026-10-20',
        'status' => ObligationStatus::Overdue,
        'notes' => 'Unique-Overdue-Note',
    ]);

    // 4. High Risk (overdue in past: Oct 18)
    $highRiskOb = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 40000000,
        'due_date' => '2026-10-18',
        'status' => ObligationStatus::Overdue,
        'notes' => 'Unique-HighRisk-Note',
    ]);
    RiskAssessment::factory()->create([
        'salary_obligation_id' => $highRiskOb->id,
        'score' => 70,
        'risk_level' => RiskLevel::High,
    ]);

    // Test type=upcoming
    $respUpcoming = $this->actingAs($user)->get(route('reports.index', ['type' => 'upcoming']));
    $respUpcoming->assertStatus(200);
    $respUpcoming->assertSee('Unique-Upcoming-Note');
    $respUpcoming->assertDontSee('Unique-Paid-Note');
    $respUpcoming->assertSee('₦100,000.00');

    // Test type=paid
    $respPaid = $this->actingAs($user)->get(route('reports.index', ['type' => 'paid']));
    $respPaid->assertStatus(200);
    $respPaid->assertSee('Unique-Paid-Note');
    $respPaid->assertDontSee('Unique-Upcoming-Note');
    $respPaid->assertSee('₦200,000.00');

    // Test type=overdue
    $respOverdue = $this->actingAs($user)->get(route('reports.index', ['type' => 'overdue']));
    $respOverdue->assertStatus(200);
    $respOverdue->assertSee('Unique-Overdue-Note');

    // Test type=high_risk
    $respHighRisk = $this->actingAs($user)->get(route('reports.index', ['type' => 'high_risk']));
    $respHighRisk->assertStatus(200);
    $respHighRisk->assertSee('Unique-HighRisk-Note');
    $respHighRisk->assertSee('₦400,000.00');
});

test('report filters by employee and due date range', function () {
    Carbon::setTestNow('2026-10-25');

    $user = User::factory()->create();
    $empA = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Worker Alpha']);
    $empB = Employee::factory()->create(['user_id' => $user->id, 'name' => 'Worker Beta']);

    // Obligation for Emp A in range
    SalaryObligation::factory()->create([
        'employee_id' => $empA->id,
        'amount' => 5000000,
        'due_date' => '2026-11-10',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation for Emp B in range
    SalaryObligation::factory()->create([
        'employee_id' => $empB->id,
        'amount' => 6000000,
        'due_date' => '2026-11-15',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Obligation for Emp A OUT of range (December)
    SalaryObligation::factory()->create([
        'employee_id' => $empA->id,
        'amount' => 7000000,
        'due_date' => '2026-12-20',
        'status' => ObligationStatus::Upcoming,
    ]);

    // Filter by Emp A and date range Nov 01 to Nov 30
    $response = $this->actingAs($user)->get(route('reports.index', [
        'type' => 'upcoming',
        'employee_id' => $empA->id,
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]));

    $response->assertStatus(200);
    $response->assertSee('Worker Alpha');
    $response->assertDontSee('₦60,000.00');
    $response->assertSee('1 obligation');
    $response->assertSee('₦50,000.00');
    $response->assertDontSee('₦70,000.00');
});

test('multi-tenant isolation: user cannot see another user records in reports', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $empA = Employee::factory()->create(['user_id' => $userA->id, 'name' => 'Confidential Employee']);
    SalaryObligation::factory()->create([
        'employee_id' => $empA->id,
        'amount' => 8800000,
        'status' => ObligationStatus::Upcoming,
    ]);

    $response = $this->actingAs($userB)->get(route('reports.index', ['type' => 'upcoming']));

    $response->assertStatus(200);
    $response->assertDontSee('Confidential Employee');
    $response->assertDontSee('₦88,000.00');
    $response->assertSee('0 obligations');
    $response->assertSee('₦0.00');
});
