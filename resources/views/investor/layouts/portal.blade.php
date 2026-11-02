<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#176b4d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="EBIMS Investor">
    <title>@yield('title', 'Investor Portal') | EBIMS</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('investor-manifest.json') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <style>
        :root {
            --portal-ink: #172033;
            --portal-muted: #667085;
            --portal-border: #dce4ea;
            --portal-bg: #f4f7f8;
            --portal-panel: #ffffff;
            --portal-green: #176b4d;
            --portal-green-dark: #10533b;
            --portal-blue: #265d8a;
            --portal-amber: #a45b11;
            --portal-red: #a43a3a;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--portal-bg);
            color: var(--portal-ink);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            letter-spacing: 0;
        }
        a { color: var(--portal-blue); }
        .portal-shell { min-height: 100vh; }
        .portal-topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid var(--portal-border);
            display: flex;
            justify-content: space-between;
            min-height: 68px;
            padding: 0 28px;
        }
        .portal-brand { align-items: center; color: var(--portal-ink); display: flex; gap: 12px; text-decoration: none; }
        .portal-brand img { border-radius: 4px; height: 42px; object-fit: cover; width: 42px; }
        .portal-brand strong { display: block; font-size: 16px; }
        .portal-brand span { color: var(--portal-muted); display: block; font-size: 12px; margin-top: 2px; }
        .portal-nav { align-items: center; display: flex; gap: 8px; }
        .portal-nav a, .portal-nav button {
            background: transparent;
            border: 0;
            color: var(--portal-muted);
            cursor: pointer;
            font: inherit;
            padding: 10px 12px;
            text-decoration: none;
        }
        .portal-nav a:hover, .portal-nav button:hover { color: var(--portal-green); }
        .portal-container { margin: 0 auto; max-width: 1220px; padding: 28px; }
        .portal-alert { border: 1px solid; border-radius: 6px; margin-bottom: 18px; padding: 12px 14px; }
        .portal-alert-success { background: #eaf7f0; border-color: #acd6be; color: #155c3f; }
        .portal-alert-warning { background: #fff7e8; border-color: #ebce95; color: #7c470e; }
        .portal-alert-error { background: #fff0f0; border-color: #e2b2b2; color: #8a2929; }
        .portal-install-banner {
            align-items: center;
            background: #eaf7f0;
            border: 1px solid #acd6be;
            border-radius: 10px;
            color: #155c3f;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 12px 16px;
        }
        .portal-install-banner[hidden] { display: none; }
        .portal-install-banner strong,
        .portal-install-banner span { display: block; }
        .portal-install-banner span { color: #477661; font-size: 12px; margin-top: 2px; }
        .portal-install-button {
            background: var(--portal-green);
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 12px;
            white-space: nowrap;
        }
        .portal-install-button:hover { background: var(--portal-green-dark); }
        .portal-footer { color: var(--portal-muted); font-size: 12px; padding: 12px 28px 28px; text-align: center; }

        @media (max-width: 720px) {
            .portal-topbar { align-items: flex-start; gap: 12px; padding: 14px 18px; }
            .portal-brand span { display: none; }
            .portal-nav { flex-wrap: wrap; justify-content: flex-end; }
            .portal-container { padding: 20px 16px; }
            .portal-install-banner { align-items: flex-start; flex-direction: column; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="portal-shell">
    @unless(request()->routeIs('investor.login', 'investor.register', 'investor.registration-submitted'))
    <header class="portal-topbar">
        <a class="portal-brand" href="{{ auth('investor')->check() ? route('investor.dashboard') : route('investor.login') }}">
            <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS">
            <span><strong>EBIMS Investor Portal</strong><span>Emuria Micro Finance Limited</span></span>
        </a>
        <nav class="portal-nav" aria-label="Investor navigation">
            @auth('investor')
                <a href="{{ route('investor.dashboard') }}"><i class="mdi mdi-view-dashboard-outline"></i> Overview</a>
                <form method="POST" action="{{ route('investor.logout') }}">
                    @csrf
                    <button type="submit"><i class="mdi mdi-logout"></i> Sign out</button>
                </form>
            @else
                <a href="{{ route('investor.login') }}">Investor login</a>
                <a href="{{ route('investor.register') }}">Register</a>
                <a href="{{ route('login') }}">Staff login</a>
            @endauth
        </nav>
    </header>
    @endunless

    <main class="portal-container">
        @include('investor.partials.flash-messages')
        @if(request()->routeIs('investor.login', 'investor.register'))
            <div class="portal-install-banner" id="investor-install-banner" hidden>
                <div>
                    <strong>Install the EBIMS Investor Portal</strong>
                    <span>Open it like an app from your phone or computer.</span>
                </div>
                <button type="button" class="portal-install-button" id="investor-install-button">
                    <i class="mdi mdi-download-outline" aria-hidden="true"></i> Install app
                </button>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="portal-footer">THANK YOU FOR CHOOSING EBIMS</footer>
</div>
<script>
    (() => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('investor-service-worker.js') }}', { scope: '/investor/' })
                .catch(() => {
                    // Installation is optional; a registration failure must not block login.
                });
        });
    })();

    (() => {
        const banner = document.getElementById('investor-install-banner');
        const button = document.getElementById('investor-install-button');

        if (!banner || !button) {
            return;
        }

        let installPrompt = null;

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            installPrompt = event;
            banner.hidden = false;
        });

        button.addEventListener('click', async () => {
            if (!installPrompt) {
                return;
            }

            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            banner.hidden = true;
        });

        window.addEventListener('appinstalled', () => {
            banner.hidden = true;
            installPrompt = null;
        });
    })();
</script>
@stack('scripts')
</body>
</html>
