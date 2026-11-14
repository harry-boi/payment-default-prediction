<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Smart Bill Reminder') }} - Payment Default Prediction</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col justify-between">
        <!-- Top Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3 text-indigo-700">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span class="font-bold text-lg text-gray-900 tracking-tight">Smart Bill Reminder</span>
                </div>

                <nav class="flex items-center space-x-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150">
                            {{ __('Go to Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition px-3 py-2">
                            {{ __('Log In') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150">
                            {{ __('Register') }}
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-grow flex items-center">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <div class="text-center max-w-3xl mx-auto">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mb-4 border border-indigo-200">
                        Smart Early-Warning & Default Prediction System
                    </span>
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight">
                        Salary Reminders & Payment Default Prediction
                    </h1>
                    <p class="mt-4 text-lg text-gray-600 leading-relaxed">
                        An early-warning monitoring tool designed for income payers and employers to track salary obligations, predict payment default risk using transparent rule-based indicators, and keep worker salaries on schedule.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                                Get Started (Create Account)
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Employee & Salary Scheduling</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Register workers, set regular salaries, schedule weekly, biweekly, or monthly obligations, and manage active employment status.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Payment Default Risk Scoring</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Evaluates historical late payments, employer overdue loads, outstanding ratios, and due proximity to score default risk from 0 to 85.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">In-App Reminders & Reports</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Automatic 7-day upcoming alerts and overdue reminders keep employers informed without external SMS or email dependencies.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-500 space-y-1">
                <p class="font-semibold text-gray-700">Smart Bill Reminder of Payment Default Prediction System for Income Payers</p>
                <p>Designed by Effiong Dominic Eyoh &bull; Department of Software and Web Development &bull; Akwa Ibom State Polytechnic, Ikot Osurua</p>
            </div>
        </footer>
    </body>
</html>
