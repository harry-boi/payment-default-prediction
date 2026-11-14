<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Payroll & Risk Reports') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Structured audit reports with count and monetary total aggregations.</p>
            </div>
            <div>
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 shadow-sm transition"
                >
                    <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print / Export Report
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $typeLabels = [
            'upcoming' => 'Upcoming Salary Obligations',
            'paid' => 'Settled & Paid Commitments',
            'overdue' => 'Overdue / Delayed Obligations',
            'high_risk' => 'High-Risk Default Commitments',
        ];
        $currentTypeLabel = $typeLabels[$type] ?? 'Salary Obligations Report';
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Type Selector Tabs (Section 9.2: 4 types) -->
            <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                <nav class="grid grid-cols-2 sm:grid-cols-4 gap-2" aria-label="Report Types">
                    <!-- 1. Upcoming -->
                    <a
                        href="{{ route('reports.index', array_merge(request()->query(), ['type' => 'upcoming'])) }}"
                        class="flex flex-col items-center justify-center p-3 rounded-lg text-xs font-bold transition text-center {{ $type === 'upcoming' ? 'bg-amber-100 text-amber-900 border border-amber-300 shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        <span>Upcoming Obligations</span>
                        <span class="text-[10px] font-normal text-amber-800 mt-0.5">Due in future</span>
                    </a>

                    <!-- 2. Paid -->
                    <a
                        href="{{ route('reports.index', array_merge(request()->query(), ['type' => 'paid'])) }}"
                        class="flex flex-col items-center justify-center p-3 rounded-lg text-xs font-bold transition text-center {{ $type === 'paid' ? 'bg-green-100 text-green-900 border border-green-300 shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        <span>Paid Commitments</span>
                        <span class="text-[10px] font-normal text-green-800 mt-0.5">Settled payouts</span>
                    </a>

                    <!-- 3. Overdue -->
                    <a
                        href="{{ route('reports.index', array_merge(request()->query(), ['type' => 'overdue'])) }}"
                        class="flex flex-col items-center justify-center p-3 rounded-lg text-xs font-bold transition text-center {{ $type === 'overdue' ? 'bg-red-100 text-red-900 border border-red-300 shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        <span>Overdue Obligations</span>
                        <span class="text-[10px] font-normal text-red-800 mt-0.5">Past due date</span>
                    </a>

                    <!-- 4. High Risk -->
                    <a
                        href="{{ route('reports.index', array_merge(request()->query(), ['type' => 'high_risk'])) }}"
                        class="flex flex-col items-center justify-center p-3 rounded-lg text-xs font-bold transition text-center {{ $type === 'high_risk' ? 'bg-red-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        <span>High-Risk Obligations</span>
                        <span class="text-[10px] font-normal {{ $type === 'high_risk' ? 'text-red-100' : 'text-red-600' }} mt-0.5">Score $\ge 57$</span>
                    </a>
                </nav>
            </div>

            <!-- Filter Criteria (Employee filter and date range on due_date) -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm print:hidden">
                <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <!-- Employee Filter -->
                    <div>
                        <label for="employee_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                            Employee
                        </label>
                        <select id="employee_id" name="employee_id" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ (string) request('employee_id') === (string) $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="from" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                            Due Date From
                        </label>
                        <input
                            type="date"
                            id="from"
                            name="from"
                            value="{{ request('from') }}"
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="to" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                            Due Date To
                        </label>
                        <input
                            type="date"
                            id="to"
                            name="to"
                            value="{{ request('to') }}"
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2">
                        <button
                            type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-gray-900 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-gray-800 transition"
                        >
                            Filter Report
                        </button>
                        @if (request()->hasAny(['employee_id', 'from', 'to']))
                            <a
                                href="{{ route('reports.index', ['type' => $type]) }}"
                                class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Report Summary Header (Visible in UI and Print) -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider block">Generated Audit Report</span>
                    <h3 class="text-lg font-black text-gray-900 mt-0.5">{{ $currentTypeLabel }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Scope:
                        @if (request('from') || request('to'))
                            Scheduled between <strong>{{ request('from') ?: 'Beginning' }}</strong> and <strong>{{ request('to') ?: 'Present/Future' }}</strong>
                        @else
                            All recorded dates
                        @endif
                        &bull;
                        Employee:
                        <strong>
                            @if (request('employee_id') && ($selected = $employees->firstWhere('id', request('employee_id'))))
                                {{ $selected->name }}
                            @else
                                All Employees
                            @endif
                        </strong>
                    </p>
                </div>
                <div class="sm:text-right">
                    <span class="text-xs text-gray-500 block">Report Generation Timestamp</span>
                    <span class="text-xs font-mono font-bold text-gray-800 mt-0.5 block">{{ now()->format('F d, Y - H:i:s') }}</span>
                </div>
            </div>

            <!-- Report Records Table (Section 9.2) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Scheduled Due Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Period</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Frequency</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Obligation Amount</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Risk Level</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider print:hidden">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($records as $record)
                                @php
                                    $isOverdue = $record->isOverdue();
                                    $assessment = $record->latestRiskAssessment;
                                    $isHighRisk = $assessment?->isHigh() ?? false;
                                    $rowClass = ($isOverdue || $isHighRisk) ? 'bg-red-50/40 hover:bg-red-50/70' : 'hover:bg-gray-50';
                                @endphp
                                <tr class="{{ $rowClass }} transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $record->employee->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->employee->job_title ?: 'Worker' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                        {{ $record->due_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                        <div class="font-medium text-gray-800">{{ $record->payment_period ?: '—' }}</div>
                                        @if ($record->notes)
                                            <div class="text-[11px] text-gray-500 italic mt-0.5">{{ $record->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                        {{ $record->frequency->label() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-black {{ ($isOverdue || $isHighRisk) ? 'text-red-700' : 'text-gray-900' }}">
                                        {{ $record->formatted_amount }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-status-badge :status="$record->status->value" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($assessment)
                                            <x-risk-badge :level="$assessment->risk_level->value" :score="$assessment->score" />
                                        @else
                                            <span class="text-xs text-gray-400 italic">Unassessed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold print:hidden space-x-2">
                                        <a href="{{ route('salary-obligations.show', $record) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                            View
                                        </a>
                                        @if (! $record->isPaid())
                                            <span class="text-gray-300">|</span>
                                            <a href="{{ route('salary-obligations.payments.create', $record) }}" class="text-green-700 hover:text-green-900 hover:underline font-bold">
                                                Pay
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <div class="max-w-sm mx-auto">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <h4 class="mt-2 text-sm font-semibold text-gray-900">No report records found</h4>
                                            <p class="mt-1 text-xs text-gray-500">
                                                No salary obligations matched the selected report type and filter criteria.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Aggregated Totals at Bottom (Section 9.2: with a count and total amount at the bottom) -->
                <div class="bg-gray-50 border-t-2 border-gray-300 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center space-x-2 text-sm font-bold text-gray-900">
                        <span>Total Records Filtered:</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-gray-200 text-gray-800 text-xs font-black">
                            {{ number_format($totalCount) }} {{ Str::plural('obligation', $totalCount) }}
                        </span>
                    </div>

                    <div class="sm:text-right">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Aggregate Monetary Value</span>
                        <div class="text-2xl font-black text-indigo-900 mt-0.5">
                            {{ \App\Support\Money::format($totalAmountKobo) }}
                        </div>
                    </div>
                </div>

                @if ($records->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 print:hidden">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
