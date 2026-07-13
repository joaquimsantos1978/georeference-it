<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Reference (Swagger UI) | georeference.it</title>
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; }
        .topbar {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px; background: #16a34a; font-family: ui-sans-serif, system-ui, sans-serif;
        }
        .topbar a { color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; }
        .topbar a:hover { text-decoration: underline; }
        .topbar span { color: #dcfce7; font-size: 13px; }
        .swagger-ui .topbar { display: none; } {{-- hide Swagger UI's own default topbar --}}
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('api-docs') }}">&larr; georeference.it API docs</a>
        <span>Interactive reference generated from openapi.yaml</span>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            SwaggerUIBundle({
                url: "{{ url('/openapi.yaml') }}",
                dom_id: '#swagger-ui',
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout',
            });
        };
    </script>
</body>
</html>
