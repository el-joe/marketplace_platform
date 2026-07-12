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

    <div class="hidden md:block absolute inset-0 {{ $isAr ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black via-black/85 to-transparent lg:via-black/70"></div>
    <div class="md:hidden absolute inset-x-0 bottom-0 h-[70%] bg-gradient-to-t from-[#1c1c1c] via-[#1c1c1c]/80 to-transparent"></div>
    <div class="hidden md:block absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#1c1c1c] to-transparent"></div>

    <div class="absolute inset-x-0 inset-y-0 flex items-center md:items-center">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-[560px] md:mt-12 {{ $isAr ? 'text-right' : 'text-left' }}">
                <h1 class="text-white md:text-[#feee00] font-black leading-[1.1] text-[40px] sm:text-[44px] md:text-[50px]">
                    {{ $isAr ? 'اشحن بطريقتك' : 'Ship Your Way' }}
                </h1>
                <h2 class="mt-3 text-white font-bold text-[24px] sm:text-[28px] md:text-[32px] leading-tight">
                    {{ $isAr ? 'اختر طريقة الشحن المناسبة لك' : 'Choose how you ship' }}
                </h2>
                <p class="mt-3 text-white font-semibold text-[16px] md:text-[18px] max-w-[500px] leading-relaxed">
                    {!! $isAr
                        ? 'التنفيذ من قبل نون (FBN) أو التنفيذ من قبل الشريك (FBP). في كل الحالتين، نساعدك على التوصيل بسرعة.'
                        : 'Fulfilled by noon (FBN) or Fulfilled by Partner (FBP).<br class="hidden md:block"> Either way, we\'ll help you deliver fast.' !!}
                </p>
                <a href="https://youtu.be/rm45BkhIBxY" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center mt-6 w-full sm:w-auto bg-[#feee00] hover:bg-[#e5d600] text-black
                          font-black text-base px-14 py-2.5 rounded-full transition-colors">
                    {{ $isAr ? 'شاهد الفيديو' : 'Watch Video' }}
                </a>
            </div>
        </div>
    </div>
</section>
