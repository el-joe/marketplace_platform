@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl sm:text-3xl font-black text-white mb-6 lg:mb-8">
        {{ $isAr ? 'لماذا تنضم إلينا؟' : 'Why join us?' }}
    </h2>

    <div class="grid gap-4 md:grid-cols-3 md:gap-4 lg:gap-8">
        @php
            $cards = [
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-01.jpg',
                    'title_ar' => 'وصل الملايين',
                    'title_en' => 'Reach millions',
                    'desc_ar' => 'ملايين المتسوقين، تطبيق واحد. نون تعرض منتجاتك لعدد أكبر من الناس، كل يوم.',
                    'desc_en' => 'Millions of shoppers, one app. noon puts your products in front of more people, every day.',
                ],
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-02.jpg',
                    'title_ar' => 'توصيل سريع ومرن',
                    'title_en' => 'Fast, flexible delivery',
                    'desc_ar' => 'اختر طريقة الشحن التي تناسبك. نون تهتم بالسرعة، العناية، ورضا العملاء.',
                    'desc_en' => 'Pick the shipping method that suits you. noon takes care of speed, handling and customer satisfaction.',
                ],
                [
                    'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-join-03.jpg',
                    'title_ar' => 'نمِّ أعمالك بسرعة، واربح أكثر',
                    'title_en' => 'Grow your business faster, earn more',
                    'desc_ar' => 'حقق النمو مع أدوات البيع من نون — صُممت لتحوّل شغفك إلى نتائج حقيقية.',
                    'desc_en' => 'Grow with noon\'s selling tools — built to turn your passion into real results.',
                ],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="relative rounded-2xl overflow-hidden h-full min-h-[320px] md:min-h-[380px]">
                <img src="{{ $card['image'] }}" alt="{{ $isAr ? $card['title_ar'] : $card['title_en'] }}"
                     class="absolute inset-0 w-full h-full object-cover object-bottom">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-black/10"></div>
                <div class="relative h-full flex flex-col justify-end px-5 pb-6 pt-10 lg:px-6 lg:pb-8">
                    <h3 class="text-white font-black text-lg lg:text-xl text-pretty">
                        {{ $isAr ? $card['title_ar'] : $card['title_en'] }}
                    </h3>
                    <p class="mt-3 text-gray-300 text-sm font-medium text-pretty">
                        {{ $isAr ? $card['desc_ar'] : $card['desc_en'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</section>
