<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('marketer.nav.subtitle')) — Noon</title>
    <script>
    window.trans = @json(__('js'));
    window.t = window.t || function (path, vars) {
        const parts = String(path).split('.');
        let cur = window.trans || {};
        for (const part of parts) {
            cur = cur == null ? undefined : cur[part];
            if (cur === undefined) return path;
        }
        if (typeof cur === 'string' && vars) {
            return cur.replace(/\{(\w+)\}/g, (_, key) => (vars[key] ?? ''));
        }
        return cur;
    };
    </script>

    @vite(['resources/css/app.css', 'resources/js/marketer/app.js'])
    <style>
        /* Prevent Alpine.js elements from flashing */
        [x-cloak] { display: none !important; }

        /* ── Sidebar layout ─────────────────────────────────────────────────── */
        body {
            background: #f8fafc;
        }

        #marketer-sidebar {
            width: 240px;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            position: fixed;
            top: 0;
            inset-inline-start: 0;
            display: flex;
            flex-direction: column;
            z-index: 40;
        }

        #marketer-sidebar .brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        #marketer-sidebar .brand h1 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #facc15;
            letter-spacing: -0.5px;
        }

        #marketer-sidebar .brand p {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 2px;
        }

        #marketer-sidebar nav {
            flex: 1;
            padding: 1rem 0;
        }

        #marketer-sidebar nav a {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 1.5rem;
            font-size: 0.875rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.15s;
        }

        #marketer-sidebar nav a:hover,
        #marketer-sidebar nav a.active {
            color: #facc15;
            background: rgba(250, 204, 21, 0.08);
        }

        #marketer-sidebar nav a svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        #marketer-sidebar .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
        }

        #marketer-sidebar .referral-chip {
            background: rgba(250, 204, 21, 0.12);
            border: 1px solid rgba(250, 204, 21, 0.3);
            border-radius: 8px;
            padding: 0.4rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        #marketer-sidebar .referral-chip span {
            color: #facc15;
            font-family: monospace;
        }

        #marketer-sidebar .referral-chip .copy-icon {
            opacity: 0.5;
            font-size: 0.7rem;
        }

        /* ── Content area ───────────────────────────────────────────────────── */
        #marketer-content {
            margin-inline-start: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #marketer-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        #marketer-topbar .type-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            padding: 2px 8px;
        }

        #marketer-main {
            flex: 1;
            padding: 1.5rem;
        }

        /* ── Mobile sidebar toggle ──────────────────────────────────────────── */
        @media (max-width: 768px) {
            #marketer-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s;
            }

            html[dir="rtl"] #marketer-sidebar {
                transform: translateX(100%);
            }

            #marketer-sidebar.open {
                transform: translateX(0);
            }

            #marketer-content {
                margin-inline-start: 0;
            }
        }
    </style>
    @stack('styles')
</head>

