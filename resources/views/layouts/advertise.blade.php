<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale', 'ar') === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', session('locale', 'ar') === 'ar' ? 'حلول الإعلانات للبائعين | نون' : 'Ad Solutions for Sellers | noon')</title>
    <meta name="description" content="@yield('description', session('locale', 'ar') === 'ar' ? 'مرحبًا بكم في إعلانات نون' : 'Welcome to the noon ads')">
    <meta name="robots" content="index,follow">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    {{-- Cairo for Arabic --}}
    <link href="https://fonts.bunny.net/css?family=cairo:200,300,400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/portal/app.js'])
</head>

<body class="bg-white text-gray-900 antialiased" style="font-family: 'Figtree', 'Cairo', sans-serif;">

    @include('portal.partials.advertise-nav')

    <main>
        @yield('content')
    </main>

    @include('portal.partials.advertise-footer')

    @stack('scripts')
</body>

</html>
