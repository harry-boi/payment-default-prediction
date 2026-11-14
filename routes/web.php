<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalaryObligationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Employee Management (Phase 2)
    Route::resource('employees', EmployeeController::class)->except(['destroy']);
    Route::patch('employees/{employee}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.status');

    // Salary Obligations (Phase 3)
    Route::resource('salary-obligations', SalaryObligationController::class)->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/auth.php';
