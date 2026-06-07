<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'georeference.it') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    <x-navbar />

    @if(session('success'))
        <div class="max-w-7xl mx-auto w-full px-4 mt-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto w-full px-4 mt-4">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-700 mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between text-xs text-gray-400">
            <span>georeference.it — {{ __('Crowdsourced georeferencing of natural history specimens') }}</span>
            <div class="flex gap-4">
                <a href="{{ route('about') }}" class="hover:text-green-600">{{ __('About') }}</a>
                <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank" class="hover:text-green-600">GitHub</a>
                <a href="#" class="hover:text-green-600">{{ __('API') }}</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
