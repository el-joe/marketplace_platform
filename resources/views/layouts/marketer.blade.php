<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Marketer Portal') — Noon</title>
    @vite(['resources/css/app.css', 'resources/js/marketer/app.js'])
    <style>
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
            left: 0;
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
            margin-left: 240px;
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

            #marketer-sidebar.open {
                transform: translateX(0);
            }

            #marketer-content {
                margin-left: 0;
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
            <p>Partner Portal</p>
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
                Dashboard
            </a>

            <a href="{{ route('marketer.campaigns.index') }}"
                class="{{ Str::startsWith($route, 'marketer.campaigns') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17.5 2.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 7.5-7.5z" />
                </svg>
                My Campaigns
            </a>

            <a href="{{ route('marketer.earnings.index') }}"
                class="{{ Str::startsWith($route, 'marketer.earnings') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Earnings
            </a>

            @auth('marketer')
            @php
                $invitationPendingCount = \App\Models\VendorCampaignInvitation::where('marketer_id', auth()->guard('marketer')->id())
                    ->where('status', 'pending')
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count();
            @endphp
            <a href="{{ route('marketer.invitations.index') }}"
                class="{{ Str::startsWith($route, 'marketer.invitations') ? 'active' : '' }}"
                style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Invitations
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
            @endauth

            <a href="{{ route('marketer.profile.edit') }}"
                class="{{ Str::startsWith($route, 'marketer.profile') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Profile
            </a>
        </nav>

        <div class="sidebar-footer">
            @auth('marketer')
                <p style="color:#64748b; font-size:0.7rem; margin-bottom:4px;">Referral Code</p>
                <div class="referral-chip" onclick="copyReferral()" title="Click to copy">
                    <span>{{ auth()->guard('marketer')->user()->referral_code }}</span>
                    <span class="copy-icon">📋</span>
                </div>
                <form method="POST" action="{{ route('marketer.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        style="color:#ef4444;font-size:0.75rem;background:none;border:none;cursor:pointer;padding:0;">
                        Sign out
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
                <button class="md:hidden p-1" onclick="toggleMobileSidebar()" aria-label="Toggle menu">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div>
                    <h2 class="text-sm font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                </div>
            </div>

            @auth('marketer')
                @php $m = auth()->guard('marketer')->user(); @endphp
                <div class="flex items-center gap-3">
                    <x-notification-bell guard="marketer" />
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-800">{{ $m->name }}</p>
                        <span class="type-badge">{{ ucfirst(str_replace('_', ' ', $m->type)) }}</span>
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
            var hamburger = e.target.closest('[aria-label="Toggle menu"]');
            if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !hamburger) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>

</html>
