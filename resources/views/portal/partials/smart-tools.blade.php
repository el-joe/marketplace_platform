@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<div class="bg-[#151515] pt-8 pb-10 lg:pt-10 lg:pb-14">
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-yellow-400 font-black text-xl lg:text-2xl">{{ $isAr ? 'نمِّ أعمالك بذكاء' : 'Grow your business smarter' }}</h2>
        <h3 class="text-white font-black text-lg lg:text-xl mt-1 mb-6 lg:mb-8">
            {{ $isAr ? 'كل ما تحتاجه للتوسع والبقاء في الصدارة' : 'Everything you need to scale and stay ahead' }}
        </h3>

        <div class="grid gap-4 md:grid-cols-3 md:gap-4 lg:gap-8">
            @php
                $cards = [
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-01.jpg',
                        'title_ar' => 'إعلانات تحقق نتائج',
                        'title_en' => 'Ads that deliver results',
                        'desc_ar' => 'استفد من إعلانات نون، مجموعتنا الإعلانية الداخلية لعرض منتجاتك أمام المزيد من العملاء.',
                        'desc_en' => 'Use noon Ads, our in-house advertising suite, to put your products in front of more customers.',
                    ],
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-fbp.jpg',
                        'title_ar' => 'اعرف تكاليفك',
                        'title_en' => 'Know your costs',
                        'desc_ar' => 'مع هيكل الرسوم التنافسي والشفاف من نون، ستعرف دائمًا ما ستكسبه - لا مفاجآت، فقط نمو',
                        'desc_en' => 'With noon\'s competitive, transparent fee structure, you\'ll always know what you\'ll earn - no surprises, just growth',
                    ],
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-hero-092025.jpg',
                        'title_ar' => 'توسع مع الرؤى',
                        'title_en' => 'Scale with insights',
                        'desc_ar' => 'حوّل البيانات إلى قرارات أذكى باستخدام أدوات التقارير والرؤى القوية لدينا',
                        'desc_en' => 'Turn data into smarter decisions with our powerful reporting and insights tools',
                    ],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="relative rounded-2xl overflow-hidden h-full min-h-[320px] md:min-h-[380px]">
                    <img src="{{ $card['image'] }}" alt="{{ $isAr ? $card['title_ar'] : $card['title_en'] }}"
                         class="absolute inset-0 w-full h-full object-cover object-bottom">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-black/10"></div>
                    <div class="relative h-full flex flex-col justify-end px-5 pb-6 pt-10 lg:px-6 lg:pb-8">
                        <h4 class="text-white font-black text-lg lg:text-xl text-pretty">
                            {{ $isAr ? $card['title_ar'] : $card['title_en'] }}
                        </h4>
                        <p class="mt-3 text-gray-300 text-sm font-medium text-pretty">
                            {{ $isAr ? $card['desc_ar'] : $card['desc_en'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-end">
            <a href="{{ route('portal.smart-tools') }}" class="inline-flex items-center gap-2 text-yellow-400 font-bold text-sm">
                {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                </svg>
            </a>
        </div>
    </section>
</div>
