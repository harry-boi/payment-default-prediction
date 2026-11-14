<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Record Salary Payment') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Settle salary obligation for {{ $salaryObligation->employee->name }}.</p>
            </div>
            <div>
                <a href="{{ route('salary-obligations.show', $salaryObligation) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition flex items-center">
                    &larr; Back to Obligation
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $dueDateStr = $salaryObligation->due_date->format('Y-m-d');
        $initialPaidAt = old('paid_at', $defaultPaymentDate);
    @endphp

    <div class="py-8" x-data="{
        dueDate: '{{ $dueDateStr }}',
        paidAt: '{{ $initialPaidAt }}',
        reference: '{{ old('reference', '') }}',
        showConfirmModal: false,
        get isOnTime() {
            if (!this.paidAt) return true;
            return this.paidAt <= this.dueDate;
        },
        get delayDays() {
            if (!this.paidAt || this.isOnTime) return 0;
            const due = new Date(this.dueDate);
            const paid = new Date(this.paidAt);
            const diffTime = paid.getTime() - due.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays > 0 ? diffDays : 0;
        },
        openConfirmation() {
            const form = this.$refs.paymentForm;
            if (form.reportValidity()) {
                this.showConfirmModal = true;
            }
        },
        submitForm() {
            this.showConfirmModal = false;
            this.$refs.paymentForm.submit();
        }
    }">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Obligation Details Summary Card -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-indigo-100">
                    <div>
                        <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block">Target Obligation</span>
                        <h3 class="text-lg font-bold text-gray-900 mt-0.5">{{ $salaryObligation->employee->name }}</h3>
                        <p class="text-xs text-gray-600">{{ $salaryObligation->employee->job_title ?: 'Worker' }} &bull; {{ $salaryObligation->frequency->label() }}</p>
                    </div>
                    <div class="sm:text-right">
                        <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block">Amount Payable</span>
                        <div class="text-xl font-black text-gray-900">{{ $salaryObligation->formatted_amount }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 text-xs">
                    <div>
                        <span class="text-gray-500 block font-medium">Scheduled Due Date</span>
                        <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $salaryObligation->due_date->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block font-medium">Payment Period</span>
                        <span class="font-semibold text-gray-800 text-sm mt-0.5 block">{{ $salaryObligation->payment_period ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block font-medium">Current Status</span>
                        <div class="mt-0.5">
                            <x-status-badge :status="$salaryObligation->status->value" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Record Payment Form -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 sm:p-8">
                <form x-ref="paymentForm" method="POST" action="{{ route('salary-obligations.payments.store', $salaryObligation) }}" class="space-y-5" @submit.prevent="openConfirmation">
                    @csrf

                    <!-- Amount Paid -->
                    <div>
                        <x-input-label for="amount_paid" :value="__('Amount Paid (in ₦ Naira)')" class="font-semibold text-gray-700" />
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold sm:text-sm">₦</span>
                            </div>
                            <x-text-input
                                id="amount_paid"
                                name="amount_paid"
                                type="text"
                                class="pl-8 block w-full bg-gray-50 text-gray-700 font-semibold cursor-not-allowed"
                                :value="old('amount_paid', $salaryObligation->amount_in_naira)"
                                readonly
                                required
                            />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Payment must equal the exact obligation amount (no partial payments allowed).</p>
                        <x-input-error :messages="$errors->get('amount_paid')" class="mt-1" />
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <x-input-label for="paid_at" :value="__('Actual Payment Date')" class="font-semibold text-gray-700" />
                        <x-text-input
                            id="paid_at"
                            name="paid_at"
                            type="date"
                            class="mt-1 block w-full"
                            x-model="paidAt"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-1">Date when funds were disbursed to the employee.</p>
                        <x-input-error :messages="$errors->get('paid_at')" class="mt-1" />
                    </div>

                    <!-- Calculated Status Preview (Figure 3.7 / Read-only) -->
                    <div class="p-4 rounded-lg border" :class="isOnTime ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold block uppercase tracking-wider" :class="isOnTime ? 'text-green-800' : 'text-red-800'">
                                    Calculated Payment Status (Read-Only)
                                </span>
                                <div class="text-sm font-bold mt-1" :class="isOnTime ? 'text-green-900' : 'text-red-900'">
                                    <template x-if="isOnTime">
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Paid On Time (0 days delay)
                                        </span>
                                    </template>
                                    <template x-if="!isOnTime">
                                        <span class="inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Paid Late (<span x-text="delayDays"></span> days delay)
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full" :class="isOnTime ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                <span x-text="isOnTime ? 'On-Time' : 'Late'"></span>
                            </span>
                        </div>
                        <p class="text-xs mt-2" :class="isOnTime ? 'text-green-700' : 'text-red-700'">
                            Calculated automatically against scheduled due date of <strong>{{ $salaryObligation->due_date->format('M d, Y') }}</strong>.
                        </p>
                    </div>

                    <!-- Payment Reference / Note -->
                    <div>
                        <x-input-label for="reference" :value="__('Payment Reference / Note (Optional)')" class="font-semibold text-gray-700" />
                        <x-text-input
                            id="reference"
                            name="reference"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g. TRF-9823412, Zenith Bank Transfer, Cash Receipt #4"
                            x-model="reference"
                        />
                        <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('salary-obligations.show', $salaryObligation) }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition">
                            Cancel
                        </a>
                        <button
                            type="button"
                            @click="openConfirmation"
                            class="inline-flex items-center px-5 py-2.5 bg-green-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition"
                        >
                            Record Payment &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div
            x-show="showConfirmModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            style="display: none;"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    x-show="showConfirmModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showConfirmModal = false"
                ></div>

                <!-- Modal Panel -->
                <div
                    x-show="showConfirmModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-bold text-gray-900">
                                    Confirm Payment Disbursal
                                </h3>
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>Please review and confirm the salary payment details below:</p>
                                    
                                    <div class="mt-3 bg-gray-50 rounded-lg p-3 border border-gray-200 space-y-2 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Employee:</span>
                                            <span class="font-bold text-gray-900">{{ $salaryObligation->employee->name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Amount Paid:</span>
                                            <span class="font-bold text-gray-900">{{ $salaryObligation->formatted_amount }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Payment Date:</span>
                                            <span class="font-semibold text-gray-900" x-text="paidAt"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Status Classification:</span>
                                            <span class="font-bold" :class="isOnTime ? 'text-green-700' : 'text-red-700'" x-text="isOnTime ? 'Paid On Time (0 delay)' : 'Paid Late (' + delayDays + ' days delay)'"></span>
                                        </div>
                                    </div>

                                    @if ($salaryObligation->employee->isActive())
                                        <p class="mt-3 text-xs text-indigo-700 bg-indigo-50 p-2.5 rounded-lg border border-indigo-100">
                                            <strong>Recurrence Notice:</strong> Recording this payment will automatically schedule the next <strong>{{ $salaryObligation->frequency->label() }}</strong> obligation.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            type="button"
                            @click="submitForm"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto sm:text-sm font-bold"
                        >
                            Confirm & Record Payment
                        </button>
                        <button
                            type="button"
                            @click="showConfirmModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
