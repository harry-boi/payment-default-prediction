@php
    $unreadRemindersCount = 0;
    if (Illuminate\Support\Facades\Schema::hasTable('reminders') && Auth::check()) {
        try {
            $unreadRemindersCount = Auth::user()->reminders()->where('is_read', false)->count();
        } catch (\Throwable $e) {
            $unreadRemindersCount = 0;
        }
    }
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo & Brand Name -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-indigo-700 hover:text-indigo-900 transition">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span class="font-bold text-lg text-gray-900 tracking-tight hidden md:inline">Smart Bill Reminder</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 lg:space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="Route::has('employees.index') ? route('employees.index') : url('/employees')" :active="request()->routeIs('employees.*') || request()->is('employees*')">
                        {{ __('Employees') }}
                    </x-nav-link>

                    <x-nav-link :href="Route::has('salary-obligations.index') ? route('salary-obligations.index') : url('/salary-obligations')" :active="request()->routeIs('salary-obligations.*') || request()->is('salary-obligations*')">
                        {{ __('Salary Obligations') }}
                    </x-nav-link>

                    <x-nav-link :href="Route::has('payments.index') ? route('payments.index') : url('/payments')" :active="request()->routeIs('payments.*') || request()->is('payments*')">
                        {{ __('Payments') }}
                    </x-nav-link>

                    <x-nav-link :href="Route::has('reminders.index') ? route('reminders.index') : url('/reminders')" :active="request()->routeIs('reminders.*') || request()->is('reminders*')">
                        <span>{{ __('Reminders') }}</span>
                        @if ($unreadRemindersCount > 0)
                            <span class="ms-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ $unreadRemindersCount }}
                            </span>
                        @endif
                    </x-nav-link>

                    <x-nav-link :href="Route::has('risk-assessments.index') ? route('risk-assessments.index') : url('/risk-assessments')" :active="request()->routeIs('risk-assessments.*') || request()->is('risk-assessments*')">
                        {{ __('Risk Assessment') }}
                    </x-nav-link>

                    <x-nav-link :href="Route::has('reports.index') ? route('reports.index') : url('/reports')" :active="request()->routeIs('reports.*') || request()->is('reports*')">
                        {{ __('Reports') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-600 bg-white hover:text-gray-900 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Button (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('employees.index') ? route('employees.index') : url('/employees')" :active="request()->routeIs('employees.*') || request()->is('employees*')">
                {{ __('Employees') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('salary-obligations.index') ? route('salary-obligations.index') : url('/salary-obligations')" :active="request()->routeIs('salary-obligations.*') || request()->is('salary-obligations*')">
                {{ __('Salary Obligations') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('payments.index') ? route('payments.index') : url('/payments')" :active="request()->routeIs('payments.*') || request()->is('payments*')">
                {{ __('Payments') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('reminders.index') ? route('reminders.index') : url('/reminders')" :active="request()->routeIs('reminders.*') || request()->is('reminders*')">
                <span class="flex items-center justify-between">
                    <span>{{ __('Reminders') }}</span>
                    @if ($unreadRemindersCount > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                            {{ $unreadRemindersCount }}
                        </span>
                    @endif
                </span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('risk-assessments.index') ? route('risk-assessments.index') : url('/risk-assessments')" :active="request()->routeIs('risk-assessments.*') || request()->is('risk-assessments*')">
                {{ __('Risk Assessment') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="Route::has('reports.index') ? route('reports.index') : url('/reports')" :active="request()->routeIs('reports.*') || request()->is('reports*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
