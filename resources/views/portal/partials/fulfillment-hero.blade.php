@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="relative overflow-hidden bg-black">
    {{-- Mobile: near full-viewport hero --}}
    <div class="h-[calc(100svh_-_72px)] md:hidden relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-hero-092025.jpg"
             alt="{{ $isAr ? 'مستودع نون' : 'A noon warehouse' }}"
             class="absolute inset-0 w-full h-full object-cover {{ $isAr ? '-scale-x-100' : '' }}">
    </div>

    {{-- Desktop: fixed-height hero, image anchored to one side --}}
    <div class="hidden md:flex md:justify-end h-[444px] relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-hero-092025.jpg"
             alt="{{ $isAr ? 'مستودع نون' : 'A noon warehouse' }}"
             class="h-full w-full max-w-none object-cover object-center lg:w-[80%] xl:w-[70%] xl:object-[100%_25%] {{ $isAr ? '-scale-x-100' : '' }}">
    </div>

    <div class="absolute inset-0 {{ $isAr ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black via-black/85 to-transparent lg:via-black/70"></div>
    <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black to-transparent"></div>

    <div class="absolute inset-x-0 top-[30%] md:top-0 md:inset-y-0 flex items-start md:items-center">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-[560px] {{ $isAr ? 'text-right' : 'text-left' }}">
                <h1 class="text-white md:text-yellow-400 font-extrabold leading-[1.1] text-[36px] md:text-[50px]">
                    {{ $isAr ? 'اشحن بطريقتك' : 'Ship your way' }}
                </h1>
                <h2 class="mt-3 text-white font-bold text-[20px] md:text-[32px] leading-tight">
                    {{ $isAr ? 'اختر طريقة الشحن المناسبة لك' : 'Choose the shipping method that fits you' }}
                </h2>
                <p class="mt-2 text-gray-200 font-semibold text-[16px] md:text-[18px] max-w-[500px] leading-relaxed">
                    {{ $isAr
                        ? 'التنفيذ من قبل نون (FBN) أو التنفيذ من قبل الشريك (FBP). في كل الحالتين، نساعدك على التوصيل بسرعة.'
                        : 'Fulfilled by noon (FBN) or Fulfilled by partner (FBP). Either way, we help you deliver fast.' }}
                </p>
                <a href="https://youtu.be/rm45BkhIBxY" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center mt-6 w-full sm:w-auto bg-yellow-400 hover:bg-yellow-300 text-black
                          font-black text-base px-7 py-3.5 rounded-full transition-colors">
                    {{ $isAr ? 'شاهد الفيديو' : 'Watch the video' }}
                </a>
            </div>
        </div>
    </div>
</section>
