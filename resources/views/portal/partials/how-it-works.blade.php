@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<div class="bg-[#151515] py-10 lg:py-12">
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-yellow-400 font-black text-lg lg:text-xl mb-1">{{ $isAr ? 'البدء' : 'Getting started' }}</p>
        <h2 class="text-white font-black text-xl lg:text-2xl leading-snug mb-8 lg:mb-10">
            {{ $isAr ? 'جاهز انك تبدأ البيع؟' : 'Ready to start selling?' }}<br>
            {{ $isAr ? 'انه سريع و سهل - اليك ما تحتاجه' : "It's quick and easy - here's what you'll need" }}
        </h2>

        <div class="grid md:grid-cols-2 gap-10 lg:gap-16 items-start">

            {{-- Floating device grid --}}
            <div class="relative mx-auto max-w-[420px] w-full">
                <div class="grid grid-cols-3 gap-4 items-center">
                    <div class="self-center -mt-6">
                        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-sell-grid-01.png" alt=""
                             class="animate-[float_6s_ease-in-out_infinite] rounded-xl w-full">
                    </div>
                    <div class="flex flex-col gap-4">
                        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-sell-grid-02.png" alt=""
                             class="animate-[float_7s_ease-in-out_infinite] rounded-xl w-full" style="animation-delay:1s">
                        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-sell-grid-03.png" alt=""
                             class="animate-[float_5s_ease-in-out_infinite] rounded-xl w-full" style="animation-delay:.5s">
                    </div>
                    <div class="flex flex-col gap-4 mt-10">
                        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-sell-grid-04.png" alt=""
                             class="animate-[float_6s_ease-in-out_infinite] rounded-xl w-full" style="animation-delay:1.5s">
                        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-sell-grid-05.png" alt=""
                             class="animate-[float_8s_ease-in-out_infinite] rounded-xl w-full" style="animation-delay:.8s">
                    </div>
                </div>
            </div>

            {{-- Checklist + steps --}}
            <div>
                <h3 class="text-white font-bold text-lg mb-4">{{ $isAr ? 'قائمة تجهيز البائع:' : 'Seller readiness checklist:' }}</h3>
                <ul class="space-y-3 mb-8">
                    @foreach([
                        [$isAr ? 'عنوان البريد الالكتروني (للاستخدام التجاري او الشخصي) و رقم الهاتف' : 'A business or personal email address and a phone number'],
                        [$isAr ? 'السجل التجاري / رخصة التجارة' : 'Trade licence / commercial registration'],
                        [$isAr ? 'الهوية الشخصية - جواز السفر / بطاقة الهوية الاماراتية' : 'A valid ID - passport or Emirates ID'],
                    ] as $item)
                        <li class="flex items-start gap-3 text-gray-200 text-[15px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3E008" width="18" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                            <span>{{ $item[0] }}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('portal.faq') }}"
                   class="inline-block border border-gray-600 hover:border-yellow-400 text-white text-sm font-bold px-5 py-2.5 rounded-full transition-colors mb-10">
                    {{ $isAr ? 'الأسئلة الشائعة حول المستندات' : 'Document FAQs' }}
                </a>

                <div class="space-y-3 mb-6">
                    @foreach([
                        [$isAr ? 'الخطوة 1:' : 'Step 1:', $isAr ? 'قم بالتسجيل كبائع' : 'Sign up as a seller'],
                        [$isAr ? 'الخطوة 2:' : 'Step 2:', $isAr ? 'اختر نظام التسجيل' : 'Choose your fulfilment model'],
                        [$isAr ? 'الخطوة 3:' : 'Step 3:', $isAr ? 'قم بادراج منتجاتك' : 'List your products'],
                    ] as $step)
                        <p class="text-[15px]">
                            <span class="text-gray-500">{{ $step[0] }} </span>
                            <span class="text-white font-semibold">{{ $step[1] }}</span>
                        </p>
                    @endforeach
                </div>

                <p class="text-[15px] text-gray-300 mb-4">
                    <span class="text-white font-bold">{{ $isAr ? 'و هذا هو كل شئ!' : "And that's it!" }}</span>
                    {{ $isAr ? ' عميلك الاول قد يكون علي بعد نقرة واحدة فقط' : ' Your first customer could just be one click away' }}
                </p>

                <a href="{{ route('portal.how-it-works') }}" class="inline-flex items-center gap-2 text-yellow-400 font-bold text-sm">
                    {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>

{{-- Dubai Traders Program banner --}}
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <a href="{{ route('portal.register') }}" class="block">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-trader-banner-ar.png"
             alt="Dubai Traders Program" class="w-full rounded-2xl lg:hidden">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-trader-banner-strip-ar.jpg"
             alt="Dubai Traders Program" class="w-full rounded-2xl hidden lg:block">
    </a>
</section>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>
