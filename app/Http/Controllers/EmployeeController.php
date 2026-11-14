<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\ObligationStatusSync;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $employees = $request->user()
            ->employees()
            ->latest()
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $employee = $request->user()->employees()->create([
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'job_title' => $request->validated('job_title'),
                'salary_amount' => Money::toKobo($request->validated('salary_amount')),
                'salary_frequency' => $request->validated('salary_frequency'),
                'status' => EmployeeStatus::Active,
            ]);

            // Section 6.4: If a date is given, create the employee's first obligation in the same transaction
            if (! empty($request->validated('first_payment_date'))) {
                $dueDate = Carbon::parse($request->validated('first_payment_date'));
                $employee->salaryObligations()->create([
                    'amount' => $employee->salary_amount,
                    'due_date' => $dueDate->toDateString(),
                    'frequency' => $employee->salary_frequency->value,
                    'status' => ObligationStatusSync::determineStatus($dueDate),
                    'payment_period' => $dueDate->format('F Y'),
                ]);
            }

            return $employee;
        });

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employee->name} added successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): View
    {
        Gate::authorize('view', $employee);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $employee->update([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'job_title' => $request->validated('job_title'),
            'salary_amount' => Money::toKobo($request->validated('salary_amount')),
            'salary_frequency' => $request->validated('salary_frequency'),
            'status' => $request->validated('status'),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Deactivate or reactivate an employee (no delete).
     */
    public function toggleStatus(Employee $employee): RedirectResponse
    {
        Gate::authorize('toggleStatus', $employee);

        $newStatus = $employee->isActive() ? EmployeeStatus::Inactive : EmployeeStatus::Active;
        $employee->update(['status' => $newStatus]);

        $action = $newStatus === EmployeeStatus::Active ? 'reactivated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Employee {$employee->name} was {$action} successfully.");
    }
}
