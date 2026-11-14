<?php

namespace App\Http\Controllers;

use App\Enums\ObligationStatus;
use App\Models\SalaryObligation;
use App\Services\ObligationStatusSync;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display structured reports with totals (Section 9.2 & Section 11 Phase 8).
     * Types: upcoming, paid, overdue, high_risk.
     * Optional employee filter and date range on due_date, with count and total amount at bottom.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Sync statuses for fresh data
        ObligationStatusSync::syncForUser($user);

        $type = $request->input('type', 'upcoming');
        if (! in_array($type, ['upcoming', 'paid', 'overdue', 'high_risk'], true)) {
            $type = 'upcoming';
        }

        $query = SalaryObligation::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->with(['employee', 'latestRiskAssessment', 'payments']);

        // 1. Filter by report type
        match ($type) {
            'upcoming' => $query->where('status', ObligationStatus::Upcoming),
            'paid' => $query->where('status', ObligationStatus::Paid),
            'overdue' => $query->where('status', ObligationStatus::Overdue),
            'high_risk' => $query->whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
                ->whereHas('latestRiskAssessment', fn ($q) => $q->where('risk_level', 'high')),
        };

        // 2. Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // 3. Filter by date range on due_date (Section 9.2)
        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', $request->input('to'));
        }

        // Order by scheduled due date
        $query->orderBy('due_date', 'asc');

        // Aggregated totals at bottom (Section 9.2)
        $totalCount = (clone $query)->count();
        $totalAmountKobo = (int) (clone $query)->sum('amount');

        $records = $query->paginate(20)->withQueryString();

        $employees = $user->employees()->orderBy('name')->get();

        return view('reports.index', compact(
            'records',
            'type',
            'employees',
            'totalCount',
            'totalAmountKobo'
        ));
    }
}
