@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-950 py-24" id="testimonials">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'شهادات البائعين' : 'Seller Testimonials' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                {{ $isAr ? 'ماذا يقول بائعونا؟' : 'What Our Sellers Say' }}
            </h2>
        </div>

        {{-- Video embed placeholder --}}
        <div class="relative max-w-3xl mx-auto mb-16 rounded-3xl overflow-hidden bg-gray-900
                    border border-gray-800 aspect-video flex items-center justify-center group cursor-pointer">
            {{-- Thumbnail overlay --}}
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 to-gray-800"></div>

            {{-- Play button --}}
            <div class="relative z-10 flex flex-col items-center gap-4">
                <div class="w-20 h-20 bg-yellow-400 rounded-full flex items-center justify-center
                            shadow-xl shadow-yellow-400/30 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-gray-950 {{ $isAr ? 'mr-1' : 'ml-1' }}" viewBox="0 0 24 24"
                        fill="currentColor">
                        <polygon points="5,3 19,12 5,21" />
                    </svg>
                </div>
                <div class="text-white font-bold text-lg">
                    {{ $isAr ? 'شاهد قصص نجاح بائعينا' : 'Watch Our Seller Success Stories' }}
                </div>
                <div class="text-gray-400 text-sm">
                    {{ $isAr ? 'ألف + بائع ناجح' : '1,000+ successful sellers' }}
                </div>
            </div>

            {{-- Decorative text --}}
            <div class="absolute bottom-6 {{ $isAr ? 'right-6' : 'left-6' }} text-gray-600 text-xs">
                YouTube Placeholder
            </div>
        </div>

        {{-- Testimonial cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $testimonials = [
                    [
                        'avatar' => '👨‍💼',
                        'name_ar' => 'محمد العلي',
                        'name_en' => 'Mohammed Al Ali',
                        'store_ar' => 'متجر الإلكترونيات الذكية',
                        'store_en' => 'Smart Electronics Store',
                        'quote_ar' => 'منذ انضممت إلى نون، تضاعفت مبيعاتي ثلاث مرات في أقل من ستة أشهر. الدعم الفني رائع والمنصة سهلة الاستخدام جداً.',
                        'quote_en' => 'Since joining Noon, my sales tripled in less than six months. The technical support is amazing and the platform is very easy to use.',
                        'rating' => 5,
                        'growth' => '+٣٠٠٪',
                        'growth_en' => '+300%',
                    ],
                    [
                        'avatar' => '👩‍💼',
                        'name_ar' => 'فاطمة الزهراء',
                        'name_en' => 'Fatima Al Zahra',
                        'store_ar' => 'بوتيك الأزياء الحديثة',
                        'store_en' => 'Modern Fashion Boutique',
                        'quote_ar' => 'خدمة التنفيذ من نون غيّرت أعمالي كلياً. لم أعد أقلق بشأن الشحن وأركز فقط على توفير أفضل المنتجات.',
                        'quote_en' => 'Noon\'s fulfillment service completely transformed my business. I no longer worry about shipping and focus only on providing the best products.',
                        'rating' => 5,
                        'growth' => '+١٨٠٪',
                        'growth_en' => '+180%',
                    ],
                    [
                        'avatar' => '👨‍🍳',
                        'name_ar' => 'أحمد الشمري',
                        'name_en' => 'Ahmed Al Shammari',
                        'store_ar' => 'مطبخ الحلويات الشرقية',
                        'store_en' => 'Oriental Sweets Kitchen',
                        'quote_ar' => 'الأدوات التحليلية ساعدتني على فهم ما يريده العملاء بالضبط. الآن معدل تحويلي أعلى بكثير مما كان عليه.',
                        'quote_en' => 'The analytics tools helped me understand exactly what customers want. Now my conversion rate is much higher than it was.',
                        'rating' => 4,
                        'growth' => '+٢٢٠٪',
                        'growth_en' => '+220%',
                    ],
                    [
                        'avatar' => '👩‍🔬',
                        'name_ar' => 'نورا الحسيني',
                        'name_en' => 'Noura Al Husseini',
                        'store_ar' => 'متجر العناية الطبيعية',
                        'store_en' => 'Natural Care Store',
                        'quote_ar' => 'بدأت بمنتجات قليلة والآن لدي أكثر من ٢٠٠ منتج. نون أعطتني الأدوات والثقة للتوسع في ثلاث دول.',
                        'quote_en' => 'I started with a few products and now have over 200. Noon gave me the tools and confidence to expand into three countries.',
                        'rating' => 5,
                        'growth' => '+٤٥٠٪',
                        'growth_en' => '+450%',
                    ],
                ];
            @endphp

            @foreach($testimonials as $t)
                <div class="bg-gray-900 border border-gray-800 hover:border-gray-700 rounded-3xl p-6
                            transition-all duration-300 hover:-translate-y-1 hover:shadow-lg flex flex-col">

                    {{-- Header --}}
                    <div class="flex items-center gap-3 mb-4 {{ $isAr ? 'flex-row-reverse' : '' }}">
                        <div class="w-12 h-12 bg-gray-800 rounded-full flex items-center justify-center text-2xl shrink-0">
                            {{ $t['avatar'] }}
                        </div>
                        <div class="{{ $isAr ? 'text-right' : 'text-left' }}">
                            <div class="font-bold text-white text-sm">{{ $isAr ? $t['name_ar'] : $t['name_en'] }}</div>
                            <div class="text-xs text-gray-400">{{ $isAr ? $t['store_ar'] : $t['store_en'] }}</div>
                        </div>
                    </div>

                    {{-- Stars --}}
                    <div class="flex gap-0.5 mb-3 {{ $isAr ? 'flex-row-reverse justify-end' : '' }}">
                        @for($s = 1; $s <= 5; $s++)
                            <svg class="w-4 h-4 {{ $s <= $t['rating'] ? 'text-yellow-400' : 'text-gray-700' }}"
                                viewBox="0 0 24 24" fill="currentColor">
                                <polygon
                                    points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />
                            </svg>
                        @endfor
                    </div>

                    {{-- Quote --}}
                    <p class="text-gray-400 text-sm leading-relaxed flex-1 {{ $isAr ? 'text-right' : 'text-left' }}">
                        "{{ $isAr ? $t['quote_ar'] : $t['quote_en'] }}"
                    </p>

                    {{-- Growth badge --}}
                    <div class="mt-4 pt-4 border-t border-gray-800">
                        <span class="inline-flex items-center gap-1 bg-green-500/20 text-green-400
                                     text-xs font-bold px-3 py-1.5 rounded-full">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="m23 6-9.5 9.5-5-5L1 18" />
                                <path d="M17 6h6v6" />
                            </svg>
                            {{ $isAr ? $t['growth'] : $t['growth_en'] }}
                            {{ $isAr ? 'نمو في المبيعات' : 'Sales Growth' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>