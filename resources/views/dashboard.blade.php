<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Payroll Dashboard') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Welcome back, {{ Auth::user()->name }}. Overview of your workforce commitments and default risk projections.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a
                    href="{{ route('salary-obligations.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm"
                >
                    + Schedule Salary
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Summary 5 Metric Cards (Section 9.2) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- 1. Active Employees -->
                <a href="{{ route('employees.index') }}" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition group">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block group-hover:text-indigo-600">Active Employees</span>
                    <div class="text-2xl font-black text-gray-900 mt-2">
                        {{ number_format($totalActiveEmployees) }}
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">Enrolled workforce</span>
                </a>

                <!-- 2. Amount Due Soon -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">Due Soon ({{ $windowDays }}d)</span>
                    <div class="text-2xl font-black text-gray-900 mt-2">
                        {{ \App\Support\Money::format($amountDueSoonKobo) }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Payable within {{ $windowDays }} days</span>
                </div>

                <!-- 3. Upcoming Payments -->
                <a href="{{ route('salary-obligations.index', ['status' => 'upcoming']) }}" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-amber-300 hover:shadow-md transition group">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block group-hover:text-amber-600">Upcoming Payments</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ number_format($upcomingCount) }}
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">Scheduled commitments</span>
                </a>

                <!-- 4. Overdue Payments (Prominent) -->
                <a href="{{ route('salary-obligations.index', ['status' => 'overdue']) }}" class="bg-white p-5 rounded-xl border {{ $overdueCount > 0 ? 'border-red-300 bg-red-50/30 ring-2 ring-red-100' : 'border-gray-200' }} shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-red-600 uppercase tracking-wider block">Overdue Payments</span>
                        @if ($overdueCount > 0)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-red-100 text-red-800">ALERT</span>
                        @endif
                    </div>
                    <div class="text-2xl font-black text-red-700 mt-2">
                        {{ number_format($overdueCount) }}
                    </div>
                    <span class="text-xs text-red-600 mt-1 block font-medium">Unpaid past due date</span>
                </a>

                <!-- 5. High-Risk Obligations (Prominent) -->
                <a href="{{ route('risk-assessments.index', ['risk_level' => 'high']) }}" class="bg-white p-5 rounded-xl border {{ $highRiskCount > 0 ? 'border-red-300 bg-red-50/30 ring-2 ring-red-100' : 'border-gray-200' }} shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-red-600 uppercase tracking-wider block">High Default Risk</span>
                        @if ($highRiskCount > 0)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-red-100 text-red-800">$\ge 57$</span>
                        @endif
                    </div>
                    <div class="text-2xl font-black text-red-700 mt-2">
                        {{ number_format($highRiskCount) }}
                    </div>
                    <span class="text-xs text-red-600 mt-1 block font-medium">Elevated default probability</span>
                </a>
            </div>

            <!-- Quick Navigation Strip (Section 9.2) -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-2 text-indigo-900 text-sm font-semibold">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Quick Operations:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('employees.create') }}" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        + Add Employee
                    </a>
                    <a href="{{ route('salary-obligations.create') }}" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        + Schedule Obligation
                    </a>
                    <a href="{{ route('payments.index') }}" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        Payment History
                    </a>
                    <a href="{{ route('risk-assessments.index') }}" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        Risk Predictions
                    </a>
                    <a href="{{ Route::has('reports.index') ? route('reports.index') : url('/reports') }}" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        Audit Reports
                    </a>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column (Tables: Overdue & Upcoming) -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Overdue Payments Table (Visually Prominent Red) -->
                    @if ($overdueObligations->isNotEmpty())
                        <div class="bg-white shadow-sm rounded-xl border-2 border-red-200 overflow-hidden">
                            <div class="px-6 py-4 bg-red-50 border-b border-red-200 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="flex h-2.5 w-2.5 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-600"></span>
                                    </span>
                                    <h3 class="font-bold text-base text-red-900">Overdue Payments Requiring Immediate Settlement</h3>
                                </div>
                                <a href="{{ route('salary-obligations.index', ['status' => 'overdue']) }}" class="text-xs font-bold text-red-700 hover:text-red-900 hover:underline">
                                    View All ({{ $overdueCount }}) &rarr;
                                </a>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Days Overdue</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($overdueObligations as $ob)
                                            <tr class="bg-red-50/30 hover:bg-red-50/60 transition">
                                                <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                                    {{ $ob->employee->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap font-black text-red-700">
                                                    {{ $ob->formatted_amount }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-gray-700 font-medium">
                                                    {{ $ob->due_date->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-red-600">
                                                    {{ abs($ob->daysRemaining()) }} days overdue
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <x-status-badge status="overdue" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                                    <a
                                                        href="{{ route('salary-obligations.payments.create', $ob) }}"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 shadow-sm transition"
                                                    >
                                                        Pay Now
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Upcoming Payments Table (Section 9.2: Employee, Amount, Due Date, Days Remaining, Risk) -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-bold text-base text-gray-900">Upcoming Salary Payments</h3>
                            <a href="{{ route('salary-obligations.index', ['status' => 'upcoming']) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">
                                View All ({{ $upcomingCount }}) &rarr;
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Days Remaining</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Risk Level</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($upcomingObligations as $obligation)
                                        @php
                                            $days = $obligation->daysRemaining();
                                            $assessment = $obligation->latestRiskAssessment;
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-bold text-gray-900">{{ $obligation->employee->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $obligation->employee->job_title ?: 'Worker' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                                {{ $obligation->formatted_amount }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                                {{ $obligation->due_date->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-medium">
                                                @if ($days === 0)
                                                    <span class="text-amber-700 font-bold">Due Today</span>
                                                @elseif ($days > 0)
                                                    <span class="text-gray-700">In {{ $days }} days</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($assessment)
                                                    <x-risk-badge :level="$assessment->risk_level->value" :score="$assessment->score" />
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Evaluating</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2 text-xs font-medium">
                                                <a
                                                    href="{{ route('salary-obligations.payments.create', $obligation) }}"
                                                    class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition"
                                                >
                                                    Pay
                                                </a>
                                                <a href="{{ route('salary-obligations.show', $obligation) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                                <p class="text-sm font-medium">No upcoming salary obligations scheduled.</p>
                                                <div class="mt-2">
                                                    <a href="{{ route('salary-obligations.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                                                        + Schedule an upcoming salary payment
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Unread Reminders Panel - Section 9.2) -->
                <div class="space-y-6">

                    <!-- Unread Reminders Panel -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <h3 class="font-bold text-base text-gray-900">Unread Reminders</h3>
                            </div>
                            <a href="{{ route('reminders.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">
                                View All &rarr;
                            </a>
                        </div>

                        <div class="p-4 space-y-3">
                            @forelse ($unreadReminders as $reminder)
                                @php $isOverdue = $reminder->isOverdue(); @endphp
                                <div class="p-3.5 rounded-lg border {{ $isOverdue ? 'bg-red-50/50 border-red-200' : 'bg-gray-50 border-gray-200' }} text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $isOverdue ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $isOverdue ? 'Overdue Alert' : 'Upcoming' }}
                                        </span>
                                        <span class="text-gray-400 text-[10px]">{{ $reminder->created_at->diffForHumans() }}</span>
                                    </div>

                                    <p class="font-medium text-gray-900 leading-snug">
                                        {{ $reminder->message }}
                                    </p>

                                    <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                                        <a
                                            href="{{ route('salary-obligations.payments.create', $reminder->salaryObligation) }}"
                                            class="font-bold text-green-700 hover:text-green-900 hover:underline"
                                        >
                                            Disburse Payment &rarr;
                                        </a>

                                        <form method="POST" action="{{ route('reminders.read', $reminder) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-gray-500 hover:text-gray-800 hover:underline">
                                                Dismiss
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center text-gray-400 text-xs">
                                    <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path></svg>
                                    <p class="mt-2 font-medium text-gray-700">All caught up!</p>
                                    <p class="mt-0.5 text-gray-400">No unread alerts or notifications.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Quick Guidance Card -->
                    <div class="bg-gradient-to-br from-indigo-700 to-indigo-900 text-white p-5 rounded-xl shadow-sm">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-200">Default Risk Scoring Model</span>
                        <h4 class="font-bold text-base mt-1">Smart Default Prevention</h4>
                        <p class="text-xs text-indigo-100 mt-2 leading-relaxed">
                            Scores evaluate historical payment delays, current employer debt load, outstanding ratios, and due date proximity on an 85-point scale.
                        </p>
                        <div class="mt-4 pt-3 border-t border-indigo-600/60 flex items-center justify-between text-xs">
                            <span class="text-indigo-200">Algorithm 3.8.7</span>
                            <a href="{{ route('risk-assessments.index') }}" class="font-bold text-white hover:underline">
                                Inspect Factors &rarr;
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
