<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Employees') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage workers, salary terms, and employment statuses.</p>
            </div>
            <div>
                <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Employee') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                @if ($employees->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Employee</th>
                                    <th scope="col" class="px-6 py-3.5">Contact</th>
                                    <th scope="col" class="px-6 py-3.5">Salary Amount</th>
                                    <th scope="col" class="px-6 py-3.5">Frequency</th>
                                    <th scope="col" class="px-6 py-3.5">Status</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($employees as $employee)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-semibold text-gray-900">{{ $employee->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $employee->job_title ?: 'Worker' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {{ $employee->phone ?: '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                            {{ $employee->formatted_salary }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {{ $employee->salary_frequency->label() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <x-status-badge :status="$employee->status->value" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2 text-xs font-medium">
                                            <a href="{{ route('employees.show', $employee) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                View
                                            </a>
                                            <span class="text-gray-300">|</span>
                                            <a href="{{ route('employees.edit', $employee) }}" class="text-gray-600 hover:text-gray-900 hover:underline">
                                                Edit
                                            </a>
                                            <span class="text-gray-300">|</span>
                                            <form method="POST" action="{{ route('employees.status', $employee) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to {{ $employee->isActive() ? 'deactivate' : 'reactivate' }} {{ $employee->name }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="{{ $employee->isActive() ? 'text-amber-600 hover:text-amber-900' : 'text-green-600 hover:text-green-900' }} hover:underline">
                                                    {{ $employee->isActive() ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($employees->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $employees->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 px-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">No employees registered yet</h3>
                        <p class="mt-1 text-sm text-gray-500 max-w-sm mx-auto">
                            Add your employees and their regular salary details to begin tracking salary obligations and risk predictions.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                                + Add First Employee
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
