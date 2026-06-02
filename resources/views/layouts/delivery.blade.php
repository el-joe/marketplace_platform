<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111827">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Delivery') | Noon</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/delivery/app.js'])
    @stack('head')

    <style>
        :root {
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --nav-height: calc(64px + var(--safe-bottom));
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100dvh;
            overscroll-behavior: none;
        }

        /* Center on desktop, full-width on mobile */
        #app-shell {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100dvh;
            background: #0f172a;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        #app-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #111827;
            border-bottom: 1px solid #1e293b;
        }

        #page-content {
            flex: 1;
            overflow-y: auto;
            padding-bottom: var(--nav-height);
            -webkit-overflow-scrolling: touch;
        }

        #bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: #111827;
            border-top: 1px solid #1e293b;
            height: var(--nav-height);
            padding-bottom: var(--safe-bottom);
            z-index: 40;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 4px;
            gap: 2px;
            font-size: 10px;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            transition: color 0.15s;
            -webkit-tap-highlight-color: transparent;
            height: 64px;
        }

        .nav-item.active,
        .nav-item:active {
            color: #facc15;
        }

        .nav-item svg {
            width: 22px;
            height: 22px;
            stroke-width: 1.8;
        }

        /* Card / surface */
        .d-card {
            background: #1e293b;
            border-radius: 16px;
            padding: 16px;
        }

        /* Action button */
        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 56px;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.01em;
            transition: opacity 0.15s, transform 0.1s;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
            border: none;
        }

        .btn-action:active {
            opacity: 0.85;
            transform: scale(0.98);
        }

        .btn-yellow {
            background: #facc15;
            color: #0f172a;
        }

        .btn-green {
            background: #22c55e;
            color: #fff;
        }

        .btn-red {
            background: #ef4444;
            color: #fff;
        }

        .btn-blue {
            background: #3b82f6;
            color: #fff;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #475569;
            color: #cbd5e1;
        }

        /* Status chips */
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .chip-assigned {
            background: #1e3a5f;
            color: #60a5fa;
        }

        .chip-accepted {
            background: #1e3a2a;
            color: #4ade80;
        }

        .chip-picked_up {
            background: #3b2e00;
            color: #facc15;
        }

        .chip-delivered {
            background: #14532d;
            color: #86efac;
        }

        .chip-failed {
            background: #450a0a;
            color: #fca5a5;
        }

        /* Toggle switch */
        .toggle-track {
            width: 56px;
            height: 30px;
            border-radius: 15px;
            background: #334155;
            position: relative;
            cursor: pointer;
            transition: background 0.25s;
        }

        .toggle-track.on {
            background: #22c55e;
        }

        .toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff;
            transition: transform 0.25s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .3);
        }

        .toggle-track.on .toggle-thumb {
            transform: translateX(26px);
        }
    </style>
</head>

<body>

    <div id="app-shell" x-data="deliveryApp()" @availability-changed.window="isAvailable = $event.detail.is_available">

        {{-- ── Header ─────────────────────────────────────────────────────────── --}}
        <header id="app-header">
            <div class="flex items-center justify-between px-4 h-14">
                <div class="flex items-center gap-2">
                    @yield(
                        'header-left',
                        '<span class="text-yellow-400 font-bold text-xl tracking-tight">noon</span><span class="text-slate-400 text-sm ml-1 font-medium">delivery</span>'
                    )
            </di    v>
            <div class="flex items-center gap-3">
                    @auth('delivery')
                        @yield('header-right', '')
                    @endauth
                </div>
        </div>
        </header>

        {{--     ── Page Content ───────────────────────────────────────────────────── --}}
    <main id="page-content" class="px-4 pt-4">
        @if(session('success'))
            <div class="mb-3 p-3 rounded-xl bg-green-900/60 text-green-300 text-sm font-medium">
                    {{ session('success') }}
                </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="mb-3 p-3 rounded-xl bg-red-900/60 text-red-300 text-sm font-medium">
                    {{ session('error') ?? $errors->first() }}
            </div>
        @endif

            @yield('content')
        </main>

            {{-- ── Bottom Navigation ──────────────────────────────────────────────── --}}
    @auth('delivery')
        <nav id=    "bottom-nav">
            <div class="flex h-16">
                @php
                    $current = request()->route()->getName() ?? '';
                    function navActive(string $prefix, string $current): string
                    {
                        return str_starts_with($current, $prefix) ? 'active' : '';
                    }
                @endphp

                     <a h    ref="{{ route('delivery.dashboard') }}"
                   class    ="nav-item {{ navActive('delivery.dashboard', $current) }}">

                                                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    Home
                    </a>

                    <a href="{{ route('delivery.assignments.index') }}"
                       class="nav-item {{ navActive('delivery.assignments', $current) }}">


                                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                        </svg>
                    Orders
                    </a>

                         <a href="{{ route('delivery.dashboard') }}#map"
                   class    ="nav-item {{ navActive('delivery.map', $current) }}
                "               >
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m0 8V9m0 0L9 7"/>
                        </svg>
                    Map
                    </a>

                   <a h    ref="{{ route('delivery.earnings.index') }}"
                   class    ="nav-item {{ navActive('delivery.earnings', $current) }}">
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    Earnings
                    </a>

                <a h    ref="{{ route('delivery.profile.index') }}"
                       class="nav-item {{ navActive('delivery.profile', $current) }}">

                                                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Profile
                    </a>
                </div>
        </nav>
    @endauth

    </div>

        <script>
function         deliveryApp() {
            return {
                isAvailable: @json(auth('delivery')->user()?->is_available ?? false),
        };
}
    </script>


@stack('scripts')
</body>
</html>
