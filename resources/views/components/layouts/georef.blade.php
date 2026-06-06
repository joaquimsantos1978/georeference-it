<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('') }}">
    <title>{{ __('Georeference') }} — georeference.it</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        html, body, #map { height: 100%; margin: 0; padding: 0; }
        body { overflow: hidden; }
        [data-tooltip] { position: relative; }
[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 0; top: 100%;
    background: rgba(0,0,0,0.8);
    color: white;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 100;
    pointer-events: none;
    margin-top: 2px;
}
    </style>
</head>
<body class="bg-gray-900">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
