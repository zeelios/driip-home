<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Zeelios') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Instrument Sans', sans-serif;
                background-color: #000;
                color: #fff;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .container {
                text-align: center;
                padding: 2rem;
            }

            .logo {
                font-size: 4rem;
                font-weight: 600;
                letter-spacing: 0.3em;
                text-transform: uppercase;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #fff 0%, #888 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .tagline {
                font-size: 1rem;
                color: #666;
                letter-spacing: 0.2em;
                text-transform: uppercase;
                margin-bottom: 3rem;
            }

            .divider {
                width: 100px;
                height: 1px;
                background: linear-gradient(90deg, transparent, #fff, transparent);
                margin: 2rem auto;
            }

            .signature {
                font-size: 0.875rem;
                color: #444;
                letter-spacing: 0.1em;
                margin-top: 4rem;
            }

            .signature span {
                color: #fff;
                font-weight: 500;
            }

            .nav-links {
                display: flex;
                gap: 2rem;
                justify-content: center;
                margin-top: 2rem;
            }

            .nav-links a {
                color: #888;
                text-decoration: none;
                font-size: 0.875rem;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                padding: 0.5rem 1.5rem;
                border: 1px solid #333;
                transition: all 0.3s ease;
            }

            .nav-links a:hover {
                color: #fff;
                border-color: #fff;
            }
        </style>
    @endif
</head>

<body>
    <div class="container">
        <h1 class="logo">Zeelios</h1>
        <p class="tagline">Coming Soon</p>

        <div class="divider"></div>

        <nav class="nav-links">
            <a href="https://www.zeelios.com" target="_blank">Visit Zeelios</a>
            <a href="{{ config('app.url') }}/api" target="_blank">API</a>
        </nav>

        <p class="signature">Crafted by <span>zeelios</span></p>
    </div>
</body>

</html>