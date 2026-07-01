@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' | georeference.it' : 'georeference.it | Georeference GBIF Specimens' }}</title>
    @php
        $metaDescription = $description ?? 'georeference.it is a crowdsourced platform to add coordinates to ungeoreferenced natural history specimens from GBIF. Help map biodiversity collections worldwide.';
        $metaTitle = isset($title) ? $title . ' | georeference.it' : 'georeference.it | Georeference GBIF Specimens';
        $metaUrl = url()->current();
        $metaImage = url('/images/logo.png');
    @endphp
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $metaUrl }}">
    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:site_name" content="georeference.it">
    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 640px) {
            .footer-inner { flex-direction: column !important; }
            .footer-inner > div { flex: none !important; width: 100% !important; }
            .footer-inner .footer-grid { grid-template-columns: repeat(2,1fr) !important; }
        }
    </style>
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
        <div class="max-w-7xl mx-auto px-4 py-6 text-xs text-gray-400">
            <div class="footer-inner" style="display:flex;gap:2rem;align-items:flex-start;">
                {{-- Brand --}}
                <div style="flex:0 0 50%;">
                    <div class="font-semibold text-gray-600 dark:text-gray-300 mb-1">georeference.it</div>
                    <p class="text-gray-400 leading-relaxed">{{ __('Crowdsourced georeferencing of natural history specimens') }}</p>
                </div>
                {{-- Links — 3/4 --}}
                <div class="footer-grid" style="flex:1;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
                    <div>
                        <div class="font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">{{ __('Platform') }}</div>
                        <div class="flex flex-col gap-1.5">
                            <a href="{{ route('georef.index') }}" class="hover:text-green-600">{{ __('Georeference') }}</a>
                            <a href="{{ route('explore') }}" class="hover:text-green-600">{{ __('Explore') }}</a>
                            <a href="{{ route('datasets') }}" class="hover:text-green-600">{{ __('Datasets') }}</a>
                            <a href="{{ route('stats') }}" class="hover:text-green-600">{{ __('Stats') }}</a>
                            <a href="{{ route('impact') }}" class="hover:text-green-600">{{ __('Impact') }}</a>
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">{{ __('Community') }}</div>
                        <div class="flex flex-col gap-1.5">
                            <a href="{{ route('leaderboard') }}" class="hover:text-green-600">{{ __('Leaderboard') }}</a>
                            <a href="{{ route('activity') }}" class="hover:text-green-600">{{ __('Activity') }}</a>
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">{{ __('Docs') }}</div>
                        <div class="flex flex-col gap-1.5">
                            <a href="{{ route('about') }}" class="hover:text-green-600">{{ __('About') }}</a>
                            <a href="{{ route('how-it-works') }}" class="hover:text-green-600">{{ __('How it works') }}</a>
                            <a href="{{ route('georeferencing-guide') }}" class="hover:text-green-600">{{ __('Georeferencing guide') }}</a>
                            <a href="{{ route('extension') }}" class="hover:text-green-600">{{ __('Browser extensions') }}</a>
                            <a href="{{ route('api-docs') }}" class="hover:text-green-600 font-mono">API</a>
                            <a href="{{ route('cite') }}" class="hover:text-green-600">{{ __('Cite') }}</a>
                            <a href="https://github.com/joaquimsantos1978/georeference-it" target="_blank" class="hover:text-green-600">GitHub</a>
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">{{ __('Legal') }}</div>
                        <div class="flex flex-col gap-1.5">
                            <a href="{{ route('privacy') }}" class="hover:text-green-600">{{ __('Privacy') }}</a>
                            <a href="{{ route('terms') }}" class="hover:text-green-600">{{ __('Terms') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
