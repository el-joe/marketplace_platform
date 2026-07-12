@php
    $isAr = session('locale', 'ar') === 'ar';
    $langToggleUrl = route('portal.language', $isAr ? 'en' : 'ar');

    $navLinks = [
        ['block' => 'link_home', 'label_ar' => 'الصفحة الرئيسية', 'label_en' => 'Home', 'route' => 'portal.home'],
        [
            'block' => 'link_how_it_works', 'label_ar' => 'البدء', 'label_en' => 'Getting Started', 'route' => 'portal.how-it-works',
            'submenu' => [
                ['label_ar' => 'إعداد حسابك', 'label_en' => 'Setting up your account', 'route' => '#'],
                ['label_ar' => 'إدراج منتجاتك', 'label_en' => 'Listing your products', 'route' => '#'],
                ['label_ar' => 'اختيار نموذج الشحن الخاص بك', 'label_en' => 'Choosing your fulfilment model', 'route' => '#'],
            ]
        ],
        [
            'block' => 'link_fulfillment', 'label_ar' => 'الشحن والتوصيل', 'label_en' => 'Shipping & Fulfilment', 'route' => 'portal.fulfillment',
            'submenu' => [
                ['label_ar' => 'مشحون من نون (FBN)', 'label_en' => 'Fulfilled by noon (FBN)', 'route' => '#'],
                ['label_ar' => 'مشحون من الشريك (FBP)', 'label_en' => 'Fulfilled by Partner (FBP)', 'route' => '#'],
            ]
        ],
        [
            'block' => 'link_smart_tools', 'label_ar' => 'نمّي بذكاء', 'label_en' => 'Grow Smarter', 'route' => 'portal.smart-tools',
            'submenu' => [
                ['label_ar' => 'الإعلان على نون', 'label_en' => 'Advertising on noon', 'route' => '#'],
                ['label_ar' => 'هيكل رسوم نون', 'label_en' => 'noon\'s Fee Structure', 'route' => '#'],
                ['label_ar' => 'النمو باستخدام التحليلات', 'label_en' => 'Scale with Insights', 'route' => '#'],
            ]
        ],
    ];
    foreach ($navLinks as $i => $l) {
        $navLinks[$i]['label'] = portal_content('nav', $l['block'], 'label', $l['label_en'], $l['label_ar']);
    }
@endphp

