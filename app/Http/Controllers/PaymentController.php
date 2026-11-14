<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\SalaryObligation;
use App\Services\PaymentRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Display a paginated listing of payment history (Section 9.2).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Payment::query()
            ->whereHas('salaryObligation.employee', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['salaryObligation.employee'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        if ($request->filled('employee_id')) {
            $query->whereHas('salaryObligation', function ($q) use ($request) {
                $q->where('employee_id', $request->input('employee_id'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->paginate(15)->withQueryString();

        $employees = $user->employees()->orderBy('name')->get();

        // Calculate summary statistics
        $statsQuery = Payment::query()
            ->whereHas('salaryObligation.employee', fn ($q) => $q->where('user_id', $user->id));

        $totalPaidKobo = (int) $statsQuery->sum('amount_paid');
        $totalPaymentsCount = $statsQuery->count();
        $onTimeCount = (clone $statsQuery)->where('status', 'paid_on_time')->count();
        $lateCount = (clone $statsQuery)->where('status', 'paid_late')->count();

        return view('payments.index', compact(
            'payments',
            'employees',
            'totalPaidKobo',
            'totalPaymentsCount',
            'onTimeCount',
            'lateCount'
        ));
    }

    /**
     * Show the form for recording a payment for a salary obligation.
     */
    public function create(Request $request, SalaryObligation $salaryObligation): View|RedirectResponse
    {
        Gate::authorize('view', $salaryObligation);

        if ($salaryObligation->isPaid()) {
            return redirect()
                ->route('salary-obligations.show', $salaryObligation)
                ->with('info', 'This salary obligation has already been settled and paid.');
        }

        $salaryObligation->load('employee');

        return view('payments.create', [
            'salaryObligation' => $salaryObligation,
            'defaultPaymentDate' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Store a newly recorded payment in storage (Algorithm 3.8.5).
     */
    public function store(StorePaymentRequest $request, SalaryObligation $salaryObligation, PaymentRecorder $recorder): RedirectResponse
    {
        Gate::authorize('update', $salaryObligation);

        $payment = $recorder->record($salaryObligation, $request->validated());

        return redirect()
            ->route('payments.index')
            ->with('success', "Payment of {$payment->formatted_amount} recorded for {$salaryObligation->employee->name} ({$payment->status->label()}). Next recurring schedule updated.");
    }
}
