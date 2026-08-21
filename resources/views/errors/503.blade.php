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
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #d1fae5;
            border-top-color: #16a34a;
            border-radius: 50%;
            margin: 1.5rem auto 0;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="card">
        <img src="/images/logo.png" alt="georeference.it" class="logo">
        <h1>We'll be right back</h1>
        <p>georeference.it is undergoing brief scheduled maintenance.</p>
        <p>Estamos a fazer manutenção programada — voltamos em breve.</p>
        <div class="spinner"></div>
    </div>
</body>
</html>
