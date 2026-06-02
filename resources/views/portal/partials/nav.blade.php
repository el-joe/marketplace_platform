@php
    $isAr = session('locale', 'ar') === 'ar';
    $registerUrl = route('portal.register');
    $loginUrl = route('partner.login');
    $langToggleUrl = route('portal.language', $isAr ? 'en' : 'ar');
@endphp

<nav class="sticky top-0 z-50 bg-[#111111] border-b border-gray-800 shadow-xl" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('portal.home') }}" class="flex items-center gap-2 shrink-0">
                <span
                    class="bg-yellow-400 text-gray-950 font-black text-xl px-2 py-0.5 rounded tracking-tight">noon</span>
                <span class="text-white text-sm font-semibold hidden sm:inline">
                    {{ $isAr ? 'للبائعين' : 'Sellers' }}
                </span>
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-300">
                <a href="{{ route('portal.home') }}"
                    class="hover:text-yellow-400 transition-colors {{ request()->routeIs('portal.home') ? 'text-yellow-400' : '' }}">
                    {{ $isAr ? 'الصفحة الرئيسية' : 'Home' }}
                </a>
                <a href="{{ route('portal.how-it-works') }}"
                    class="hover:text-yellow-400 transition-colors {{ request()->routeIs('portal.how-it-works') ? 'text-yellow-400' : '' }}">
                    {{ $isAr ? 'البدء' : 'Get Started' }}
                </a>
                <a href="{{ route('portal.fulfillment') }}"
                    class="hover:text-yellow-400 transition-colors {{ request()->routeIs('portal.fulfillment') ? 'text-yellow-400' : '' }}">
                    {{ $isAr ? 'الشحن والتوصيل' : 'Fulfillment' }}
                </a>
                <a href="{{ route('portal.smart-tools') }}"
                    class="hover:text-yellow-400 transition-colors {{ request()->routeIs('portal.smart-tools') ? 'text-yellow-400' : '' }}">
                    {{ $isAr ? 'نمّي بذكاء' : 'Smart Tools' }}
                </a>
                <a href="{{ route('portal.faq') }}"
                    class="hover:text-yellow-400 transition-colors {{ request()->routeIs('portal.faq') ? 'text-yellow-400' : '' }}">
                    {{ $isAr ? 'الأسئلة الشائعة' : 'FAQ' }}
                </a>
            </div>

            {{-- Right actions --}}
            <div class="hidden md:flex items-center gap-3">
                {{-- Language toggle --}}
                <a href="{{ $langToggleUrl }}" class="text-sm text-gray-400 hover:text-white border border-gray-700 hover:border-gray-500
                          px-3 py-1.5 rounded-full transition-colors">
                    {{ $isAr ? 'English' : 'العربية' }}
                </a>

                {{-- Country flag --}}
                {{--<div class="flex items-center gap-1 text-sm text-gray-400 border border-gray-700 px-3 py-1.5 rounded-full"
                    x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open" class="flex items-center gap-1 hover:text-white transition-colors">
                        <span>🇦🇪</span>
                        <span>{{ $isAr ? 'الإمارات' : 'UAE' }}</span>
                        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="absolute {{ $isAr ? 'left-4' : 'right-4' }} top-14 mt-1 w-36 bg-gray-900 border border-gray-700
                                rounded-xl shadow-2xl overflow-hidden">
                        <a href="#" class="flex items-center gap-2 px-4 py-2.5 hover:bg-gray-800 text-sm text-white">
                            <span>🇦🇪</span> {{ $isAr ? 'الإمارات' : 'UAE' }}
                        </a>
                        <a href="#" class="flex items-center gap-2 px-4 py-2.5 hover:bg-gray-800 text-sm text-gray-300">
                            <span>🇸🇦</span> {{ $isAr ? 'السعودية' : 'KSA' }}
                        </a>
                        <a href="#" class="flex items-center gap-2 px-4 py-2.5 hover:bg-gray-800 text-sm text-gray-300">
                            <span>🇪🇬</span> {{ $isAr ? 'مصر' : 'Egypt' }}
                        </a>
                    </div>
                </div>--}}

                {{-- Login --}}
                <a href="{{ $loginUrl }}" class="text-sm text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400
                          px-4 py-1.5 rounded-full transition-colors">
                    {{ $isAr ? 'تسجيل الدخول' : 'Login' }}
                </a>

                {{-- Register CTA --}}
                <a href="{{ $registerUrl }}" class="text-sm font-bold bg-yellow-400 hover:bg-yellow-300 text-gray-950
                          px-5 py-2 rounded-full transition-colors shadow-lg shadow-yellow-400/20">
                    {{ $isAr ? 'سجّل الآن' : 'Register Now' }}
                </a>
            </div>

            {{-- Mobile hamburger --}}
            <button @click="mobileOpen = !mobileOpen"
                class="md:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path x-show="!mobileOpen" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="mobileOpen" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        class="md:hidden bg-[#111111] border-t border-gray-800 px-4 py-4 space-y-3">
        <a href="{{ route('portal.home') }}" class="block py-2 text-gray-300 hover:text-yellow-400">
            {{ $isAr ? 'الصفحة الرئيسية' : 'Home' }}
        </a>
        <a href="{{ route('portal.how-it-works') }}" class="block py-2 text-gray-300 hover:text-yellow-400">
            {{ $isAr ? 'البدء' : 'Get Started' }}
        </a>
        <a href="{{ route('portal.fulfillment') }}" class="block py-2 text-gray-300 hover:text-yellow-400">
            {{ $isAr ? 'الشحن والتوصيل' : 'Fulfillment' }}
        </a>
        <a href="{{ route('portal.smart-tools') }}" class="block py-2 text-gray-300 hover:text-yellow-400">
            {{ $isAr ? 'نمّي بذكاء' : 'Smart Tools' }}
        </a>
        <a href="{{ route('portal.faq') }}" class="block py-2 text-gray-300 hover:text-yellow-400">
            {{ $isAr ? 'الأسئلة الشائعة' : 'FAQ' }}
        </a>
        <div class="pt-3 flex flex-col gap-2 border-t border-gray-800">
            <a href="{{ $langToggleUrl }}"
                class="text-center py-2 border border-gray-700 rounded-full text-sm text-gray-400">
                {{ $isAr ? 'English' : 'العربية' }}
            </a>
            <a href="{{ $loginUrl }}"
                class="text-center py-2 border border-gray-600 rounded-full text-sm text-gray-300">
                {{ $isAr ? 'تسجيل الدخول' : 'Login' }}
            </a>
            <a href="{{ $registerUrl }}"
                class="text-center py-2 bg-yellow-400 rounded-full text-sm font-bold text-gray-950">
                {{ $isAr ? 'سجّل الآن' : 'Register Now' }}
            </a>
        </div>
    </div>
</nav>
