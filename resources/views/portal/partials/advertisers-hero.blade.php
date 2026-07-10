@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-white">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12 lg:pt-16 pb-8 lg:pb-12">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div class="order-2 lg:order-1 {{ $isAr ? 'text-center lg:text-right' : 'text-center lg:text-left' }}">
                <p class="text-yellow-500 font-black text-xs sm:text-sm uppercase tracking-wider mb-3">
                    {{ $isAr ? 'المعلنين' : 'Advertisers' }}
                </p>
                <p class="text-gray-600 font-medium text-base sm:text-lg max-w-[52ch] mx-auto lg:mx-0">
                    {{ $isAr
                        ? "'لا تبيع على نون، ولكنك تتطلع إلى تعزيز ظهورك لجماهير فئة كبيرة عبر المناطق الجغرافية؟ هل ترغب في زيادة الوعي بأحداث/إطلاقات محددة؟ هل تخطط لمشاركة عروض محددة للعملاء المستهدفين؟'"
                        : 'Not selling on noon, but looking to boost your visibility to large audiences across geographies? Want to increase awareness of specific events/ launches? Planning on sharing specific offers to targeted customers?' }}
                </p>
                <a href="{{ route('portal.advertise.request', $country ?? 'ae') }}"
                   class="mt-6 inline-flex items-center justify-center bg-yellow-400 hover:bg-yellow-300 text-black
                          font-black text-sm sm:text-base px-6 sm:px-8 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'اتصل بنا' : 'Contact us' }}
                </a>
            </div>
            <div class="order-1 lg:order-2">
                <img src="https://advertise.noon.com/images/advertiser-home-ar.png"
                     alt="{{ $isAr ? 'المعلنين' : 'Advertisers' }}" loading="eager"
                     class="w-full max-w-[420px] sm:max-w-[480px] mx-auto">
            </div>
        </div>

        <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-gray-900 text-center mt-6 lg:mt-10">
            {{ $isAr ? 'حلول الإعلانات ذات الشعبية' : 'Popular Ad Solutions' }}
        </h1>
    </div>
</section>
