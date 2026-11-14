<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RiskAssessmentController;
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

    // Payments & Recurrence (Phase 4)
    Route::get('salary-obligations/{salaryObligation}/payments', [PaymentController::class, 'create'])->name('salary-obligations.payments.create');
    Route::post('salary-obligations/{salaryObligation}/payments', [PaymentController::class, 'store'])->name('salary-obligations.payments.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

    // Risk Assessment Engine (Phase 5)
    Route::get('risk-assessments', [RiskAssessmentController::class, 'index'])->name('risk-assessments.index');
    Route::post('salary-obligations/{salaryObligation}/risk-assessment', [RiskAssessmentController::class, 'runAssessment'])->name('salary-obligations.risk-assessment');

    // Reminders & Scheduler (Phase 6)
    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::patch('reminders/{reminder}/read', [ReminderController::class, 'markAsRead'])->name('reminders.read');
    Route::post('reminders/read-all', [ReminderController::class, 'markAllAsRead'])->name('reminders.read-all');
});

require __DIR__.'/auth.php';
