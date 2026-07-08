@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<div id="fulfilment">
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl font-black text-white mb-6 lg:mb-8">
            {{ $isAr ? 'اختر نموذج التنفيذ المناسب لك' : 'Choose the fulfilment model that fits you' }}
        </h2>

        <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] grid md:grid-cols-[1.5fr_2fr]">
            <div class="relative aspect-[4/3] md:aspect-auto">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-fulfillment-model.jpg"
                     alt="{{ $isAr ? 'موظف نون يوجه أسطول توصيل' : 'A noon employee instructing a fleet of delivery vans' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-8 px-6 pb-8 md:pt-10 md:px-10 md:pb-10">
                <h3 class="text-white font-black text-lg lg:text-xl text-pretty">
                    {{ $isAr
                        ? 'يشمل التخزين، التعبئة، الشحن، الإرجاع، والاستبدال — وتوصيل ممتاز يضمن عودة العملاء مرة بعد مرة.'
                        : 'Covers storage, packing, shipping, returns and exchanges — with great delivery that keeps customers coming back.' }}
                </h3>
                <p class="mt-4 text-gray-300 text-[14px] font-medium">
                    {{ $isAr
                        ? 'يمكنك تنفيذ الطلبات بنفسك (FBP) أو ترك المهمة لنون من خلال التنفيذ من قِبل نون (FBN).'
                        : 'You can fulfil orders yourself (FBP) or leave it to noon with Fulfilled by noon (FBN).' }}
                </p>
                <p class="text-yellow-400 font-black text-xs uppercase tracking-wider mt-5">{{ $isAr ? 'نصيحة احترافية:' : 'Pro tip:' }}</p>
                <p class="text-gray-300 text-[14px] font-medium">
                    {{ $isAr
                        ? 'يمكنك الدمج بين النماذج — اختر أفضل نموذج لكل منتج لتحسين عملياتك.'
                        : 'You can mix models — pick the best one for each product to optimise your operations.' }}
                </p>

                <div class="mt-8 flex flex-col gap-4 items-start sm:flex-row sm:items-center sm:gap-6">
                    <a target="_blank" rel="noopener" title="{{ $isAr ? 'يفتح في نافذة جديدة' : 'Opens in a new window' }}"
                       href="https://www.youtube.com/@noonsellerlab7442/videos"
                       class="w-full sm:w-auto text-center bg-yellow-400 hover:bg-yellow-300 text-black font-black text-sm px-6 py-2.5 rounded-full transition-colors">
                        {{ $isAr ? 'شاهد فيديوهات الشرح' : 'Watch explainer videos' }}
                    </a>
                    <a href="{{ route('portal.fulfillment') }}" class="inline-flex items-center gap-2 text-yellow-400 font-bold text-sm">
                        {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 mt-8 lg:mt-10">
        <div class="rounded-2xl overflow-hidden aspect-video">
            <iframe class="w-full h-full" src="https://www.youtube.com/embed/t9a9ApEXCRc" title="noon deliveries"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
    </section>
</div>
