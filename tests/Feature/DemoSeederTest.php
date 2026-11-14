<?php

use App\Enums\RiskLevel;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\RiskAssessment;
use App\Models\SalaryObligation;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('demo seeder runs successfully and creates realistic dataset', function () {
    $this->seed(DemoSeeder::class);

    // Verify User
    $user = User::where('email', 'demo@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Effiong Dominic Eyoh');

    // Verify 6 Employees
    expect(Employee::where('user_id', $user->id)->count())->toBe(6);

    // Verify 30 Salary Obligations
    $obligationsCount = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))->count();
    expect($obligationsCount)->toBe(30);

    // Verify Payments
    $paymentsCount = Payment::whereHas('salaryObligation.employee', fn ($q) => $q->where('user_id', $user->id))->count();
    expect($paymentsCount)->toBeGreaterThanOrEqual(20);

    // Verify Reminders
    $remindersCount = Reminder::where('user_id', $user->id)->count();
    expect($remindersCount)->toBeGreaterThanOrEqual(5);

    // Verify Risk Assessments have varied levels (Low, Medium, High)
    $riskLevels = RiskAssessment::pluck('risk_level')->all();
    expect($riskLevels)->toContain(RiskLevel::Low);
    expect($riskLevels)->toContain(RiskLevel::High);
});
