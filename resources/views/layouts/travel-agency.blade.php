<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة شركات السفر') | بوابة شركات السفر</title>
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/portal/app.js'])
</head>
<body class="bg-gray-100 min-h-screen" style="font-family: 'Cairo', sans-serif;">

    {{-- Top Nav --}}
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="bg-blue-500 text-white font-black text-lg px-2.5 py-1 rounded">✈</span>
                <span class="font-bold text-gray-800">{{ auth()->guard('travel_agency')->user()->name }}</span>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('travel-agency.dashboard') }}" class="text-gray-600 hover:text-blue-600 font-medium">الرئيسية</a>
                <a href="{{ route('travel-agency.packages.index') }}" class="text-gray-600 hover:text-blue-600 font-medium">الباقات</a>
                <a href="{{ route('travel-agency.bookings.index') }}" class="text-gray-600 hover:text-blue-600 font-medium">الحجوزات</a>
                <a href="{{ route('travel-agency.profile.edit') }}" class="text-gray-600 hover:text-blue-600 font-medium">الملف الشخصي</a>
                <form method="POST" action="{{ route('travel-agency.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-500 hover:text-red-700 font-medium">خروج</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border-b border-emerald-200 px-6 py-3 text-sm text-emerald-700 text-center">
        {{ session('success') }}
    </div>
    @endif

    {{-- Content --}}
    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
