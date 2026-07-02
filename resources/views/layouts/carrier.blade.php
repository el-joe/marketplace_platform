<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة شركات الشحن') | {{ auth('shipping_supervisor')->user()?->company?->name ?? 'Carrier Portal' }}</title>
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/portal/app.js'])
</head>
<body class="bg-gray-100 min-h-screen" style="font-family: 'Cairo', sans-serif;">

    {{-- Top Nav --}}
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="bg-indigo-600 text-white font-black text-lg px-2.5 py-1 rounded">🚚</span>
                <div class="leading-tight">
                    <div class="font-bold text-gray-900 text-sm">{{ auth('shipping_supervisor')->user()?->company?->name }}</div>
                    <div class="text-xs text-gray-500">{{ auth('shipping_supervisor')->user()?->name }}</div>
                </div>
            </div>

            <div class="flex items-center gap-6 text-sm font-medium">
                <x-notification-bell guard="shipping_supervisor" />
                <a href="{{ route('carrier.dashboard') }}"
                   class="text-gray-600 hover:text-indigo-600 transition {{ request()->routeIs('carrier.dashboard') ? 'text-indigo-600 font-bold' : '' }}">
                    الرئيسية
                </a>
                <a href="{{ route('carrier.agents.index') }}"
                   class="text-gray-600 hover:text-indigo-600 transition {{ request()->routeIs('carrier.agents.*') ? 'text-indigo-600 font-bold' : '' }}">
                    المناديب
                </a>
                @if(auth('shipping_supervisor')->user()?->hasPermission('view_orders'))
                <a href="{{ route('carrier.assignments.unassigned') }}"
                   class="text-gray-600 hover:text-indigo-600 transition {{ request()->routeIs('carrier.assignments.unassigned') ? 'text-indigo-600 font-bold' : '' }}">
                    غير معينة
                </a>
                <a href="{{ route('carrier.assignments.index') }}"
                   class="text-gray-600 hover:text-indigo-600 transition {{ request()->routeIs('carrier.assignments.index') || request()->routeIs('carrier.assignments.show') ? 'text-indigo-600 font-bold' : '' }}">
                    الطلبات
                </a>
                @endif
                @if(auth('shipping_supervisor')->user()?->hasPermission('manage_agents'))
                <a href="{{ route('carrier.supervisors.index') }}"
                   class="text-gray-600 hover:text-indigo-600 transition {{ request()->routeIs('carrier.supervisors.*') ? 'text-indigo-600 font-bold' : '' }}">
                    المشرفون
                </a>
                @endif
                <form method="POST" action="{{ route('carrier.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-500 hover:text-red-700 transition font-medium">خروج</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 border-b border-emerald-200 px-6 py-3 text-sm text-emerald-700 text-center font-medium">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error') || $errors->any())
    <div class="bg-red-50 border-b border-red-200 px-6 py-3 text-sm text-red-700 text-center font-medium">
        {{ session('error') ?? $errors->first() }}
    </div>
    @endif

    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
