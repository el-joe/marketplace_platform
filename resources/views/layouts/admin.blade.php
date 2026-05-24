@php
    /** @var \App\Services\NavigationService $nav */
    $nav = app(\App\Services\NavigationService::class);
    $navGroups = $nav->adminNavigation();
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0284c7">

    <title>{{ $title ?? config('app.name', 'Marketplace Admin') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/css/components.css', 'resources/js/app.js', 'resources/js/components/layout.js'])

    @stack('styles')
</head>

<body class="bg-gray-50 font-figtree text-gray-900 antialiased">

    <div id="app-layout">
        @include('layouts.partials.sidebar', ['navGroups' => $navGroups])

        <div id="sidebar-backdrop"></div>

        <div id="main-wrapper" class="flex flex-col">
            @include('layouts.partials.topbar', ['breadcrumbs' => $breadcrumbs ?? []])

            <main id="content" class="flex-1">
                @if(session('success'))
                    <x-flash-message type="success" :message="session('success')" />
                @endif
                @if(session('error'))
                    <x-flash-message type="error" :message="session('error')" />
                @endif
                @if(session('warning'))
                    <x-flash-message type="warning" :message="session('warning')" />
                @endif
                @if(session('info'))
                    <x-flash-message type="info" :message="session('info')" />
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>

</html>