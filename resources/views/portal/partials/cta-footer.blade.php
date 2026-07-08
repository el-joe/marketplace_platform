@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-yellow-400 py-12 lg:py-16">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-black/70 font-bold text-sm mb-2">{{ $isAr ? 'يبدأ هنا' : 'It starts here' }}</p>
        <h2 class="text-3xl sm:text-4xl font-black text-black leading-tight mb-3">
            {{ $isAr ? 'جاهز للبيع؟' : 'Ready to sell?' }}
        </h2>
        <p class="text-black/80 text-base sm:text-lg mb-8 max-w-2xl mx-auto leading-relaxed">
            {{ $isAr
                ? 'انضم إلى الاف البائعين في جميع أنحاء المنطقة الذين ينمون ويزدهرون مع نون'
                : 'Join thousands of sellers across the region who are growing and thriving with noon' }}
        </p>
        <a href="{{ route('portal.register') }}"
           class="inline-flex items-center gap-2 bg-black hover:bg-gray-900 text-white
                  font-black text-base px-8 py-3.5 rounded-full transition-colors">
            {{ $isAr ? 'سجل الآن' : 'Register now' }}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" class="{{ $isAr ? '-scale-x-100' : '' }}">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
            </svg>
        </a>
    </div>
</section>

{{-- ── Footer ──────────────────────────────────────────────────────────── --}}
<footer class="bg-black border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <div class="grid md:grid-cols-4 gap-8 mb-10">

            {{-- Brand --}}
            <div class="{{ $isAr ? 'text-right' : 'text-left' }}">
                <div class="flex items-center gap-2 mb-4 {{ $isAr ? 'justify-end' : 'justify-start' }}">
                    <span class="bg-yellow-400 text-gray-950 font-black text-xl px-2 py-0.5 rounded">noon</span>
                    <span class="text-white text-sm font-semibold">{{ $isAr ? 'للبائعين' : 'Sellers' }}</span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">
                    {{ $isAr
                        ? 'المنصة الرائدة للتجارة الإلكترونية في الشرق الأوسط. نمّ أعمالك معنا.'
                        : 'The leading e-commerce platform in the Middle East. Grow your business with us.' }}
                </p>
                <div class="flex gap-3 mt-4 {{ $isAr ? 'justify-end' : 'justify-start' }}">
                    @foreach([
                        ['https://twitter.com/noon', 'X (Twitter)', '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.912-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
                        ['https://instagram.com/noon', 'Instagram', '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'],
                        ['https://linkedin.com/company/noon', 'LinkedIn', '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>'],
                    ] as $social)
                    <a href="{{ $social[0] }}" target="_blank" rel="noopener"
                       aria-label="{{ $social[1] }}"
                       class="w-9 h-9 bg-gray-800 hover:bg-yellow-400 text-gray-400 hover:text-gray-950
                              rounded-full flex items-center justify-center transition-all">
                        {!! $social[2] !!}
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Links --}}
            @php
            $footerCols = [
                [
                    'title_ar' => 'البائعون',
                    'title_en' => 'Sellers',
                    'links' => [
                        ['التسجيل', 'Register', route('portal.register')],
                        ['تسجيل الدخول', 'Login', route('partner.login')],
                        ['كيف تبدأ', 'How to Start', route('portal.how-it-works')],
                        ['الأسئلة الشائعة', 'FAQ', route('portal.faq')],
                    ],
                ],
                [
                    'title_ar' => 'الخدمات',
                    'title_en' => 'Services',
                    'links' => [
                        ['التوصيل والشحن', 'Shipping & Delivery', route('portal.fulfillment')],
                        ['الأدوات الذكية', 'Smart Tools', route('portal.smart-tools')],
                        ['الإعلانات', 'Advertising', route('portal.smart-tools')],
                        ['التحليلات', 'Analytics', route('portal.smart-tools')],
                    ],
                ],
                [
                    'title_ar' => 'الدعم',
                    'title_en' => 'Support',
                    'links' => [
                        ['مركز المساعدة', 'Help Center', '#'],
                        ['تواصل معنا', 'Contact Us', 'mailto:sellers@noon.com'],
                        ['سياسة الخصوصية', 'Privacy Policy', '#'],
                        ['الشروط والأحكام', 'Terms & Conditions', '#'],
                    ],
                ],
            ];
            @endphp

            @foreach($footerCols as $col)
            <div class="{{ $isAr ? 'text-right' : 'text-left' }}">
                <h4 class="font-bold text-white mb-4 text-sm uppercase tracking-wider">
                    {{ $isAr ? $col['title_ar'] : $col['title_en'] }}
                </h4>
                <ul class="space-y-2">
                    @foreach($col['links'] as $link)
                    <li>
                        <a href="{{ $link[2] }}"
                           class="text-gray-400 hover:text-yellow-400 transition-colors text-sm">
                            {{ $isAr ? $link[0] : $link[1] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach

        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-500 text-sm">
                &copy; {{ date('Y') }} نون للتجارة الإلكترونية — Noon E-commerce. {{ $isAr ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}
            </p>
            <div class="flex items-center gap-4">
                <a href="{{ route('portal.language', 'ar') }}"
                   class="text-xs {{ $isAr ? 'text-yellow-400' : 'text-gray-500 hover:text-gray-300' }} transition-colors">العربية</a>
                <span class="text-gray-700">|</span>
                <a href="{{ route('portal.language', 'en') }}"
                   class="text-xs {{ !$isAr ? 'text-yellow-400' : 'text-gray-500 hover:text-gray-300' }} transition-colors">English</a>
            </div>
        </div>

    </div>
</footer>
