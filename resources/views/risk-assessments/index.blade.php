<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Default Risk Assessment') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Multi-factor default prediction engine for active salary commitments.</p>
            </div>
            <div>
                <a href="{{ route('salary-obligations.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    View Salary Obligations &rarr;
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Risk Level Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- High Risk (Prominent) -->
                <div class="bg-white p-5 rounded-xl border {{ $highRiskCount > 0 ? 'border-red-300 ring-2 ring-red-100 bg-red-50/30' : 'border-gray-200' }} shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-red-600 uppercase tracking-wider block">High Default Risk</span>
                        <span class="px-2 py-0.5 rounded text-xs font-black bg-red-100 text-red-800">57 - 85 pts</span>
                    </div>
                    <div class="text-3xl font-black text-red-700 mt-2">
                        {{ $highRiskCount }}
                    </div>
                    <span class="text-xs text-red-600 mt-1 block font-medium">Critical attention required</span>
                </div>

                <!-- Medium Risk -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Medium Default Risk</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">29 - 56 pts</span>
                    </div>
                    <div class="text-3xl font-black text-amber-600 mt-2">
                        {{ $mediumRiskCount }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Approaching due or moderate delay</span>
                </div>

                <!-- Low Risk -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-green-600 uppercase tracking-wider block">Low Default Risk</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">0 - 28 pts</span>
                    </div>
                    <div class="text-3xl font-black text-green-600 mt-2">
                        {{ $lowRiskCount }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Reliable payment trajectory</span>
                </div>

                <!-- Total Open Assessed -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Open Assessed</span>
                    <div class="text-3xl font-black text-indigo-700 mt-2">
                        {{ $totalOpenCount }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Active upcoming & overdue commitments</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('risk-assessments.index') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 items-end">
                    <!-- Employee Filter -->
                    <div>
                        <label for="employee_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Filter by Employee</label>
                        <select id="employee_id" name="employee_id" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Risk Level Filter -->
                    <div>
                        <label for="risk_level" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Risk Classification</label>
                        <select id="risk_level" name="risk_level" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Risk Levels</option>
                            <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>High Risk (57 - 85)</option>
                            <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Medium Risk (29 - 56)</option>
                            <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Low Risk (0 - 28)</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-xs font-semibold uppercase tracking-wider hover:bg-gray-800 transition">
                            Apply Filter
                        </button>
                        @if (request()->hasAny(['employee_id', 'risk_level']))
                            <a href="{{ route('risk-assessments.index') }}" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Risk Assessment Table (Section 9.2: Employee, Obligation, Score, Risk Level, Main Factors, Run Assessment) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Employee
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Obligation & Due Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Risk Score
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Level
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Main Contributing Factors
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($obligations as $obligation)
                                @php
                                    $assessment = $obligation->latestRiskAssessment;
                                    $isHigh = $assessment?->isHigh() ?? false;
                                    $rowClass = $isHigh ? 'bg-red-50/40 hover:bg-red-50/70' : 'hover:bg-gray-50';
                                @endphp
                                <tr class="{{ $rowClass }} transition" x-data="{ openFactors: false }">
                                    <!-- Employee -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $obligation->employee->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $obligation->employee->job_title ?: 'Worker' }}</div>
                                    </td>

                                    <!-- Obligation Details -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $obligation->formatted_amount }}</div>
                                        <div class="text-xs text-gray-500 flex items-center space-x-1 mt-0.5">
                                            <span>Due: {{ $obligation->due_date->format('M d, Y') }}</span>
                                            <span>&bull;</span>
                                            <x-status-badge :status="$obligation->status->value" />
                                        </div>
                                    </td>

                                    <!-- Risk Score -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($assessment)
                                            <div class="flex items-center space-x-2">
                                                <span class="text-base font-black {{ $isHigh ? 'text-red-700' : ($assessment->isMedium() ? 'text-amber-700' : 'text-green-700') }}">
                                                    {{ $assessment->score }}
                                                </span>
                                                <span class="text-xs text-gray-400 font-semibold">/ 85</span>
                                            </div>
                                            <!-- Mini Score Bar -->
                                            <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1 overflow-hidden">
                                                <div
                                                    class="h-1.5 rounded-full {{ $isHigh ? 'bg-red-600' : ($assessment->isMedium() ? 'bg-amber-500' : 'bg-green-500') }}"
                                                    style="width: {{ min(100, round(($assessment->score / 85) * 100)) }}%"
                                                ></div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Not evaluated</span>
                                        @endif
                                    </td>

                                    <!-- Risk Level Badge -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($assessment)
                                            <x-risk-badge :level="$assessment->risk_level->value" :score="$assessment->score" />
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Main Factors (with Alpine toggle) -->
                                    <td class="px-6 py-4 text-xs text-gray-700 max-w-md">
                                        @if ($assessment && !empty($assessment->factors['sentences']))
                                            <div class="space-y-1">
                                                <!-- Primary factor visible directly -->
                                                <div class="font-medium text-gray-900">
                                                    {{ $assessment->factors['sentences'][0] ?? 'Evaluation recorded' }}
                                                </div>

                                                <!-- Toggle button for full breakdown -->
                                                <button
                                                    type="button"
                                                    @click="openFactors = !openFactors"
                                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center mt-1 focus:outline-none"
                                                >
                                                    <span x-text="openFactors ? 'Hide all 4 factors' : 'View all 4 component factors (' + ({{ count($assessment->factors['sentences']) }}) + ')'"></span>
                                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform" :class="openFactors ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                </button>

                                                <!-- Collapsible full factors list -->
                                                <div x-show="openFactors" x-cloak class="mt-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200 space-y-1.5 text-xs">
                                                    @foreach ($assessment->factors['sentences'] as $sentence)
                                                        <div class="flex items-start space-x-1.5">
                                                            <span class="text-indigo-600 font-bold">&bull;</span>
                                                            <span class="text-gray-800">{{ $sentence }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">No factor details</span>
                                        @endif
                                    </td>

                                    <!-- Actions: Run assessment button -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                        <form method="POST" action="{{ route('salary-obligations.risk-assessment', $obligation) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-600 shadow-sm transition"
                                                title="Recalculate risk score for this obligation"
                                            >
                                                <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Run assessment
                                            </button>
                                        </form>

                                        <a href="{{ route('salary-obligations.show', $obligation) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="max-w-sm mx-auto">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                            </svg>
                                            <h4 class="mt-2 text-sm font-semibold text-gray-900">No open obligations to assess</h4>
                                            <p class="mt-1 text-xs text-gray-500">
                                                All salary obligations have either been settled or no active commitments exist.
                                            </p>
                                            <div class="mt-4">
                                                <a href="{{ route('salary-obligations.create') }}" class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 text-xs font-semibold text-white rounded-lg hover:bg-indigo-700 transition">
                                                    + Schedule New Salary
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
