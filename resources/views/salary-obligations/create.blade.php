<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Create Salary Schedule') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Schedule a recurring or upcoming salary obligation for an active worker.</p>
            </div>
            <div>
                <a href="{{ route('salary-obligations.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition flex items-center">
                    &larr; Back to Obligations
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $employeeData = $employees->mapWithKeys(function ($emp) {
            return [$emp->id => [
                'salary' => $emp->salary_in_naira,
                'frequency' => $emp->salary_frequency->label(),
            ]];
        });
        $initialEmployeeId = old('employee_id', $selectedEmployee?->id ?? ($employees->first()?->id ?? ''));
    @endphp

    <div class="py-8" x-data="{
        employees: {{ Js::from($employeeData) }},
        selectedId: '{{ $initialEmployeeId }}',
        amount: '{{ old('amount', $selectedEmployee?->salary_in_naira ?? ($employees->first()?->salary_in_naira ?? '')) }}',
        get frequency() {
            return this.employees[this.selectedId] ? this.employees[this.selectedId].frequency : 'Monthly';
        },
        updateEmployee(id) {
            this.selectedId = id;
            if (this.employees[id]) {
                this.amount = this.employees[id].salary;
            }
        }
    }">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 sm:p-8">
                @if ($employees->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-600 mb-4">You have no active employees to schedule obligations for.</p>
                        <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            + Add Employee First
                        </a>
                    </div>
                @else
                    <form method="POST" action="{{ route('salary-obligations.store') }}" class="space-y-5">
                        @csrf

                        <!-- Employee Selection -->
                        <div>
                            <x-input-label for="employee_id" :value="__('Select Employee')" class="font-semibold text-gray-700" />
                            <select id="employee_id" name="employee_id" x-model="selectedId" @change="updateEmployee($event.target.value)" required class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ (string)$emp->id === (string)$initialEmployeeId ? 'selected' : '' }}>
                                        {{ $emp->name }} ({{ $emp->job_title ?: 'Worker' }} &bull; {{ $emp->formatted_salary }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('employee_id')" class="mt-1" />
                        </div>

                        <!-- Amount in Naira -->
                        <div>
                            <x-input-label for="amount" :value="__('Obligation Amount (in ₦ Naira)')" class="font-semibold text-gray-700" />
                            <div class="relative mt-1 rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm font-bold">₦</span>
                                </div>
                                <input id="amount" name="amount" type="text" x-model="amount" required class="block w-full rounded-lg border-gray-300 pl-8 focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="70,000" />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Defaults to worker's agreed regular salary. Stored as integer kobo.</p>
                            <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                        </div>

                        <!-- Payment Frequency (Copied from employee, read-only) -->
                        <div>
                            <span class="block font-semibold text-sm text-gray-700">Payment Frequency</span>
                            <div class="mt-1 flex items-center space-x-2 text-sm text-gray-700 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Copied from employee: <strong x-text="frequency"></strong></span>
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div>
                            <x-input-label for="due_date" :value="__('Due Date')" class="font-semibold text-gray-700" />
                            <input id="due_date" name="due_date" type="date" value="{{ old('due_date', now()->addDays(7)->toDateString()) }}" required class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                            <p class="text-xs text-gray-500 mt-1">Expected date when this salary must be paid.</p>
                            <x-input-error :messages="$errors->get('due_date')" class="mt-1" />
                        </div>

                        <!-- Payment Period -->
                        <div>
                            <x-input-label for="payment_period" :value="__('Payment Period (Optional)')" class="font-semibold text-gray-700" />
                            <x-text-input id="payment_period" name="payment_period" type="text" class="mt-1 block w-full rounded-lg text-sm" :value="old('payment_period', now()->format('F Y'))" placeholder="e.g. October 2026, Week 42" />
                            <p class="text-xs text-gray-500 mt-1">Identifies which billing or salary period this obligation covers.</p>
                            <x-input-error :messages="$errors->get('payment_period')" class="mt-1" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <x-input-label for="notes" :value="__('Notes (Optional)')" class="font-semibold text-gray-700" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="Any additional notes regarding this obligation...">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                        </div>

                        <!-- Submit & Cancel -->
                        <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                            <a href="{{ route('salary-obligations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-5 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm">
                                Save Schedule
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
