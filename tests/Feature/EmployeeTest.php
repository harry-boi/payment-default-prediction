<?php

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;

test('authenticated user can view employees page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertStatus(200);
});

test('test 5: add an employee with valid salary information saves and displays employee', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('employees.store'), [
        'name' => 'Emmanuel Okon',
        'phone' => '08012345678',
        'job_title' => 'Shop Attendant',
        'salary_amount' => '70,000.00',
        'salary_frequency' => 'monthly',
    ]);

    $response->assertRedirect(route('employees.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('employees', [
        'user_id' => $user->id,
        'name' => 'Emmanuel Okon',
        'salary_amount' => 7000000, // ₦70,000 in kobo
        'salary_frequency' => 'monthly',
        'status' => 'active',
    ]);

    $indexResponse = $this->actingAs($user)->get(route('employees.index'));
    $indexResponse->assertSee('Emmanuel Okon');
    $indexResponse->assertSee('₦70,000.00');
});

test('creating employee with invalid salary fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('employees.store'), [
        'name' => 'Invalid Salary Worker',
        'salary_amount' => 'invalid-amount',
        'salary_frequency' => 'monthly',
    ]);

    $response->assertSessionHasErrors('salary_amount');
});

test('authenticated user can edit employee details', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'salary_amount' => 5000000,
    ]);

    $response = $this->actingAs($user)->put(route('employees.update', $employee), [
        'name' => 'Updated Name',
        'phone' => '08099999999',
        'job_title' => 'Manager',
        'salary_amount' => '65,000',
        'salary_frequency' => 'monthly',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('employees.index'));

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'name' => 'Updated Name',
        'salary_amount' => 6500000,
    ]);
});

test('authenticated user can toggle employee status between active and inactive', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'status' => EmployeeStatus::Active,
    ]);

    $response = $this->actingAs($user)->patch(route('employees.status', $employee));

    $response->assertSessionHas('success');
    expect($employee->fresh()->status)->toBe(EmployeeStatus::Inactive);

    // Toggle back
    $this->actingAs($user)->patch(route('employees.status', $employee));
    expect($employee->fresh()->status)->toBe(EmployeeStatus::Active);
});

test('user cannot view or modify another users employees', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $employeeB = Employee::factory()->create([
        'user_id' => $userB->id,
        'name' => 'User B Worker',
    ]);

    // User A cannot view User B's employee
    $response = $this->actingAs($userA)->get(route('employees.show', $employeeB));
    $response->assertStatus(403);

    // User A cannot edit User B's employee
    $response = $this->actingAs($userA)->get(route('employees.edit', $employeeB));
    $response->assertStatus(403);

    // User A cannot update User B's employee
    $response = $this->actingAs($userA)->put(route('employees.update', $employeeB), [
        'name' => 'Hacked Name',
        'salary_amount' => '100,000',
        'salary_frequency' => 'monthly',
        'status' => 'active',
    ]);
    $response->assertStatus(403);

    // User A cannot toggle status of User B's employee
    $response = $this->actingAs($userA)->patch(route('employees.status', $employeeB));
    $response->assertStatus(403);

    // User A's employee index does NOT list User B's worker
    $response = $this->actingAs($userA)->get(route('employees.index'));
    $response->assertDontSee('User B Worker');
});
