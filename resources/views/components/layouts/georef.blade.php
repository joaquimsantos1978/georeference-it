<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('') }}">
    <title>Georeference Specimens — georeference.it</title>
    @php
        $metaDescription = 'Add coordinates to ungeoreferenced natural history specimens from GBIF. Collaborative georeferencing tool for biodiversity collections worldwide.';
        $metaUrl = url('/georef');
        $metaImage = url('/images/logo.png');
    @endphp
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $metaUrl }}">
    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:title" content="Georeference Specimens — georeference.it">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:site_name" content="georeference.it">
    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Georeference Specimens — georeference.it">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }
        html, body { margin: 0; padding: 0; overflow: hidden; max-width: 100vw; }
        #georef-content { height: calc(100vh - 48px); height: calc(100dvh - 48px); }
    </style>
</head>
<body class="bg-gray-900">
    <x-navbar />
    <div id="georef-content">
        {{ $slot }}
    </div>
    @stack('scripts')
</body>
</html>
