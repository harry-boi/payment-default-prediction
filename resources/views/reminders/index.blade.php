<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Reminders & Alerts') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Automated forward-looking payment notices and overdue default warnings.</p>
            </div>
            @if ($unreadCount > 0)
                <div>
                    <form method="POST" action="{{ route('reminders.read-all') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 shadow-sm transition"
                        >
                            <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Mark All As Read
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total Unread -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Unread Notifications</span>
                        @if ($unreadCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">Action Required</span>
                        @endif
                    </div>
                    <div class="text-3xl font-black {{ $unreadCount > 0 ? 'text-indigo-700' : 'text-gray-900' }} mt-2">
                        {{ $unreadCount }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Active notifications awaiting acknowledgment</span>
                </div>

                <!-- Overdue Alerts -->
                <div class="bg-white p-5 rounded-xl border {{ $unreadOverdueCount > 0 ? 'border-red-300 bg-red-50/30 ring-2 ring-red-100' : 'border-gray-200' }} shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-red-600 uppercase tracking-wider block">Overdue Alerts</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Critical</span>
                    </div>
                    <div class="text-3xl font-black text-red-700 mt-2">
                        {{ $unreadOverdueCount }}
                    </div>
                    <span class="text-xs text-red-600 mt-1 block font-medium">Unpaid obligations past scheduled due date</span>
                </div>

                <!-- Upcoming Reminders -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Upcoming Reminders</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">{{ config('reminders.window_days', 7) }}-Day Window</span>
                    </div>
                    <div class="text-3xl font-black text-amber-600 mt-2">
                        {{ $unreadUpcomingCount }}
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">Salaries due within reminder window</span>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('reminders.index') }}" class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Status Filter -->
                        <div>
                            <select name="status" class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses (Read & Unread)</option>
                                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread Only</option>
                                <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read Only</option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <select name="type" class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Types (Upcoming & Overdue)</option>
                                <option value="upcoming" {{ request('type') === 'upcoming' ? 'selected' : '' }}>Upcoming Reminders</option>
                                <option value="overdue" {{ request('type') === 'overdue' ? 'selected' : '' }}>Overdue Alerts</option>
                            </select>
                        </div>

                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-xs font-semibold uppercase tracking-wider hover:bg-gray-800 transition">
                            Filter
                        </button>

                        @if (request()->hasAny(['status', 'type']))
                            <a href="{{ route('reminders.index') }}" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Reminders List -->
            <div class="space-y-3">
                @forelse ($reminders as $reminder)
                    @php
                        $isOverdue = $reminder->isOverdue();
                        $cardBg = ! $reminder->is_read
                            ? ($isOverdue ? 'bg-red-50/40 border-red-200' : 'bg-indigo-50/30 border-indigo-200')
                            : 'bg-white border-gray-200 opacity-80';
                    @endphp
                    <div class="rounded-xl border p-5 {{ $cardBg }} shadow-sm transition hover:shadow-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <!-- Icon -->
                            <div class="mt-0.5 flex-shrink-0">
                                @if ($isOverdue)
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Details -->
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $isOverdue ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $isOverdue ? 'Overdue Alert' : 'Upcoming Due Date' }}
                                    </span>
                                    <span class="text-xs text-gray-500">&bull; {{ $reminder->created_at->diffForHumans() }}</span>
                                    @if ($reminder->is_read)
                                        <span class="text-xs text-gray-400 italic">(Read)</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white">NEW</span>
                                    @endif
                                </div>

                                <p class="text-base font-semibold text-gray-900 mt-1.5">
                                    {{ $reminder->message }}
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-600">
                                    <span>Employee: <strong>{{ $reminder->salaryObligation->employee->name }}</strong></span>
                                    <span>&bull;</span>
                                    <span>Due: <strong>{{ $reminder->salaryObligation->due_date->format('M d, Y') }}</strong></span>
                                    <span>&bull;</span>
                                    <span>Amount: <strong>{{ $reminder->salaryObligation->formatted_amount }}</strong></span>
                                    @if ($reminder->salaryObligation->latestRiskAssessment)
                                        <span>&bull;</span>
                                        <x-risk-badge :level="$reminder->salaryObligation->latestRiskAssessment->risk_level->value" :score="$reminder->salaryObligation->latestRiskAssessment->score" />
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2 sm:self-center self-end flex-shrink-0">
                            @if (! $reminder->salaryObligation->isPaid())
                                <a
                                    href="{{ route('salary-obligations.payments.create', $reminder->salaryObligation) }}"
                                    class="inline-flex items-center px-3.5 py-1.5 bg-green-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-green-700 transition shadow-sm"
                                >
                                    Pay Now &rarr;
                                </a>
                            @endif

                            @if (! $reminder->is_read)
                                <form method="POST" action="{{ route('reminders.read', $reminder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-sm"
                                        title="Mark as Read"
                                    >
                                        Mark as Read
                                    </button>
                                </form>
                            @endif

                            <a
                                href="{{ route('salary-obligations.show', $reminder->salaryObligation) }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-900 transition"
                            >
                                View Obligation
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-500 shadow-sm">
                        <div class="max-w-sm mx-auto">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <h4 class="mt-2 text-sm font-semibold text-gray-900">No reminders found</h4>
                            <p class="mt-1 text-xs text-gray-500">
                                @if (request()->hasAny(['status', 'type']))
                                    No reminders matched your filter criteria. Try clearing filters.
                                @else
                                    You have no active bill reminders or overdue warnings at this time.
                                @endif
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('salary-obligations.index') }}" class="inline-flex items-center px-3.5 py-1.5 border border-indigo-600 text-xs font-semibold text-indigo-600 rounded-lg hover:bg-indigo-50 transition">
                                    View Salary Obligations &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($reminders->hasPages())
                <div class="bg-white p-4 rounded-xl border border-gray-200">
                    {{ $reminders->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
