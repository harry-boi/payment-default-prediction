<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        {{ __('Salary Obligation') }}: {{ $salaryObligation->formatted_amount }}
                    </h2>
                    <x-status-badge :status="$salaryObligation->status->value" />
                </div>
                <p class="text-sm text-gray-500 mt-1">Due for {{ $salaryObligation->employee->name }} on {{ $salaryObligation->due_date->format('F d, Y') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('salary-obligations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    &larr; Back to List
                </a>
                @if (! $salaryObligation->isPaid())
                    <a href="{{ url('/salary-obligations/' . $salaryObligation->id . '/payments') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition shadow-sm">
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
