<?php

use App\Enums\ObligationStatus;
use App\Enums\RiskLevel;
use App\Models\Employee;
use App\Models\SalaryObligation;
use App\Models\User;
use Carbon\Carbon;

test('test 12: run a risk assessment score level and factors are shown', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Aniefiok Ukpong',
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 5000000,
        'due_date' => '2026-10-30', // 2 days away -> 10 D points
        'status' => ObligationStatus::Upcoming,
    ]);

    // Manually trigger assessment via POST endpoint
    $response = $this->actingAs($user)->post(route('salary-obligations.risk-assessment', $obligation));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $assessment = $obligation->fresh()->latestRiskAssessment;
    expect($assessment)->not->toBeNull()
        ->and($assessment->score)->toBe(10) // 0 (A: no history) + 0 (B: 0 overdue) + 0 (C: 0 ratio) + 10 (D: 2 days)
        ->and($assessment->risk_level)->toBe(RiskLevel::Low)
        ->and($assessment->factors['sentences'])->toHaveCount(4)
        ->and($assessment->factors['sentences'][3])->toContain('10 points');

    // View obligation show page - score, level badge, and factors are shown
    $showResponse = $this->actingAs($user)->get(route('salary-obligations.show', $obligation));
    $showResponse->assertStatus(200);
    $showResponse->assertSee('Default Risk Assessment');
    $showResponse->assertSee('10');
    $showResponse->assertSee('Low Risk');
    $showResponse->assertSee('Due date is imminent in 2 days: 10 points');
});

test('authenticated user can view risk assessments overview page', function () {
    Carbon::setTestNow('2026-10-28');

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Victoria Edet',
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 7000000,
        'due_date' => '2026-10-30',
        'status' => ObligationStatus::Upcoming,
    ]);

    $response = $this->actingAs($user)->get(route('risk-assessments.index'));

    $response->assertStatus(200);
    $response->assertSee('Victoria Edet');
    $response->assertSee('₦70,000.00');
    $response->assertSee('Low Risk');
    $response->assertSee('Run assessment');
});

test('user cannot trigger risk assessment for another user obligation', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeA = Employee::factory()->create(['user_id' => $userA->id, 'name' => 'Private Employee']);
    $obligationA = SalaryObligation::factory()->create(['employee_id' => $employeeA->id]);

    $response = $this->actingAs($userB)->post(route('salary-obligations.risk-assessment', $obligationA));
    $response->assertStatus(403);

    $indexResponse = $this->actingAs($userB)->get(route('risk-assessments.index'));
    $indexResponse->assertStatus(200);
    $indexResponse->assertDontSee('Private Employee');
});
