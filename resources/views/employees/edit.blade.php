<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Edit Employee') }}: {{ $employee->name }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Update worker contact details, salary, and employment status.</p>
            </div>
            <div>
                <a href="{{ route('employees.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition flex items-center">
                    &larr; Back to Employees
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 sm:p-8">
                <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- Employee Name -->
                    <div>
                        <x-input-label for="name" :value="__('Employee Name')" class="font-semibold text-gray-700" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-lg" :value="old('name', $employee->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <x-input-label for="phone" :value="__('Phone Number (Optional)')" class="font-semibold text-gray-700" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full rounded-lg" :value="old('phone', $employee->phone)" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Job Role / Title -->
                    <div>
                        <x-input-label for="job_title" :value="__('Job Role')" class="font-semibold text-gray-700" />
                        <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full rounded-lg" :value="old('job_title', $employee->job_title)" />
                        <x-input-error :messages="$errors->get('job_title')" class="mt-1" />
                    </div>

                    <!-- Salary Amount in Naira -->
                    <div>
                        <x-input-label for="salary_amount" :value="__('Salary Amount (in ₦ Naira)')" class="font-semibold text-gray-700" />
                        <div class="relative mt-1 rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm font-bold">₦</span>
                            </div>
                            <input id="salary_amount" name="salary_amount" type="text" value="{{ old('salary_amount', $employee->salary_in_naira) }}" required class="block w-full rounded-lg border-gray-300 pl-8 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Current amount: {{ $employee->formatted_salary }}</p>
                        <x-input-error :messages="$errors->get('salary_amount')" class="mt-1" />
                    </div>

                    <!-- Salary Frequency -->
                    <div>
                        <x-input-label for="salary_frequency" :value="__('Salary Frequency')" class="font-semibold text-gray-700" />
                        <select id="salary_frequency" name="salary_frequency" required class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            @foreach (\App\Enums\SalaryFrequency::cases() as $frequency)
                                <option value="{{ $frequency->value }}" {{ old('salary_frequency', $employee->salary_frequency->value) === $frequency->value ? 'selected' : '' }}>
                                    {{ $frequency->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('salary_frequency')" class="mt-1" />
                    </div>

                    <!-- Employment Status -->
                    <div>
                        <x-input-label for="status" :value="__('Employment Status')" class="font-semibold text-gray-700" />
                        <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            @foreach (\App\Enums\EmployeeStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('status', $employee->status->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Inactive employees cannot receive new scheduled obligations.</p>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    <!-- Submit & Cancel -->
                    <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                        <a href="{{ route('employees.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-5 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm">
                            Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