<body>

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside id="marketer-sidebar">
        <div class="brand">
            <h1>noon <span style="color:#f1f5f9;font-weight:400">marketer</span></h1>
            <p>{{ __('marketer.nav.subtitle') }}</p>
        </div>

        <nav>
            @php $route = Route::currentRouteName(); @endphp

            <a href="{{ route('marketer.dashboard') }}"
                class="{{ Str::startsWith($route, 'marketer.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
                {{ __('marketer.nav.dashboard') }}
            </a>

            <a href="{{ route('marketer.analytics.index') }}"
                class="{{ Str::startsWith($route, 'marketer.analytics') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M8 17V10M13 17V6M18 17v-4" />
                </svg>
                {{ __('marketer.nav.analytics') }}
            </a>

            @php $isInfluencer = auth('marketer')->check() && auth('marketer')->user()->isInfluencer(); @endphp

            @if(!$isInfluencer)
                <a href="{{ route('marketer.campaigns.index') }}"
                    class="{{ Str::startsWith($route, 'marketer.campaigns') ? 'active' : '' }}"
                    style="position:relative;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.5 2.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 7.5-7.5z" />
                    </svg>
                    {{ __('marketer.nav.campaigns') }}
                    @if(($badges['pending_campaigns'] ?? 0) > 0)
                        <span style="
                            position:absolute; top:6px; right:10px;
                            background:#f59e0b; color:#fff;
                            font-size:0.6rem; font-weight:700;
                            min-width:1.1rem; height:1.1rem;
                            border-radius:999px;
                            display:inline-flex; align-items:center; justify-content:center;
                            padding:0 3px; line-height:1;
                        ">{{ $badges['pending_campaigns'] > 99 ? '99+' : $badges['pending_campaigns'] }}</span>
                    @endif
                </a>

                <a href="{{ route('marketer.promo-codes.index') }}"
                    class="{{ Str::startsWith($route, 'marketer.promo-codes') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.59 13.41L11 3.83A2 2 0 009.59 3.24H4a1 1 0 00-1 1v5.59a2 2 0 00.59 1.41l9.58 9.58a2 2 0 002.83 0l4.59-4.59a2 2 0 000-2.82z" />
                        <circle cx="7.5" cy="7.5" r="1.5" />
                    </svg>
                    {{ __('marketer.promo_codes.title') }}
                </a>
            @endif

            <a href="{{ route('marketer.qr-codes.index') }}"
                class="{{ Str::startsWith($route, 'marketer.qr-codes') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 14h3v3M14 20h3M20 14v3M20 20v.01" />
                </svg>
                {{ __('marketer.nav.qr_codes') }}
            </a>

            <a href="{{ route('marketer.samples.index') }}"
                class="{{ Str::startsWith($route, 'marketer.samples') ? 'active' : '' }}"
                style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                {{ __('marketer.nav.sample_requests') }}
                @if(($badges['pending_sample_requests'] ?? 0) > 0)
                    <span style="
                        position:absolute; top:6px; right:10px;
                        background:#f59e0b; color:#fff;
                        font-size:0.6rem; font-weight:700;
                        min-width:1.1rem; height:1.1rem;
                        border-radius:999px;
                        display:inline-flex; align-items:center; justify-content:center;
                        padding:0 3px; line-height:1;
                    ">{{ $badges['pending_sample_requests'] > 99 ? '99+' : $badges['pending_sample_requests'] }}</span>
                @endif
            </a>

            @if($isInfluencer)
                <a href="{{ route('marketer.deals.index') }}"
                    class="{{ Str::startsWith($route, 'marketer.deals') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zM17 8a4 4 0 100 8" />
                    </svg>
                    {{ __('marketer.nav.deals') }}
                </a>

                <a href="{{ route('marketer.media-kit.show') }}"
                    class="{{ Str::startsWith($route, 'marketer.media-kit') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4M16 2v4M3 10h18" />
                    </svg>
                    {{ __('marketer.nav.media_kit') }}
                </a>
            @endif

            <a href="{{ route('marketer.earnings.index') }}"
                class="{{ Str::startsWith($route, 'marketer.earnings') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('marketer.nav.earnings') }}
            </a>

            <a href="{{ route('marketer.wallet.index') }}"
                class="{{ Str::startsWith($route, 'marketer.wallet') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9A2.25 2.25 0 0018.75 6.75H5.25A2.25 2.25 0 003 9v3" />
                </svg>
                {{ __('marketer.nav.wallet') }}
            </a>

            @auth('marketer')
            @php
                $invitationPendingCount = $badges['pending_invitations'] ?? 0;
            @endphp
            <a href="{{ route('marketer.secret-promotions.index') }}"
                class="{{ Str::startsWith($route, 'marketer.secret-promotions') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ __('marketer.nav.secret_promotions') }}
            </a>

            <a href="{{ route('marketer.invitations.index') }}"
                class="{{ Str::startsWith($route, 'marketer.invitations') ? 'active' : '' }}"
                style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ __('marketer.nav.invitations') }}
                @if($invitationPendingCount > 0)
                    <span style="
                        position:absolute; top:6px; right:10px;
                        background:#f59e0b; color:#fff;
                        font-size:0.6rem; font-weight:700;
                        min-width:1.1rem; height:1.1rem;
                        border-radius:999px;
                        display:inline-flex; align-items:center; justify-content:center;
                        padding:0 3px; line-height:1;
                    ">{{ $invitationPendingCount > 99 ? '99+' : $invitationPendingCount }}</span>
                @endif
            </a>

            @php
                $adminOfferPendingCount = $badges['pending_admin_offers'] ?? 0;
            @endphp
            <a href="{{ route('marketer.admin-offers.index') }}"
                class="{{ Str::startsWith($route, 'marketer.admin-offers') ? 'active' : '' }}"
                style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
                {{ __('marketer.nav.admin_offers') }}
                @if($adminOfferPendingCount > 0)
                    <span style="
                        position:absolute; top:6px; right:10px;
                        background:#f59e0b; color:#fff;
                        font-size:0.6rem; font-weight:700;
                        min-width:1.1rem; height:1.1rem;
                        border-radius:999px;
                        display:inline-flex; align-items:center; justify-content:center;
                        padding:0 3px; line-height:1;
                    ">{{ $adminOfferPendingCount > 99 ? '99+' : $adminOfferPendingCount }}</span>
                @endif
            </a>
            @endauth

            <a href="{{ route('marketer.profile.edit') }}"
                class="{{ Str::startsWith($route, 'marketer.profile') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('marketer.nav.profile') }}
            </a>

            <a href="{{ route('marketer.store.edit') }}"
                class="{{ Str::startsWith($route, 'marketer.store') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 9.5L4.5 4h15L21 9.5M3 9.5V19a1 1 0 001 1h16a1 1 0 001-1V9.5M3 9.5h18M9 21v-6h6v6" />
                </svg>
                {{ __('marketer.nav.store') }}
            </a>
        </nav>

        <div class="sidebar-footer">
            @auth('marketer')
                <p style="color:#64748b; font-size:0.7rem; margin-bottom:4px;">{{ __('marketer.nav.referral_code') }}</p>
                <div class="referral-chip" onclick="copyReferral()" title="{{ __('marketer.nav.click_to_copy') }}">
                    <span>{{ auth()->guard('marketer')->user()->referral_code }}</span>
                    <span class="copy-icon">📋</span>
                </div>
                <form method="POST" action="{{ route('marketer.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        style="color:#ef4444;font-size:0.75rem;background:none;border:none;cursor:pointer;padding:0;">
                        {{ __('marketer.nav.sign_out') }}
                    </button>
                </form>
            @endauth
        </div>
    </aside>

    {{-- ── Content ──────────────────────────────────────────────────────────── --}}
    <div id="marketer-content">

        <header id="marketer-topbar">
            <div class="flex items-center gap-3">
                {{-- Mobile hamburger --}}
                <button class="md:hidden p-1" onclick="toggleMobileSidebar()" aria-label="{{ __('marketer.nav.toggle_menu') }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div>
                    <h2 class="text-sm font-semibold text-gray-800">@yield('page-title', __('marketer.nav.dashboard'))</h2>
                </div>
            </div>

            @auth('marketer')
                @php $m = auth()->guard('marketer')->user(); @endphp
                <div class="flex items-center gap-3">

                    {{-- Language switcher (AR / EN) --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-medium
                                   text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors">
                            {{-- Globe icon --}}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10
                                       5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <span>{{ strtoupper(app()->getLocale()) }}</span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute end-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                            {{-- English --}}
                            <form method="POST" action="{{ route('marketer.locale.switch') }}">
                                @csrf
                                <input type="hidden" name="locale" value="en">
                                <button type="submit"
                                    class="w-full text-start px-3 py-2 text-sm text-gray-700 hover:bg-gray-50
                                           flex items-center justify-between">
                                    <span>{{ __('marketer.nav.lang_en') }}</span>
                                    @if(app()->getLocale() === 'en')
                                        <svg class="w-3.5 h-3.5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            {{-- Arabic --}}
                            <form method="POST" action="{{ route('marketer.locale.switch') }}">
                                @csrf
                                <input type="hidden" name="locale" value="ar">
                                <button type="submit"
                                    class="w-full text-start px-3 py-2 text-sm text-gray-700 hover:bg-gray-50
                                           flex items-center justify-between">
                                    <span>{{ __('marketer.nav.lang_ar') }}</span>
                                    @if(app()->getLocale() === 'ar')
                                        <svg class="w-3.5 h-3.5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>

                    <x-notification-bell guard="marketer" />
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-800">{{ $m->name }}</p>
                        <span class="type-badge">{{ ucfirst(str_replace('_', ' ', $m->type?->value)) }}</span>
                    </div>
                    <div
                        class="w-9 h-9 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($m->name, 0, 1)) }}
                    </div>
                </div>
            @endauth
        </header>

        <main id="marketer-main">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Copy helper --}}
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); } catch(e) {}
            document.body.removeChild(ta);
            return Promise.resolve();
        }

        function copyReferral() {
            const code = '{{ auth()->guard("marketer")->user()?->referral_code }}';
            copyToClipboard(code).then(() => {
                const el = document.querySelector('.referral-chip');
                const orig = el.querySelector('span:last-child').textContent;
                el.querySelector('span:last-child').textContent = '✓';
                setTimeout(() => { el.querySelector('span:last-child').textContent = orig; }, 1500);
            });
        }
    </script>

    @stack('scripts')

    <script>
        function toggleMobileSidebar() {
            document.getElementById('marketer-sidebar').classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            var sidebar = document.getElementById('marketer-sidebar');
            var hamburger = e.target.closest('[aria-label="{{ __('marketer.nav.toggle_menu') }}"]');
            if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !hamburger) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>

</html>
