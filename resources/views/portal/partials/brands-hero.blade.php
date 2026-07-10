@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-white">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12 lg:pt-16 pb-8 lg:pb-12">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div class="order-2 lg:order-1 {{ $isAr ? 'text-center lg:text-right' : 'text-center lg:text-left' }}">
                <p class="text-yellow-500 font-black text-xs sm:text-sm uppercase tracking-wider mb-3">
                    {{ $isAr ? 'العلامات التجارية' : 'Brands' }}
                </p>
                <p class="text-gray-600 font-medium text-base sm:text-lg max-w-[52ch] mx-auto lg:mx-0">
                    {{ $isAr
                        ? 'قم بتعزيز الوعي بعلامتك التجارية، والوصول إلى عملاء أكثر، والتواصل مع العملاء من خلال الاستفادة من المنتجات الإستراتيجية لإعلانات نون'
                        : 'Boost your brand awareness, reach large audiences, and connect with customers by leveraging noon ads strategic products.' }}
                </p>
                <p class="text-gray-600 font-medium text-base sm:text-lg max-w-[52ch] mx-auto lg:mx-0 mt-3">
                    {{ $isAr
                        ? 'الإعلانات الفيديوية متاحة الآن على إعلانات العلامة التجارية! ارتق بإعلاناتك إلى المستوى التالي من خلال تحسين تصفح المستخدمين بفيديو يتراوح مدته بين 6 إلى 45 ثانية.'
                        : 'Video Ads are now available on noon ads! Take your advertising to the next level by enhancing user browsing experience with a 6 to 45 second video.' }}
                </p>
                <a href="{{ route('portal.advertise.request', $country ?? 'ae') }}"
                   class="mt-6 inline-flex items-center justify-center bg-yellow-400 hover:bg-yellow-300 text-black
                          font-black text-sm sm:text-base px-6 sm:px-8 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'اتصل بنا' : 'Contact us' }}
                </a>
            </div>
            <div class="order-1 lg:order-2">
                <img src="https://advertise.noon.com/images/brands-home.png"
                     alt="{{ $isAr ? 'العلامات التجارية' : 'Brands' }}" loading="eager"
                     class="w-full max-w-[420px] sm:max-w-[480px] mx-auto">
            </div>
        </div>

        <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-gray-900 text-center mt-6 lg:mt-10">
            {{ $isAr ? 'حلول الإعلانات ذات الشعبية' : 'Popular Ad Solutions' }}
        </h1>
    </div>
</section>
