<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="60">
    <title>georeference.it — Maintenance</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            color: #111827;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            padding: 1.5rem;
        }
        .card {
            max-width: 26rem;
            width: 100%;
            text-align: center;
        }
        img.logo {
            height: 48px;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }
        p {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0 0 0.25rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="/images/logo.png" alt="georeference.it" class="logo">
        <h1 data-en="Under maintenance" data-pt="Em manutenção">Under maintenance</h1>
        <p data-en="georeference.it is currently undergoing maintenance. This may take a while — thanks for your patience."
           data-pt="O georeference.it está de momento em manutenção. Pode demorar algum tempo — obrigado pela paciência.">
            georeference.it is currently undergoing maintenance. This may take a while — thanks for your patience.
        </p>
    </div>
    <script>
        // Pre-rendered once when maintenance mode starts (see the artisan down --render
        // call), served as static HTML for every visitor regardless of app/DB state — so
        // language can't be resolved server-side per request the way __() normally would.
        // Browser language is the only signal available at that point.
        (function() {
            var pt = (navigator.language || '').toLowerCase().indexOf('pt') === 0;
            if (!pt) return;
            document.querySelectorAll('[data-pt]').forEach(function(el) {
                el.textContent = el.getAttribute('data-pt');
            });
        })();
    </script>
</body>
</html>
