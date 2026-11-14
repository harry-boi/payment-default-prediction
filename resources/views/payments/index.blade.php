<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Payment History') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Audit log of all completed salary payments and delay tracking.</p>
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

            <!-- Summary Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Disbursed</span>
                    <div class="text-2xl font-black text-gray-900 mt-1">
                        {{ \App\Support\Money::format($totalPaidKobo) }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Cumulative salary payout</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Payments</span>
                    <div class="text-2xl font-black text-indigo-700 mt-1">
                        {{ number_format($totalPaymentsCount) }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Completed payment transactions</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Paid On Time</span>
                    <div class="text-2xl font-black text-green-600 mt-1">
                        {{ number_format($onTimeCount) }}
                    </div>
                    <span class="text-xs text-green-700 mt-1 block">0 days delay</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Paid Late</span>
                    <div class="text-2xl font-black text-red-600 mt-1">
                        {{ number_format($lateCount) }}
                    </div>
                    <span class="text-xs text-red-600 mt-1 block">Defaults / Delayed payments</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 items-end">
                    <!-- Filter by Employee -->
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

                    <!-- Filter by Status -->
                    <div>
                        <label for="status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Payment Status</label>
                        <select id="status" name="status" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="paid_on_time" {{ request('status') === 'paid_on_time' ? 'selected' : '' }}>Paid On Time</option>
                            <option value="paid_late" {{ request('status') === 'paid_late' ? 'selected' : '' }}>Paid Late</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-xs font-semibold uppercase tracking-wider hover:bg-gray-800 transition">
                            Apply Filter
                        </button>
                        @if (request()->hasAny(['employee_id', 'status']))
                            <a href="{{ route('payments.index') }}" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Payments Table (Table 3.4 & Section 9.2) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Employee
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Expected Due Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Actual Paid Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Amount Paid
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Delay Days
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Reference / Details
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($payments as $payment)
                                <tr class="hover:bg-gray-50 transition">
                                    <!-- Employee -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">
                                            {{ $payment->salaryObligation->employee->name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $payment->salaryObligation->employee->job_title ?: 'Worker' }}
                                        </div>
                                    </td>

                                    <!-- Expected Due Date -->
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                        <div class="font-medium">{{ $payment->salaryObligation->due_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $payment->salaryObligation->payment_period ?: '—' }}</div>
                                    </td>

                                    <!-- Paid Date -->
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                        {{ $payment->paid_at->format('M d, Y') }}
                                    </td>

                                    <!-- Amount Paid -->
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                        {{ $payment->formatted_amount }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-status-badge :status="$payment->status->value" />
                                    </td>

                                    <!-- Delay Days -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        @if ($payment->isOnTime())
                                            <span class="inline-flex items-center text-green-700 font-semibold">
                                                0 days (On Time)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-red-700 font-bold">
                                                {{ $payment->delay_days }} {{ Str::plural('day', $payment->delay_days) }} late
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Reference / Details -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                        @if ($payment->reference)
                                            <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-800">{{ $payment->reference }}</span>
                                        @else
                                            <span class="text-gray-400 italic">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="max-w-sm mx-auto">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <h4 class="mt-2 text-sm font-semibold text-gray-900">No payment records found</h4>
                                            <p class="mt-1 text-xs text-gray-500">
                                                @if (request()->hasAny(['employee_id', 'status']))
                                                    No payments matched your filter criteria. Try clearing filters.
                                                @else
                                                    No salary payments have been recorded yet. Select an open obligation to disburse payment.
                                                @endif
                                            </p>
                                            <div class="mt-4">
                                                <a href="{{ route('salary-obligations.index') }}" class="inline-flex items-center px-3.5 py-1.5 border border-indigo-600 text-xs font-semibold text-indigo-600 rounded-lg hover:bg-indigo-50 transition">
                                                    View Open Salary Obligations &rarr;
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($payments->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
