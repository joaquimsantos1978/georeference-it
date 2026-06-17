<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'georeference.it') }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
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
                <a href="{{ route('about') }}" class="hover:text-green-600">About</a>
                <a href="{{ route('how-it-works') }}" class="hover:text-green-600">How it works</a>
                <a href="{{ route('extension') }}" class="hover:text-green-600">Extension</a>
                <a href="{{ route('api-docs') }}" class="hover:text-green-600">API</a>
                <a href="{{ route('privacy') }}" class="hover:text-green-600">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-green-600">Terms</a>
                <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank" class="hover:text-green-600">GitHub</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
