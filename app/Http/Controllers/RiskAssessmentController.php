<?php

namespace App\Http\Controllers;

use App\Enums\ObligationStatus;
use App\Models\SalaryObligation;
use App\Services\ObligationStatusSync;
use App\Services\RiskAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RiskAssessmentController extends Controller
{
    /**
     * Display the latest assessment of each open obligation (Section 9.2).
     */
    public function index(Request $request, RiskAssessmentService $service): View
    {
        $user = $request->user();

        // Sync upcoming/overdue statuses before assessing
        ObligationStatusSync::syncForUser($user);

        // Fetch all open obligations (Upcoming or Overdue)
        $query = SalaryObligation::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [ObligationStatus::Upcoming, ObligationStatus::Overdue])
            ->with(['employee', 'latestRiskAssessment'])
            ->orderBy('due_date', 'asc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $allOpenObligations = $query->get();

        // Ensure every open obligation has an assessment
        foreach ($allOpenObligations as $obligation) {
            if (! $obligation->latestRiskAssessment) {
                $service->assess($obligation);
                $obligation->load('latestRiskAssessment');
            }
        }

        // Apply risk level filter if requested
        $filterLevel = $request->input('risk_level');
        $filteredObligations = $allOpenObligations;

        if ($filterLevel && in_array($filterLevel, ['low', 'medium', 'high'], true)) {
            $filteredObligations = $allOpenObligations->filter(function ($obligation) use ($filterLevel) {
                return $obligation->latestRiskAssessment?->risk_level?->value === $filterLevel;
            });
        }

        // Summary counts
        $highRiskCount = $allOpenObligations->filter(fn ($o) => $o->latestRiskAssessment?->isHigh())->count();
        $mediumRiskCount = $allOpenObligations->filter(fn ($o) => $o->latestRiskAssessment?->isMedium())->count();
        $lowRiskCount = $allOpenObligations->filter(fn ($o) => $o->latestRiskAssessment?->isLow())->count();
        $totalOpenCount = $allOpenObligations->count();

        $employees = $user->employees()->orderBy('name')->get();

        return view('risk-assessments.index', [
            'obligations' => $filteredObligations,
            'employees' => $employees,
            'highRiskCount' => $highRiskCount,
            'mediumRiskCount' => $mediumRiskCount,
            'lowRiskCount' => $lowRiskCount,
            'totalOpenCount' => $totalOpenCount,
        ]);
    }

    /**
     * Manually trigger/refresh a risk assessment for an open obligation.
     */
    public function runAssessment(Request $request, SalaryObligation $salaryObligation, RiskAssessmentService $service): RedirectResponse
    {
        Gate::authorize('view', $salaryObligation);

        if ($salaryObligation->isPaid()) {
            return back()->with('info', 'Paid obligations retain their final historical assessment and are not re-calculated.');
        }

        $assessment = $service->assess($salaryObligation, force: true);

        return back()->with('success', "Risk assessment calculated for {$salaryObligation->employee->name}: Score {$assessment->score}/85 ({$assessment->risk_level->label()}).");
    }
}