<header class="fixed inset-x-0 top-0 z-50 transition-all duration-300" 
        x-data="{ mobileOpen: false, scrolled: false }"
        @scroll.window="scrolled = (window.pageYOffset > 20)"
        :class="scrolled ? 'bg-black/60 backdrop-blur-md shadow-lg' : 'bg-transparent'">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-[72px] gap-6">

            {{-- Logo --}}
            <a href="{{ route('portal.home') }}" class="flex items-center shrink-0" aria-label="{{ $isAr ? 'الصفحة الرئيسية' : 'Home' }}">
                <svg width="94" height="29" viewBox="0 0 94 29" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-[76px] h-auto">
                    <g>
                        <path d="M88.7607 2.98682L86.4781 6.93198C88.3646 8.53879 89.6615 11.0172 89.6615 13.807C89.6615 18.878 85.4648 23.048 80.2807 23.048C75.0965 23.048 70.9293 18.878 70.9293 13.6651C70.9293 13.046 70.9864 12.425 71.0988 11.8335L66.8174 14.3137C66.8174 21.6402 73.0424 27.3304 80.2825 27.3304C87.5227 27.3304 94.0019 21.2164 94.0019 13.7499C94.0019 9.38274 91.8888 5.55182 88.7625 2.98682H88.7607Z" fill="#F3E008" />
                        <path d="M82.1966 4.67671L84.5068 0.676261C83.2117 0.197166 82.0566 0 80.9015 0C80.0282 0 79.2674 0.112403 78.7036 0.224806L75.7744 5.29584C76.8448 4.67671 78.2246 4.33765 79.7187 4.33765C80.592 4.33765 81.3804 4.45006 82.1984 4.67671H82.1966Z" fill="#F3E008" />
                        <path d="M56.6571 13.4977C56.6571 15.5836 54.9604 17.2807 52.8749 17.2807H34.6586V13.6856C34.6586 11.5813 33.7337 9.59857 32.1199 8.24421C30.5098 6.89168 28.3819 6.32782 26.2818 6.6982C23.3783 7.21047 21.0829 9.50459 20.5707 12.4105C20.2004 14.5093 20.7642 16.6376 22.1182 18.2499C23.4723 19.8641 25.4546 20.7891 27.5585 20.7891H31.0993C30.9316 22.0348 30.3384 23.1809 29.4081 24.0451C28.4004 24.9812 27.0905 25.4972 25.718 25.4972H25.4509V29.0038H25.718C28.019 29.0038 30.204 28.1267 31.8712 26.5364C33.4703 25.0107 34.4393 22.9782 34.6162 20.791H52.8731C56.8911 20.791 60.1593 17.5221 60.1593 13.5032V9.28716H56.6534V13.5032L56.6571 13.4977ZM31.1546 13.6856V17.2807H27.5603C26.4955 17.2807 25.4896 16.8126 24.8043 15.9945C24.1097 15.1671 23.8315 14.1094 24.025 13.0167C24.2774 11.5813 25.4564 10.402 26.8916 10.1495C27.9822 9.95605 29.0396 10.2325 29.8687 10.929C30.6866 11.6145 31.1546 12.6206 31.1546 13.6856Z" fill="#F3E008" />
                        <path d="M14.3734 15.9408C14.3734 18.937 11.9361 21.3767 8.9387 21.3767C5.94132 21.3767 3.504 18.9388 3.504 15.9408V9.2998H0V15.9408C0 20.87 4.01062 24.8815 8.9387 24.8815C13.8668 24.8815 17.8774 20.87 17.8774 15.9408V9.2998H14.3734V15.9408Z" fill="#F3E008" />
                        <path d="M8.93864 6.52656C9.99242 6.52656 10.8509 5.66788 10.8509 4.61387C10.8509 3.55986 9.99242 2.70117 8.93864 2.70117C7.88487 2.70117 7.02637 3.55986 7.02637 4.61387C7.02637 5.66788 7.88487 6.52656 8.93864 6.52656Z" fill="#F3E008" />
                        <path d="M53.1237 6.52656C54.1775 6.52656 55.036 5.66788 55.036 4.61387C55.036 3.55986 54.1775 2.70117 53.1237 2.70117C52.0699 2.70117 51.2114 3.55986 51.2114 4.61387C51.2114 5.66788 52.0699 6.52656 53.1237 6.52656Z" fill="#F3E008" />
                    </g>
                </svg>
            </a>

            {{-- Desktop nav links --}}
            <nav class="hidden lg:flex items-center gap-[32px] h-full">
                @foreach($navLinks as $link)
                    <div class="relative group h-full flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                        <a href="{{ route($link['route']) }}"
                           class="relative flex items-center h-full text-[16px] font-bold transition-all duration-300
                                  {{ request()->routeIs($link['route']) ? 'text-white' : 'text-gray-200 hover:text-white' }}">
                            {{ $link['label'] }}
                            @if(request()->routeIs($link['route']))
                                <span class="absolute bottom-0 inset-x-0 h-[3px] bg-yellow-400 rounded-t-full"></span>
                            @endif
                            <span class="absolute bottom-0 inset-x-0 h-[3px] bg-yellow-400 rounded-t-full scale-x-0 group-hover:scale-x-100 transition-transform duration-300 {{ $isAr ? 'origin-right' : 'origin-left' }}"
                                  x-show="!{{ request()->routeIs($link['route']) ? 'true' : 'false' }}"></span>
                        </a>

                        @if(isset($link['submenu']))
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-4"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-4"
                                 class="absolute {{ $isAr ? 'right-0' : 'left-0' }} top-full w-[280px] z-50" x-cloak>
                                <div class="bg-[#1c1c1c] border border-white/5 shadow-2xl rounded-xl py-2 overflow-hidden mt-1">
                                    @foreach($link['submenu'] as $sub)
                                        <a href="{{ $sub['route'] }}" 
                                           class="relative block px-5 py-3.5 text-[15px] font-bold text-gray-300 hover:text-white transition-all duration-200 flex items-center group/sub hover:bg-white/5">
                                            <div class="w-[3px] h-0 bg-yellow-400 absolute {{ $isAr ? 'right-0' : 'left-0' }} top-1/2 -translate-y-1/2 transition-all duration-300 group-hover/sub:h-[70%] {{ $isAr ? 'rounded-l-full' : 'rounded-r-full' }}"></div>
                                            <span class="transition-transform duration-300 group-hover/sub:{{ $isAr ? '-translate-x-2' : 'translate-x-2' }}">{{ $isAr ? $sub['label_ar'] : $sub['label_en'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="w-px h-[24px] bg-white/20 self-center"></div>

                {{-- Country --}}
                <div class="relative group h-full flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button type="button" class="flex items-center gap-[8px] text-[15px] font-bold text-gray-200 hover:text-white transition-colors h-full">
                        @if($currentCountry)
                            <img src="https://f.nooncdn.com/s/app/com/common/images/flags/{{ strtolower($currentCountry->iso_code_2) }}.svg"
                                 alt="{{ $isAr ? $currentCountry->name_ar : $currentCountry->name_en }}" width="20" height="20" class="rounded-sm">
                            <span>{{ $isAr ? $currentCountry->name_ar : $currentCountry->name_en }}</span>
                        @else
                            <span>{{ $isAr ? 'الدولة' : 'Country' }}</span>
                        @endif
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-4"
                         class="absolute {{ $isAr ? 'start-0' : 'end-0' }} top-full w-48 pt-6 -mt-6 z-50" x-cloak>
                        <div class="bg-[#1c1c1c] border border-white/5 shadow-2xl rounded-xl py-2 overflow-hidden mt-1">
                            @foreach($countries as $country)
                                <a href="{{ route('portal.country', $country->site_code) }}"
                                   class="relative block px-5 py-3 text-[14px] font-bold transition-all duration-200 flex items-center gap-3 group/sub hover:bg-white/5 
                                          {{ $currentCountry && $currentCountry->id === $country->id ? 'text-yellow-400' : 'text-gray-300 hover:text-white' }}">
                                    <div class="w-[3px] h-0 bg-yellow-400 absolute {{ $isAr ? 'right-0' : 'left-0' }} top-1/2 -translate-y-1/2 transition-all duration-300 group-hover/sub:h-[70%] {{ $isAr ? 'rounded-l-full' : 'rounded-r-full' }}"></div>
                                    <span class="flex items-center gap-3 transition-transform duration-300 group-hover/sub:{{ $isAr ? '-translate-x-2' : 'translate-x-2' }}">
                                        <img src="https://f.nooncdn.com/s/app/com/common/images/flags/{{ strtolower($country->iso_code_2) }}.svg"
                                             alt="{{ $isAr ? $country->name_ar : $country->name_en }}" width="18" height="18" class="rounded-sm">
                                        <span>{{ $isAr ? $country->name_ar : $country->name_en }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Language --}}
                <div class="relative group h-full flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button type="button" class="flex items-center gap-[8px] text-[15px] font-bold text-gray-200 hover:text-white transition-colors h-full">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                        </svg>
                        <span>{{ portal_content('nav', 'language_toggle', 'label', 'English', 'العربية') }}</span>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-4"
                         class="absolute {{ $isAr ? 'start-0' : 'end-0' }} top-full w-40 pt-6 -mt-6 z-50" x-cloak>
                        <div class="bg-[#1c1c1c] border border-white/5 shadow-2xl rounded-xl py-2 overflow-hidden mt-1">
                            <a href="{{ route('portal.language', 'en') }}" 
                               class="relative block px-5 py-3 text-[14px] font-bold transition-all duration-200 flex items-center group/sub hover:bg-white/5 {{ !$isAr ? 'text-yellow-400' : 'text-gray-300 hover:text-white' }}">
                                <div class="w-[3px] h-0 bg-yellow-400 absolute {{ $isAr ? 'right-0' : 'left-0' }} top-1/2 -translate-y-1/2 transition-all duration-300 group-hover/sub:h-[70%] {{ $isAr ? 'rounded-l-full' : 'rounded-r-full' }}"></div>
                                <span class="transition-transform duration-300 group-hover/sub:{{ $isAr ? '-translate-x-2' : 'translate-x-2' }}">English</span>
                            </a>
                            <a href="{{ route('portal.language', 'ar') }}" 
                               class="relative block px-5 py-3 text-[14px] font-bold transition-all duration-200 flex items-center group/sub hover:bg-white/5 {{ $isAr ? 'text-yellow-400' : 'text-gray-300 hover:text-white' }}">
                                <div class="w-[3px] h-0 bg-yellow-400 absolute {{ $isAr ? 'right-0' : 'left-0' }} top-1/2 -translate-y-1/2 transition-all duration-300 group-hover/sub:h-[70%] {{ $isAr ? 'rounded-l-full' : 'rounded-r-full' }}"></div>
                                <span class="transition-transform duration-300 group-hover/sub:{{ $isAr ? '-translate-x-2' : 'translate-x-2' }}">العربية</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            {{-- Mobile controls --}}
            <div class="flex lg:hidden items-center gap-4">
                <div class="relative" x-data="{ mobileCountryOpen: false }" @click.outside="mobileCountryOpen = false">
                    <button type="button" @click="mobileCountryOpen = !mobileCountryOpen" aria-label="{{ $isAr ? 'اختر الدولة' : 'Select country' }}">
                        @if($currentCountry)
                            <img src="https://f.nooncdn.com/s/app/com/common/images/flags/{{ strtolower($currentCountry->iso_code_2) }}.svg"
                                 alt="{{ $isAr ? $currentCountry->name_ar : $currentCountry->name_en }}" width="20" height="20" class="rounded-sm">
                        @endif
                    </button>

                    <div x-show="mobileCountryOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute {{ $isAr ? 'start-0' : 'end-0' }} top-full mt-3 w-48 rounded-xl bg-[#1c1c1c] border border-white/10 shadow-2xl py-2 z-50">
                        @foreach($countries as $country)
                            <a href="{{ route('portal.country', $country->site_code) }}"
                               class="flex items-center gap-[10px] px-4 py-2 text-[14px] font-bold transition-colors
                                      {{ $currentCountry && $currentCountry->id === $country->id ? 'text-yellow-400' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                                <img src="https://f.nooncdn.com/s/app/com/common/images/flags/{{ strtolower($country->iso_code_2) }}.svg"
                                     alt="{{ $isAr ? $country->name_ar : $country->name_en }}" width="18" height="18" class="rounded-sm">
                                <span>{{ $isAr ? $country->name_ar : $country->name_en }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ $langToggleUrl }}" class="text-gray-200 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                    </svg>
                </a>
                <button @click="mobileOpen = !mobileOpen" class="text-white p-1" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                        <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="lg:hidden bg-[#1c1c1c] border-t border-white/10 px-4 py-4 space-y-2 shadow-2xl">
        @foreach($navLinks as $link)
            <div x-data="{ subOpen: false }" class="space-y-1">
                <div class="flex items-center justify-between">
                    <a href="{{ route($link['route']) }}"
                       class="block py-2.5 font-bold text-[16px] {{ request()->routeIs($link['route']) ? 'text-yellow-400' : 'text-gray-200' }}">
                        {{ $link['label'] }}
                    </a>
                    @if(isset($link['submenu']))
                        <button @click="subOpen = !subOpen" class="text-gray-400 p-2 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" 
                                 class="w-4 h-4 transition-transform duration-200" :class="subOpen ? 'rotate-180' : ''">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    @endif
                </div>
                
                @if(isset($link['submenu']))
                    <div x-show="subOpen" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="pl-4 pr-4 space-y-1 border-l-2 border-white/10 ml-2" x-cloak>
                        @foreach($link['submenu'] as $sub)
                            <a href="{{ $sub['route'] }}" 
                               class="block py-2 text-[14px] font-bold text-gray-400 hover:text-white transition-colors">
                                {{ $isAr ? $sub['label_ar'] : $sub['label_en'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</header>
