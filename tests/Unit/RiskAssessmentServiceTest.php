<?php

use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\RiskLevel;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\RiskAssessment;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\RiskAssessmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// --- Component A Tests (Historical Late-Payment Rate) ---

test('component A: 0 points and no_history flag when employee has no completed payments', function () {
    Carbon::setTestNow('2026-10-01');
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15', // > 7 days (0 D points)
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($obligation, '2026-10-01');

    expect($assessment->factors['a_points'])->toBe(0)
        ->and($assessment->factors['no_history'])->toBeTrue()
        ->and($assessment->late_rate)->toBe(0.0)
        ->and($assessment->factors['sentences'][0])->toContain('No history yet');
});

test('component A: 0 points when employee late rate is up to 10%', function () {
    Carbon::setTestNow('2026-10-01');
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // 10 completed payments: 1 late (10%), 9 on time
    for ($i = 0; $i < 10; $i++) {
        $ob = SalaryObligation::factory()->create(['employee_id' => $employee->id, 'status' => ObligationStatus::Paid]);
        Payment::factory()->create([
            'salary_obligation_id' => $ob->id,
            'status' => $i === 0 ? PaymentStatus::PaidLate : PaymentStatus::PaidOnTime,
            'delay_days' => $i === 0 ? 3 : 0,
        ]);
    }

    $currentObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($currentObligation, '2026-10-01');

    expect($assessment->factors['a_points'])->toBe(0)
        ->and($assessment->late_rate)->toBe(10.0);
});

test('component A: 10 points when employee late rate is above 10% up to 30%', function () {
    Carbon::setTestNow('2026-10-01');
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // 10 completed payments: 2 late (20%), 8 on time
    for ($i = 0; $i < 10; $i++) {
        $ob = SalaryObligation::factory()->create(['employee_id' => $employee->id, 'status' => ObligationStatus::Paid]);
        Payment::factory()->create([
            'salary_obligation_id' => $ob->id,
            'status' => $i < 2 ? PaymentStatus::PaidLate : PaymentStatus::PaidOnTime,
            'delay_days' => $i < 2 ? 4 : 0,
        ]);
    }

    $currentObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($currentObligation, '2026-10-01');

    expect($assessment->factors['a_points'])->toBe(10)
        ->and($assessment->late_rate)->toBe(20.0);
});

test('component A: 20 points when employee late rate is above 30% up to 50%', function () {
    Carbon::setTestNow('2026-10-01');
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // 4 completed payments: 2 late (50%), 2 on time
    for ($i = 0; $i < 4; $i++) {
        $ob = SalaryObligation::factory()->create(['employee_id' => $employee->id, 'status' => ObligationStatus::Paid]);
        Payment::factory()->create([
            'salary_obligation_id' => $ob->id,
            'status' => $i < 2 ? PaymentStatus::PaidLate : PaymentStatus::PaidOnTime,
            'delay_days' => $i < 2 ? 5 : 0,
        ]);
    }

    $currentObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($currentObligation, '2026-10-01');

    expect($assessment->factors['a_points'])->toBe(20)
        ->and($assessment->late_rate)->toBe(50.0);
});

test('component A: 30 points when employee late rate is above 50%', function () {
    Carbon::setTestNow('2026-10-01');
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // 5 completed payments: 3 late (60%), 2 on time
    for ($i = 0; $i < 5; $i++) {
        $ob = SalaryObligation::factory()->create(['employee_id' => $employee->id, 'status' => ObligationStatus::Paid]);
        Payment::factory()->create([
            'salary_obligation_id' => $ob->id,
            'status' => $i < 3 ? PaymentStatus::PaidLate : PaymentStatus::PaidOnTime,
            'delay_days' => $i < 3 ? 6 : 0,
        ]);
    }

    $currentObligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($currentObligation, '2026-10-01');

    expect($assessment->factors['a_points'])->toBe(30)
        ->and($assessment->late_rate)->toBe(60.0);
});

// --- Component B & C Tests (Currently Overdue Obligations & Outstanding Ratio) ---

test('component B and C: 0 B points and 0 C points when employer has no other overdue obligations', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-25',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($obligation, '2026-10-01');

    expect($assessment->factors['b_points'])->toBe(0)
        ->and($assessment->overdue_count)->toBe(0)
        ->and($assessment->factors['c_points'])->toBe(0)
        ->and($assessment->outstanding_ratio)->toBe(0.0);
});

