@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="relative overflow-hidden bg-black">
    {{-- Mobile: near full-viewport hero --}}
    <div class="h-[calc(100svh_-_72px)] md:hidden relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-hero.jpg"
             alt="{{ $isAr ? 'مستودع نون في حركة كاملة' : 'A noon warehouse in full motion' }}"
             class="absolute inset-0 w-full h-full object-cover {{ $isAr ? '-scale-x-100' : '' }}">
    </div>

    {{-- Desktop: fixed-height hero, image anchored to one side --}}
    <div class="hidden md:flex md:justify-end h-[444px] relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-hero.jpg"
             alt="{{ $isAr ? 'مستودع نون في حركة كاملة' : 'A noon warehouse in full motion' }}"
             class="h-full w-full max-w-none object-cover object-center lg:w-[80%] xl:w-[70%] xl:object-[100%_25%] {{ $isAr ? '-scale-x-100' : '' }}">
    </div>

    <div class="absolute inset-0 {{ $isAr ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black via-black/85 to-transparent lg:via-black/70"></div>
    <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black to-transparent"></div>

    <div class="absolute inset-x-0 top-[30%] md:top-0 md:inset-y-0 flex items-start md:items-center">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-[560px] {{ $isAr ? 'text-right' : 'text-left' }}">
                <h1 class="text-yellow-400 font-black text-[28px] md:text-[36px] mb-2">
                    {{ $isAr ? 'نمِّ أعمالك بذكاء' : 'Grow your business smarter' }}
                </h1>
                <h2 class="text-white font-extrabold leading-[1.1] text-[24px] md:text-[36px]">
                    {{ $isAr ? 'الإعلانات، الرسوم، والرؤى التحليلية' : 'Ads, fees, and analytical insights' }}
                </h2>
                <p class="mt-3 text-white font-semibold text-[16px] md:text-[18px]">
                    {{ $isAr ? 'أنفق بذكاء، وتوسّع بسرعة' : 'Spend smart, and scale fast' }}
                </p>
            </div>
        </div>
    </div>
</section>
