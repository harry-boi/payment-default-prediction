<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Salary Obligations') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Track scheduled salary commitments, payment deadlines, and overdue statuses.</p>
            </div>
            <div>
                <a href="{{ route('salary-obligations.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Create Salary Schedule') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Status Filter Tabs -->
                <div class="flex items-center space-x-1 overflow-x-auto pb-1 md:pb-0 text-xs font-semibold">
                    <a href="{{ route('salary-obligations.index', array_merge(request()->except('status', 'page'), ['status' => ''])) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ !request('status') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        All
                    </a>
                    <a href="{{ route('salary-obligations.index', array_merge(request()->except('status', 'page'), ['status' => 'upcoming'])) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ request('status') === 'upcoming' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        Upcoming
                    </a>
                    <a href="{{ route('salary-obligations.index', array_merge(request()->except('status', 'page'), ['status' => 'overdue'])) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ request('status') === 'overdue' ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        Overdue
                    </a>
                    <a href="{{ route('salary-obligations.index', array_merge(request()->except('status', 'page'), ['status' => 'paid'])) }}" 
                       class="px-3 py-1.5 rounded-lg transition {{ request('status') === 'paid' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        Paid
                    </a>
                </div>

                <!-- Employee Filter Dropdown -->
                <form method="GET" action="{{ route('salary-obligations.index') }}" class="flex items-center space-x-2">
                    @if (request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    <label for="employee_id" class="text-xs font-medium text-gray-500 whitespace-nowrap">Filter by Employee:</label>
                    <select id="employee_id" name="employee_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                        <option value="">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>

                    @if (request('employee_id') || request('status'))
                        <a href="{{ route('salary-obligations.index') }}" class="text-xs text-gray-500 hover:text-gray-700 underline whitespace-nowrap ml-1">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Obligations Table -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                @if ($obligations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Employee</th>
                                    <th scope="col" class="px-6 py-3.5">Amount</th>
                                    <th scope="col" class="px-6 py-3.5">Due Date</th>
                                    <th scope="col" class="px-6 py-3.5">Period</th>
                                    <th scope="col" class="px-6 py-3.5">Frequency</th>
                                    <th scope="col" class="px-6 py-3.5">Status</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($obligations as $obligation)
                                    @php
                                        $isOverdue = $obligation->isOverdue();
                                        $rowClass = $isOverdue ? 'bg-red-50/50 hover:bg-red-50 border-l-4 border-l-red-500' : 'hover:bg-gray-50';
                                    @endphp
                                    <tr class="{{ $rowClass }} transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-semibold text-gray-900">{{ $obligation->employee->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $obligation->employee->job_title ?: 'Worker' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold {{ $isOverdue ? 'text-red-700' : 'text-gray-900' }}">
                                            {{ $obligation->formatted_amount }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $obligation->due_date->format('M d, Y') }}</div>
                                            @if ($obligation->isUpcoming())
                                                <div class="text-xs text-amber-600 font-medium">
                                                    @php $days = $obligation->daysRemaining(); @endphp
                                                    {{ $days === 0 ? 'Due Today' : "In {$days} days" }}
                                                </div>
                                            @elseif ($isOverdue)
                                                <div class="text-xs text-red-600 font-bold">
                                                    @php $daysPast = abs($obligation->daysRemaining()); @endphp
                                                    {{ "{$daysPast} days overdue" }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                            {{ $obligation->payment_period ?: '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {{ $obligation->frequency->label() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <x-status-badge :status="$obligation->status->value" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2 text-xs font-medium">
                                            <a href="{{ route('salary-obligations.show', $obligation) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                View
                                            </a>
                                            @if (! $obligation->isPaid())
                                                <span class="text-gray-300">|</span>
                                                <a href="{{ route('salary-obligations.payments.create', $obligation) }}" class="font-bold text-green-700 hover:text-green-900 hover:underline">
                                                    Record Payment
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($obligations->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $obligations->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 px-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">No salary obligations found</h3>
                        <p class="mt-1 text-sm text-gray-500 max-w-sm mx-auto">
                            @if (request('status') || request('employee_id'))
                                No records matched your current filters. Try resetting the filters.
                            @else
                                Schedule your first salary obligation to monitor approaching deadlines and detect default risks early.
                            @endif
                        </p>
                        <div class="mt-6">
                            @if (request('status') || request('employee_id'))
                                <a href="{{ route('salary-obligations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                    Clear Filters
                                </a>
                            @else
                                <a href="{{ route('salary-obligations.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                                    + Schedule Salary
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
