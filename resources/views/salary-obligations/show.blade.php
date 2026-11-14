<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        {{ __('Salary Obligation') }}: {{ $salaryObligation->formatted_amount }}
                    </h2>
                    <x-status-badge :status="$salaryObligation->status->value" />
                    @if ($salaryObligation->latestRiskAssessment)
                        <x-risk-badge :level="$salaryObligation->latestRiskAssessment->risk_level->value" :score="$salaryObligation->latestRiskAssessment->score" />
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">Due for {{ $salaryObligation->employee->name }} on {{ $salaryObligation->due_date->format('F d, Y') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('salary-obligations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    &larr; Back to List
                </a>
                @if (! $salaryObligation->isPaid())
                    <a href="{{ route('salary-obligations.payments.create', $salaryObligation) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition shadow-sm">
                        Record Payment
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Summary Grid -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-base text-gray-900">Obligation Details</h3>
                    <span class="text-xs text-gray-500">ID: #{{ $salaryObligation->id }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Amount Due</span>
                        <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $salaryObligation->formatted_amount }}</span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Due Date</span>
                        <span class="text-base font-bold text-gray-900 mt-1 block">{{ $salaryObligation->due_date->format('M d, Y') }}</span>
                        @if ($salaryObligation->isUpcoming())
                            <span class="text-xs text-amber-600 font-medium">Due in {{ $salaryObligation->daysRemaining() }} days</span>
                        @elseif ($salaryObligation->isOverdue())
                            <span class="text-xs text-red-600 font-bold">{{ abs($salaryObligation->daysRemaining()) }} days overdue</span>
                        @else
                            <span class="text-xs text-green-600 font-semibold">Settled</span>
                        @endif
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Payment Period</span>
                        <span class="text-sm font-medium text-gray-800 mt-1 block">{{ $salaryObligation->payment_period ?: 'Not specified' }}</span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Frequency</span>
                        <span class="text-sm font-medium text-gray-800 mt-1 block">{{ $salaryObligation->frequency->label() }}</span>
                    </div>
                </div>

                @if ($salaryObligation->notes)
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-sm text-gray-600">
                        <strong class="font-semibold text-gray-800">Notes:</strong> {{ $salaryObligation->notes }}
                    </div>
                @endif
            </div>

            <!-- Risk Assessment Factors Section (Section 7 & 11 Phase 5) -->
            @php $assessment = $salaryObligation->latestRiskAssessment; @endphp
            <div class="bg-white shadow-sm rounded-xl border {{ $assessment && $assessment->isHigh() ? 'border-red-300' : 'border-gray-200' }} overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <h3 class="font-bold text-base text-gray-900">Default Risk Assessment</h3>
                        @if ($assessment)
                            <x-risk-badge :level="$assessment->risk_level->value" :score="$assessment->score" />
                        @endif
                    </div>
                    @if (! $salaryObligation->isPaid())
                        <form method="POST" action="{{ route('salary-obligations.risk-assessment', $salaryObligation) }}" class="inline">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm"
                            >
                                <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Run Assessment Now
                            </button>
                        </form>
                    @endif
                </div>

                <div class="p-6">
                    @if ($assessment)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-6 border-b border-gray-100">
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Risk Score</span>
                                <div class="flex items-baseline space-x-1 mt-1">
                                    <span class="text-2xl font-black {{ $assessment->isHigh() ? 'text-red-700' : ($assessment->isMedium() ? 'text-amber-700' : 'text-green-700') }}">
                                        {{ $assessment->score }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-bold">/ 85 maximum</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Classification</span>
                                <span class="text-sm font-bold text-gray-900 mt-1 block">{{ $assessment->risk_level->label() }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Last Evaluated</span>
                                <span class="text-xs font-medium text-gray-700 mt-1 block">{{ $assessment->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Assessment Model</span>
                                <span class="text-xs font-medium text-gray-600 mt-1 block">Algorithm 3.8.7 (4 Factors)</span>
                            </div>
                        </div>

                        <!-- 4 Factor Sentences Breakdown -->
                        <div class="mt-6">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Component Scoring Breakdown</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                @if (!empty($assessment->factors['sentences']))
                                    @php
                                        $factorTitles = [
                                            0 => 'A. Historical Late-Payment Rate (Employee)',
                                            1 => 'B. Currently Overdue Obligations (Employer)',
                                            2 => 'C. Outstanding Balance Ratio (Employer)',
                                            3 => 'D. Due Date Proximity (Obligation)',
                                        ];
                                    @endphp
                                    @foreach ($assessment->factors['sentences'] as $idx => $sentence)
                                        <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200">
                                            <span class="font-bold text-indigo-900 block mb-1">{{ $factorTitles[$idx] ?? 'Factor #' . ($idx + 1) }}</span>
                                            <p class="text-gray-700">{{ $sentence }}</p>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-500 mb-3">No risk assessment has been recorded for this obligation yet.</p>
                            <form method="POST" action="{{ route('salary-obligations.risk-assessment', $salaryObligation) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-xs font-semibold text-white rounded-lg hover:bg-indigo-700 transition">
                                    Calculate Initial Risk Score
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settlement & Payment Details (If Paid) -->
            @php $latestPayment = $salaryObligation->payments()->latest('paid_at')->first(); @endphp
            @if ($salaryObligation->isPaid() && $latestPayment)
                <div class="bg-white shadow-sm rounded-xl border border-green-200 overflow-hidden">
                    <div class="px-6 py-4 bg-green-50 border-b border-green-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <h3 class="font-bold text-sm text-green-900">Payment Settlement Record</h3>
                        </div>
                        <x-status-badge :status="$latestPayment->status->value" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-6 text-sm">
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Amount Disbursed</span>
                            <span class="font-bold text-gray-900 text-base mt-1 block">{{ $latestPayment->formatted_amount }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Disbursed Date</span>
                            <span class="font-semibold text-gray-900 mt-1 block">{{ $latestPayment->paid_at->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Delay Classification</span>
                            <span class="font-semibold mt-1 block {{ $latestPayment->isOnTime() ? 'text-green-700' : 'text-red-700' }}">
                                {{ $latestPayment->isOnTime() ? '0 days delay (On Time)' : "{$latestPayment->delay_days} days late" }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Receipt / Reference</span>
                            <span class="font-mono text-gray-800 mt-1 block">{{ $latestPayment->reference ?: 'None recorded' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Employee Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Employee Details</span>
                    <h4 class="text-lg font-bold text-gray-900 mt-1">{{ $salaryObligation->employee->name }}</h4>
                    <p class="text-xs text-gray-500">{{ $salaryObligation->employee->job_title ?: 'Worker' }} &bull; Contact: {{ $salaryObligation->employee->phone ?: 'No phone provided' }}</p>
                </div>
                <div>
                    <a href="{{ route('employees.show', $salaryObligation->employee) }}" class="inline-flex items-center px-3.5 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        View Employee Profile &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