test('component B and C: 15 B points for 1 overdue obligation and 5 C points for ratio under 25%', function () {
    $user = User::factory()->create();
    $employee1 = Employee::factory()->create(['user_id' => $user->id]);
    $employee2 = Employee::factory()->create(['user_id' => $user->id]);

    // Current obligation being assessed is ₦100,000 (10,000,000 kobo)
    $current = SalaryObligation::factory()->create([
        'employee_id' => $employee1->id,
        'amount' => 10000000,
        'due_date' => '2026-10-25',
    ]);

    // Other overdue obligation: 1 overdue obligation of ₦10,000 (10% ratio < 25%)
    SalaryObligation::factory()->create([
        'employee_id' => $employee2->id,
        'amount' => 1000000, // ₦10,000
        'status' => ObligationStatus::Overdue,
        'due_date' => '2026-09-20',
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($current, '2026-10-01');

    expect($assessment->factors['b_points'])->toBe(15)
        ->and($assessment->overdue_count)->toBe(1)
        ->and($assessment->factors['c_points'])->toBe(5)
        ->and($assessment->outstanding_ratio)->toBe(10.0);
});

test('component B and C: 25 B points for 2+ overdue obligations and 10 C points for ratio between 25% and 50%', function () {
    $user = User::factory()->create();
    $employee1 = Employee::factory()->create(['user_id' => $user->id]);
    $employee2 = Employee::factory()->create(['user_id' => $user->id]);

    // Current obligation: ₦100,000
    $current = SalaryObligation::factory()->create([
        'employee_id' => $employee1->id,
        'amount' => 10000000,
        'due_date' => '2026-10-25',
    ]);

    // 2 overdue obligations summing to ₦40,000 (40% ratio)
    SalaryObligation::factory()->create([
        'employee_id' => $employee2->id,
        'amount' => 2000000,
        'status' => ObligationStatus::Overdue,
    ]);
    SalaryObligation::factory()->create([
        'employee_id' => $employee2->id,
        'amount' => 2000000,
        'status' => ObligationStatus::Overdue,
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($current, '2026-10-01');

    expect($assessment->factors['b_points'])->toBe(25)
        ->and($assessment->overdue_count)->toBe(2)
        ->and($assessment->factors['c_points'])->toBe(10)
        ->and($assessment->outstanding_ratio)->toBe(40.0);
});

test('component C: 15 C points when outstanding ratio is above 50%', function () {
    $user = User::factory()->create();
    $employee1 = Employee::factory()->create(['user_id' => $user->id]);
    $employee2 = Employee::factory()->create(['user_id' => $user->id]);

    // Current obligation: ₦50,000
    $current = SalaryObligation::factory()->create([
        'employee_id' => $employee1->id,
        'amount' => 5000000,
        'due_date' => '2026-10-25',
    ]);

    // Overdue obligation: ₦35,000 (70% ratio > 50%)
    SalaryObligation::factory()->create([
        'employee_id' => $employee2->id,
        'amount' => 3500000,
        'status' => ObligationStatus::Overdue,
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($current, '2026-10-01');

    expect($assessment->factors['c_points'])->toBe(15)
        ->and($assessment->outstanding_ratio)->toBe(70.0);
});

// --- Component D Tests (Due Proximity) ---

test('component D: 0 points when due date is more than 7 days away', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15', // 14 days away from Oct 01
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($obligation, '2026-10-01');

    expect($assessment->factors['d_points'])->toBe(0)
        ->and($assessment->due_proximity_points)->toBe(0);
});

test('component D: 5 points when due date is 4 to 7 days away', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-06', // 5 days away from Oct 01
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($obligation, '2026-10-01');

    expect($assessment->factors['d_points'])->toBe(5)
        ->and($assessment->due_proximity_points)->toBe(5);
});

test('component D: 10 points when due date is 1 to 3 days away', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-03', // 2 days away from Oct 01
    ]);

    $service = new RiskAssessmentService;
    $assessment = $service->assess($obligation, '2026-10-01');

    expect($assessment->factors['d_points'])->toBe(10)
        ->and($assessment->due_proximity_points)->toBe(10);
});

test('component D: 15 points when due date is today or overdue', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Due today
    $dueToday = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-01',
    ]);
    // Overdue by 4 days
    $overdue = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-09-27',
    ]);

    $service = new RiskAssessmentService;
    $assessmentToday = $service->assess($dueToday, '2026-10-01');
    $assessmentOverdue = $service->assess($overdue, '2026-10-01');

    expect($assessmentToday->factors['d_points'])->toBe(15)
        ->and($assessmentToday->due_proximity_points)->toBe(15)
        ->and($assessmentOverdue->factors['d_points'])->toBe(15)
        ->and($assessmentOverdue->due_proximity_points)->toBe(15);
});

// --- Classification & Immutability Tests ---

test('risk classification correctly maps scores to Low (0-28), Medium (29-56), and High (57-85)', function () {
    expect(RiskLevel::fromScore(0))->toBe(RiskLevel::Low)
        ->and(RiskLevel::fromScore(28))->toBe(RiskLevel::Low)
        ->and(RiskLevel::fromScore(29))->toBe(RiskLevel::Medium)
        ->and(RiskLevel::fromScore(56))->toBe(RiskLevel::Medium)
        ->and(RiskLevel::fromScore(57))->toBe(RiskLevel::High)
        ->and(RiskLevel::fromScore(85))->toBe(RiskLevel::High);
});

test('service does not re-assess paid obligations and retains previous assessment', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'status' => ObligationStatus::Paid,
    ]);

    $existing = RiskAssessment::factory()->create([
        'salary_obligation_id' => $obligation->id,
        'score' => 45,
        'risk_level' => RiskLevel::Medium,
    ]);

    $service = new RiskAssessmentService;
    $result = $service->assess($obligation);

    expect($result->id)->toBe($existing->id)
        ->and(RiskAssessment::where('salary_obligation_id', $obligation->id)->count())->toBe(1);
});

test('service avoids inserting duplicate risk assessments when components are unchanged', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'due_date' => '2026-10-15',
    ]);

    $service = new RiskAssessmentService;
    $first = $service->assess($obligation, '2026-10-01');
    $second = $service->assess($obligation, '2026-10-01');

    expect($first->id)->toBe($second->id)
        ->and(RiskAssessment::where('salary_obligation_id', $obligation->id)->count())->toBe(1);
});
