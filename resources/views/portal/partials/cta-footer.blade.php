@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-yellow-400 py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 bg-yellow-500/30 border border-yellow-500/50
                    text-yellow-950 text-sm font-bold px-4 py-1.5 rounded-full mb-6">
            <span class="w-2 h-2 bg-yellow-800 rounded-full animate-pulse"></span>
            {{ $isAr ? 'انضم الآن — مجاناً' : 'Join Now — It\'s Free' }}
        </div>

        <h2 class="text-4xl sm:text-5xl font-black text-gray-950 leading-tight mb-4">
            {{ $isAr ? 'جاهز للبيع؟' : 'Ready to Sell?' }}
        </h2>
        <p class="text-gray-800 text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            {{ $isAr
                ? 'انضم إلى آلاف البائعين الناجحين على نون وابدأ رحلتك نحو النمو والنجاح في أكبر منصة تجارة إلكترونية بالمنطقة.'
                : 'Join thousands of successful sellers on Noon and start your journey towards growth and success on the region\'s largest e-commerce platform.' }}
        </p>

        {{-- CTAs --}}
        <div class="flex flex-wrap gap-4 justify-center mb-16">
            <a href="{{ route('portal.register') }}"
               class="inline-flex items-center gap-2 bg-gray-950 hover:bg-gray-800 text-white
                      font-black text-lg px-10 py-4 rounded-full shadow-xl transition-all
                      hover:scale-105 hover:shadow-2xl">
                {{ $isAr ? 'سجّل الآن' : 'Register Now' }}
                <svg class="w-5 h-5 {{ $isAr ? 'rotate-180' : '' }}" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12h14m-7-7 7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('portal.how-it-works') }}"
               class="inline-flex items-center gap-2 bg-yellow-300 hover:bg-yellow-200 text-gray-950
                      font-bold text-lg px-10 py-4 rounded-full border-2 border-yellow-600/30
                      transition-all hover:scale-105">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 8v4m0 4h.01"/>
                </svg>
                {{ $isAr ? 'حمّل دليل البائع' : 'Download Seller Guide' }}
            </a>
        </div>

        {{-- Stats row --}}
        <div class="grid grid-cols-3 gap-6 max-w-2xl mx-auto mb-16">
            @foreach([
                ['٥٠ ألف+', '50K+', 'بائع نشط', 'Active Sellers'],
                ['٩٨٪', '98%', 'رضا البائعين', 'Seller Satisfaction'],
                ['٢٤ ساعة', '24h', 'متوسط التوصيل', 'Avg Delivery'],
            ] as $s)
            <div>
                <div class="text-3xl font-black text-gray-950">{{ $isAr ? $s[0] : $s[1] }}</div>
                <div class="text-sm text-gray-700 font-medium">{{ $isAr ? $s[2] : $s[3] }}</div>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- ── Footer ──────────────────────────────────────────────────────────── --}}
<footer class="bg-[#111111] border-t border-gray-800">
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
