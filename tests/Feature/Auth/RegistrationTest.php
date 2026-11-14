<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('test 1: register with valid information creates account and redirects to dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(User::where('email', 'test@example.com')->count())->toBe(1);
});

test('test 2: register with an existing email produces validation error and no duplicate', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post('/register', [
        'name' => 'Duplicate User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    expect(User::where('email', 'existing@example.com')->count())->toBe(1);
});
