<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale', 'ar') === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بيع على نون') | نون للبائعين</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    {{-- Cairo for Arabic --}}
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/portal/app.js'])
</head>

<body class="bg-gray-950 text-white antialiased" style="font-family: 'Cairo', 'Figtree', sans-serif;">

    @include('portal.partials.nav')

    <main>
        @yield('content')
    </main>

    {{-- Scroll-to-top --}}
    <button id="scroll-top" class="fixed bottom-6 end-6 z-50 p-3 bg-yellow-400 text-gray-950 rounded-full shadow-lg
               opacity-0 pointer-events-none transition-opacity duration-300 hover:bg-yellow-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m18 15-6-6-6 6" />
        </svg>
    </button>

    @stack('scripts')
</body>

</html>