<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Models\SalaryObligation;
use App\Services\ObligationStatusSync;
use App\Services\ReminderGenerator;
use App\Services\RiskAssessmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the employer's dashboard with summary cards, tables, and alerts (Section 9.2 & Figure 3.8).
     */
    public function __invoke(
        Request $request,
        ReminderGenerator $reminderGenerator,
        RiskAssessmentService $riskAssessmentService
    ): View {
        $user = $request->user();

        // Locked Decision #4 & Section 10.7:
        // Automatically sync obligation statuses, generate reminders, and refresh risk scores for signed-in user
        ObligationStatusSync::syncForUser($user);
        $reminderGenerator->generateForUser($user);
        $riskAssessmentService->assessOpenObligationsForUser($user);

        // 1. Total active employees
        $totalActiveEmployees = $user->employees()
            ->where('status', EmployeeStatus::Active)
            ->count();

        // 2. Amount due soon (upcoming obligations due within reminder window, default 7 days)
        $windowDays = (int) config('reminders.window_days', 7);
        $today = Carbon::now()->startOfDay();
        $windowEnd = $today->copy()->addDays($windowDays);

        $amountDueSoonKobo = (int) SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Upcoming)
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $windowEnd)
            ->sum('amount');

        // 3. Number of upcoming payments
        $upcomingCount = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Upcoming)
            ->count();

        // 4. Number of overdue payments
        $overdueCount = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Overdue)
            ->count();

        // 4b. Total amount overdue (for the post-login summary modal)
        $overdueTotalKobo = (int) SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Overdue)
            ->sum('amount');

        // Post-login overdue summary modal: show once per session, only when
        // there is at least one overdue payment.
        $showOverdueModal = false;
        if ($overdueCount > 0 && ! $request->session()->has('overdue_modal_shown')) {
            $showOverdueModal = true;
            $request->session()->put('overdue_modal_shown', true);
        }

        // 5. Number of high-risk obligations
        $highRiskCount = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->whereHas('latestRiskAssessment', fn ($q) => $q->where('risk_level', 'high'))
            ->count();

        // Upcoming payments table (5 soonest)
        $upcomingObligations = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Upcoming)
            ->with(['employee', 'latestRiskAssessment'])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // Overdue payments table (5 longest overdue)
        $overdueObligations = SalaryObligation::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', ObligationStatus::Overdue)
            ->with(['employee', 'latestRiskAssessment'])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // Panel of unread reminders (latest 5)
        $unreadReminders = $user->reminders()
            ->where('is_read', false)
            ->with(['salaryObligation.employee', 'salaryObligation.latestRiskAssessment'])
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalActiveEmployees',
            'amountDueSoonKobo',
            'upcomingCount',
            'overdueCount',
            'overdueTotalKobo',
            'showOverdueModal',
            'highRiskCount',
            'upcomingObligations',
            'overdueObligations',
            'unreadReminders',
            'windowDays'
        ));
    }
}
