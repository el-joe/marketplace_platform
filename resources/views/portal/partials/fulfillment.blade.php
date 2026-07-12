@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] grid md:grid-cols-[1.5fr_2fr]">
        <div class="relative aspect-[4/3] md:aspect-auto">
            <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-fbn.jpg"
                 alt="{{ $isAr ? 'الشحن والتوصيل' : 'Shipping and Fulfilment' }}"
                 class="absolute inset-0 w-full h-full object-cover">
        </div>
        <div class="pt-8 px-6 pb-8 md:pt-10 md:px-10 md:pb-10">
            <h2 class="text-yellow-400 font-black text-xl lg:text-2xl">{{ $isAr ? 'الشحن والتوصيل' : 'Shipping and Fulfilment' }}</h2>
            <h3 class="text-white font-black text-lg lg:text-xl mt-1 mb-4">
                {{ $isAr ? 'خيارات تنفيذ مرنة تناسبك' : 'Flexible fulfilment options that work for you' }}
            </h3>
            <p class="text-gray-300 text-[15px] font-medium mb-6">
                {{ $isAr ? 'اختر النموذج الذي يناسب عملك اليوم، وتوسع بثقة' : 'Pick the model that fits your business today, and scale with confidence' }}
            </p>
            <ul class="space-y-3 mb-8">
                @foreach([
                    [$isAr ? 'التنفيذ من قبل نون — مصمم للسرعة' : 'Fulfilled by noon — Built for speed'],
                    [$isAr ? 'التنفيذ من قبل الشريك — مصمم للمرونة' : 'Fulfilled by Partner — Built for flexibility'],
                ] as $item)
                    <li class="flex items-start gap-3 text-gray-200 text-[15px]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3E008" width="18" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <span>{{ $item[0] }}</span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('portal.fulfillment') }}" class="inline-flex items-center gap-2 text-yellow-400 font-bold text-sm">
                {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
</section>
