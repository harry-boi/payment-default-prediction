<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskAssessmentController;
use App\Http\Controllers\SalaryObligationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::post('reminders/email-summary', [ReminderController::class, 'emailSummary'])->name('reminders.email-summary');

    // Reports (Phase 8)
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';

// TEMPORARY diagnostic (remove after email verification): reports only
// whether each email setting is present and its length — never the value.
Route::get('/config-check', function () {
    $report = [];
    foreach (['RESEND_API_KEY' => env('RESEND_API_KEY'), 'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'), 'MAIL_FROM_NAME' => env('MAIL_FROM_NAME')] as $name => $value) {
        $report['env'][$name] = ['present' => ! empty($value), 'length' => $value ? strlen($value) : 0];
    }
    foreach (['key' => config('services.resend.key'), 'from' => config('services.resend.from'), 'from_name' => config('services.resend.from_name')] as $name => $value) {
        $report['config'][$name] = ['present' => ! empty($value), 'length' => $value ? strlen($value) : 0];
    }
    return response()->json($report);
});
