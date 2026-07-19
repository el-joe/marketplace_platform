<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة شركات السفر') | بوابة شركات السفر</title>
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/portal/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
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
                <x-notification-bell guard="travel_agency" />
                <a href="{{ route('travel-agency.dashboard') }}" class="text-gray-600 hover:text-blue-600 font-medium">{{ __('travel.nav.dashboard') }}</a>
                <a href="{{ route('travel-agency.packages.index') }}" class="text-gray-600 hover:text-blue-600 font-medium">{{ __('travel.nav.packages') }}</a>
                <a href="{{ route('travel-agency.bookings.index') }}" class="text-gray-600 hover:text-blue-600 font-medium inline-flex items-center gap-1.5">
                    {{ __('travel.nav.bookings') }}
                    @if(($pendingBookingsCount ?? 0) > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-amber-500 text-white text-xs font-bold">{{ $pendingBookingsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('travel-agency.inquiries.index') }}" class="text-gray-600 hover:text-blue-600 font-medium inline-flex items-center gap-1.5">
                    {{ __('travel.inquiries.interested_clients') }}
                    @if(($newInquiriesCount ?? 0) > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-rose-500 text-white text-xs font-bold">{{ $newInquiriesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('travel-agency.profile.edit') }}" class="text-gray-600 hover:text-blue-600 font-medium">{{ __('travel.nav.profile') }}</a>

                {{-- Language Switcher (AR / EN) --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold
                               text-gray-600 hover:text-blue-600 hover:bg-blue-50 border border-gray-200
                               transition-colors duration-150">
                        {{-- Globe icon --}}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10
                                   5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute end-0 mt-2 w-36 bg-white rounded-xl shadow-lg border border-gray-100 z-50 py-1 overflow-hidden">
                        {{-- English --}}
                        <form method="POST" action="{{ route('travel-agency.locale.switch') }}">
                            @csrf
                            <input type="hidden" name="locale" value="en">
                            <button type="submit"
                                class="w-full text-start px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600
                                       flex items-center justify-between transition-colors">
                                <span>{{ __('travel.nav.lang_en') }}</span>
                                @if(app()->getLocale() === 'en')
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                        </form>
                        {{-- Arabic --}}
                        <form method="POST" action="{{ route('travel-agency.locale.switch') }}">
                            @csrf
                            <input type="hidden" name="locale" value="ar">
                            <button type="submit"
                                class="w-full text-start px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600
                                       flex items-center justify-between transition-colors">
                                <span>{{ __('travel.nav.lang_ar') }}</span>
                                @if(app()->getLocale() === 'ar')
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('travel-agency.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-red-500 hover:text-red-700 font-medium">{{ __('travel.nav.logout') }}</button>
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
