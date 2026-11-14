<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Http\Requests\StoreSalaryObligationRequest;
use App\Models\Employee;
use App\Models\SalaryObligation;
use App\Services\ObligationStatusSync;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SalaryObligationController extends Controller
{
    /**
     * Display a listing of salary obligations with filters.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Locked decision #4 & 6.2: Refresh overdue/upcoming statuses whenever accessed
        ObligationStatusSync::syncForUser($user);

        $query = SalaryObligation::whereHas('employee', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('employee');

        // Filter by status (upcoming, overdue, paid)
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by employee
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        $obligations = $query->orderBy('due_date', 'asc')
            ->paginate(15)
            ->withQueryString();

        $employees = $user->employees()->orderBy('name')->get();

        return view('salary-obligations.index', compact('obligations', 'employees'));
    }

    /**
     * Show the form for creating a new salary obligation.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        // Only active employees can receive new salary obligations (Section 9.1)
        $employees = $user->employees()
            ->where('status', EmployeeStatus::Active)
            ->orderBy('name')
            ->get();

        $selectedEmployee = null;
        if ($employeeId = $request->query('employee_id')) {
            $selectedEmployee = $employees->firstWhere('id', (int) $employeeId);
        }

        return view('salary-obligations.create', compact('employees', 'selectedEmployee'));
    }

    /**
     * Store a newly created salary obligation in storage.
     */
    public function store(StoreSalaryObligationRequest $request): RedirectResponse
    {
        $employee = $request->user()->employees()->findOrFail($request->validated('employee_id'));

        $amountKobo = Money::toKobo($request->validated('amount'));
        $dueDate = Carbon::parse($request->validated('due_date'));
        $status = ObligationStatusSync::determineStatus($dueDate);
        $period = $request->validated('payment_period') ?: $dueDate->format('F Y');

        $obligation = $employee->salaryObligations()->create([
            'amount' => $amountKobo,
            'due_date' => $dueDate->toDateString(),
            'frequency' => $employee->salary_frequency->value, // copied from employee (Section 9.1)
            'status' => $status,
            'payment_period' => $period,
            'notes' => $request->validated('notes'),
        ]);

        return redirect()
            ->route('salary-obligations.index')
            ->with('success', "Salary obligation of {$obligation->formatted_amount} for {$employee->name} scheduled successfully.");
    }

    /**
     * Display the specified salary obligation.
     */
    public function show(SalaryObligation $salaryObligation): View
    {
        Gate::authorize('view', $salaryObligation);

        $salaryObligation->load(['employee', 'payments', 'riskAssessments']);

        return view('salary-obligations.show', compact('salaryObligation'));
    }
}
