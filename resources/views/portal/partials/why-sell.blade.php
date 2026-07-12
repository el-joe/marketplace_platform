@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 mt-4 lg:mt-0">
    <h2 class="text-2xl sm:text-3xl font-black text-white mb-4 lg:mb-5">
        {{ $isAr ? 'لماذا تنضم إلينا؟' : 'Why Join Us?' }}
    </h2>

    <div class="grid gap-4 md:grid-cols-3 md:gap-4 lg:gap-6">
        @php
            $cards = [
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-01.jpg',
                    'title_ar' => 'وصل الملايين',
                    'title_en' => 'Reach Millions',
                    'desc_ar' => 'ملايين المتسوقين، تطبيق واحد. نون تعرض منتجاتك لعدد أكبر من الناس، كل يوم.',
                    'desc_en' => 'Millions of shoppers, one app. noon puts your products in front of more people, every single day.',
                ],
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-02.jpg',
                    'title_ar' => 'توصيل سريع ومرن',
                    'title_en' => 'Fast, Flexible Delivery',
                    'desc_ar' => 'اختر طريقة الشحن التي تناسبك. نون تهتم بالسرعة، العناية، ورضا العملاء.',
                    'desc_en' => 'Choose how you ship. noon handles the speed, care, and customer smiles.',
                ],
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-03.jpg',
                    'title_ar' => 'نمِّ أعمالك بسرعة، واربح أكثر',
                    'title_en' => 'Grow Fast, Earn More',
                    'desc_ar' => 'حقق النمو مع أدوات البيع من نون — صُممت لتحوّل شغفك إلى نتائج حقيقية.',
                    'desc_en' => 'Unlock growth with noon\'s seller tools — built to turn your hustle into real results.',
                ],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="relative rounded-xl overflow-hidden flex flex-col justify-center sm:justify-end min-h-[140px] sm:min-h-0 sm:aspect-square md:aspect-[4/3] lg:aspect-[16/9]">
                <img src="{{ $card['image'] }}" alt="{{ $isAr ? $card['title_ar'] : $card['title_en'] }}"
                     class="absolute inset-0 w-full h-full object-cover object-bottom">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/80 to-transparent"></div>
                <div class="relative px-4 py-4 sm:pb-5 lg:px-5 lg:pb-6">
                    <h3 class="text-white font-black text-base lg:text-lg text-pretty leading-tight">
                        {{ $isAr ? $card['title_ar'] : $card['title_en'] }}
                    </h3>
                    <p class="mt-1.5 text-gray-300 text-xs sm:text-sm font-medium text-pretty leading-snug">
                        {{ $isAr ? $card['desc_ar'] : $card['desc_en'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</section>
