<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        {{ $employee->name }}
                    </h2>
                    <x-status-badge :status="$employee->status->value" />
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ $employee->job_title ?: 'Worker' }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('employees.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    &larr; Back to List
                </a>
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                    Edit Employee
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Employee Details Grid -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-base text-gray-900">Employment & Salary Overview</h3>
                    <form method="POST" action="{{ route('employees.status', $employee) }}" onsubmit="return confirm('Change active status for {{ $employee->name }}?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-xs font-semibold {{ $employee->isActive() ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }} hover:underline">
                            {{ $employee->isActive() ? 'Deactivate Worker' : 'Reactivate Worker' }}
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Regular Salary</span>
                        <span class="text-xl font-extrabold text-gray-900 mt-1 block">{{ $employee->formatted_salary }}</span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Payment Frequency</span>
                        <span class="text-sm font-medium text-gray-800 mt-1 block">{{ $employee->salary_frequency->label() }}</span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Phone Contact</span>
                        <span class="text-sm font-medium text-gray-800 mt-1 block">{{ $employee->phone ?: 'Not provided' }}</span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Registered Date</span>
                        <span class="text-sm font-medium text-gray-800 mt-1 block">{{ $employee->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
