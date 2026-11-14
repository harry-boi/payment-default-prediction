<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Smart Bill Reminder') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-slate-50 min-h-screen flex flex-col justify-center items-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Header Branding -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-100 mb-3">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight sm:text-3xl">
                    Smart Bill Reminder
                </h1>
                <p class="mt-1 text-sm font-medium text-gray-500">
                    Payment Default Prediction System for Income Payers
                </p>
            </div>

            <!-- Card Container -->
            <div class="bg-white px-8 py-8 shadow-xl shadow-gray-200/50 border border-gray-100 rounded-2xl sm:px-10">
                {{ $slot }}
            </div>

            <!-- Academic Attribution Footer -->
            <p class="mt-6 text-center text-xs text-gray-400">
                Effiong Dominic Eyoh &bull; Department of Software and Web Development &bull; Akwa Ibom State Polytechnic
            </p>
        </div>
    </body>
</html>
