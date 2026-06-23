@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-950 py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'مزايانا' : 'Our Benefits' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                {{ $isAr ? 'لماذا تنضم إلينا؟' : 'Why Sell on Noon?' }}
            </h2>
            <p class="mt-4 text-gray-400 text-lg max-w-2xl mx-auto">
                {{ $isAr
    ? 'نون أكثر من مجرد منصة — شريكك الأمثل لتنمية أعمالك في المنطقة.'
    : 'Noon is more than a marketplace — your ultimate partner for business growth in the region.' }}
            </p>
        </div>

        {{-- Cards grid --}}
        <div class="grid md:grid-cols-3 gap-6 mb-16">
            @php
                $cards = [
                    [
                        'icon' => '🌍',
                        'title_ar' => 'وصل الملايين',
                        'title_en' => 'Reach Millions',
                        'desc_ar' => 'وصل إلى أكثر من ٢٠ مليون متسوق نشط عبر الإمارات والسعودية ومصر مع منصة موثوقة وشهيرة.',
                        'desc_en' => 'Reach over 20 million active shoppers across UAE, Saudi Arabia and Egypt on a trusted, well-known platform.',
                        'color' => 'from-blue-500/20 to-blue-600/5',
                        'badge_color' => 'bg-blue-500/20 text-blue-400',
                        'stat_ar' => '٢٠ مليون+',
                        'stat_en' => '20M+',
                        'stat_label_ar' => 'متسوق نشط',
                        'stat_label_en' => 'Active Shoppers',
                    ],
                    [
                        'icon' => '🚚',
                        'title_ar' => 'توصيل سريع ومرن',
                        'title_en' => 'Fast & Flexible Delivery',
                        'desc_ar' => 'اختر بين التنفيذ من نون أو التسليم من متجرك بنفسك — خيارات لوجستية تناسب جميع أحجام الأعمال.',
                        'desc_en' => 'Choose between Noon Fulfillment or Ship from Store — logistics options that fit all business sizes.',
                        'color' => 'from-green-500/20 to-green-600/5',
                        'badge_color' => 'bg-green-500/20 text-green-400',
                        'stat_ar' => '٢٤ ساعة',
                        'stat_en' => '24 Hours',
                        'stat_label_ar' => 'متوسط التوصيل',
                        'stat_label_en' => 'Avg. Delivery',
                    ],
                    [
                        'icon' => '📊',
                        'title_ar' => 'نمّ أعمالك أسرع',
                        'title_en' => 'Grow Faster',
                        'desc_ar' => 'أدوات تحليل متقدمة وإعلانات ذكية وتقارير مفصلة تساعدك على اتخاذ قرارات مدعومة بالبيانات.',
                        'desc_en' => 'Advanced analytics tools, smart ads, and detailed reports to help you make data-driven decisions.',
                        'color' => 'from-purple-500/20 to-purple-600/5',
                        'badge_color' => 'bg-purple-500/20 text-purple-400',
                        'stat_ar' => '٣x',
                        'stat_en' => '3x',
                        'stat_label_ar' => 'متوسط نمو المبيعات',
                        'stat_label_en' => 'Avg. Sales Growth',
                    ],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="relative group bg-gradient-to-br {{ $card['color'] }} bg-gray-900 border border-gray-800
                            hover:border-yellow-400/40 rounded-3xl p-8 transition-all duration-300
                            hover:shadow-xl hover:shadow-yellow-400/5 hover:-translate-y-1">
                    {{-- Icon --}}
                    <div class="text-5xl mb-6">{{ $card['icon'] }}</div>

                    {{-- Stat badge --}}
                    <div class="absolute top-6 {{ $isAr ? 'left-6' : 'right-6' }}">
                        <span class="text-xs font-bold {{ $card['badge_color'] }} px-3 py-1.5 rounded-full">
                            {{ $isAr ? $card['stat_ar'] : $card['stat_en'] }}
                            <span class="text-gray-500 font-normal">
                                {{ $isAr ? $card['stat_label_ar'] : $card['stat_label_en'] }}
                            </span>
                        </span>
                    </div>

                    <h3 class="text-xl font-black text-white mb-3">
                        {{ $isAr ? $card['title_ar'] : $card['title_en'] }}
                    </h3>
                    <p class="text-gray-400 leading-relaxed">
                        {{ $isAr ? $card['desc_ar'] : $card['desc_en'] }}
                    </p>

                    {{-- Arrow --}}
                    <div class="mt-6 flex items-center gap-2 text-yellow-400 text-sm font-semibold opacity-0
                                group-hover:opacity-100 transition-opacity duration-200">
                        <span>{{ $isAr ? 'اعرف أكثر' : 'Learn more' }}</span>
                        <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14m-7-7 7 7-7 7" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Secondary feature grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $features = [
                    ['🏦', 'مدفوعات آمنة', 'Secure Payments'],
                    ['🛡️', 'حماية من الغش', 'Fraud Protection'],
                    ['📱', 'تطبيق سهل', 'Easy App'],
                    ['🎯', 'إعلانات موجّهة', 'Targeted Ads'],
                    ['💬', 'دعم عربي', 'Arabic Support'],
                    ['📈', 'تقارير مفصلة', 'Detailed Reports'],
                    ['🔄', 'إرجاع سهل', 'Easy Returns'],
                    ['⚡', 'تسجيل سريع', 'Fast Onboarding'],
                ];
            @endphp

            @foreach($features as $feature)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 flex items-center gap-3
                            hover:border-gray-700 transition-colors">
                    <span class="text-2xl">{{ $feature[0] }}</span>
                    <span class="text-sm font-semibold text-gray-300">
                        {{ $isAr ? $feature[1] : $feature[2] }}
                    </span>
                </div>
            @endforeach
        </div>

    </div>
</section>