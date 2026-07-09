@php
    $isAr = session('locale', 'ar') === 'ar';
    $country = $country ?? 'ae';

    $features = [
        [
            'icon' => 'https://advertise.noon.com/images/targetAds.png',
            'label_ar' => 'الجمهور المستهدف',
            'label_en' => 'Targeted audience',
        ],
        [
            'icon' => 'https://advertise.noon.com/images/productVisibilityWatch.png',
            'label_ar' => 'رؤية المنتج',
            'label_en' => 'Product visibility',
        ],
        [
            'icon' => 'https://advertise.noon.com/images/growIncreaseConversion.png',
            'label_ar' => 'زيادة التحويل',
            'label_en' => 'Increase conversion',
        ],
    ];
@endphp

<section class="bg-white">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12 lg:pt-16 pb-8 lg:pb-12">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">

            {{-- Text column --}}
            <div class="order-2 lg:order-1 {{ $isAr ? 'text-center lg:text-right' : 'text-center lg:text-left' }}">
                <p class="text-xs sm:text-sm font-black uppercase tracking-wider text-yellow-500 mb-2">
                    {{ $isAr ? 'إعلانات المنتجات' : 'Product Ads' }}
                </p>
                <h1 class="text-3xl sm:text-4xl lg:text-[42px] font-black text-orange-500 leading-tight text-pretty">
                    {{ $isAr ? 'إعلانات المنتجات' : 'Product Ads' }}
                </h1>

                {{-- Feature badges --}}
                <div class="mt-6 flex items-stretch justify-center lg:justify-start gap-1 sm:gap-2">
                    @foreach($features as $feature)
                        <div class="flex-1 max-w-[140px] flex flex-col items-center gap-2 px-2 sm:px-4 py-3
                                    {{ !$loop->first ? ($isAr ? 'border-e border-gray-100' : 'border-s border-gray-100') : '' }}">
                            <img src="{{ $feature['icon'] }}" alt="{{ $isAr ? $feature['label_ar'] : $feature['label_en'] }}"
                                 loading="eager" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                            <span class="text-xs sm:text-sm font-bold text-gray-800 text-center text-pretty leading-tight">
                                {{ $isAr ? $feature['label_ar'] : $feature['label_en'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <p class="mt-6 text-gray-600 font-medium text-base sm:text-lg max-w-[52ch] mx-auto lg:mx-0">
                    {{ $isAr
                        ? 'قم بتعزيز رؤية منتجاتك على مسار التحويل السفلي من خلال الإعلانات المستهدفة التي تصل إلى قاعدة عملاء أكبر وتمكّن النمو.'
                        : 'Amplify your products visibility on the lower funnel with targeted ads that reach a larger customer base and enable growth.' }}
                </p>

                <a href="https://admanager.noon.partners/en-ae?utm_source=ad_site&utm_medium=product" target="_blank" rel="noopener"
                   class="mt-6 inline-flex items-center justify-center bg-white hover:bg-gray-900 hover:text-white text-gray-900
                          border-2 border-gray-900 font-black text-sm sm:text-base px-8 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'ابدأ الآن' : 'Start now' }}
                </a>
            </div>

            {{-- Image column --}}
            <div class="order-1 lg:order-2">
                <img src="https://advertise.noon.com/images/productAdsMain_ar.png"
                     alt="{{ $isAr ? 'إعلانات المنتجات' : 'Product Ads' }}" loading="eager"
                     class="w-full max-w-[420px] sm:max-w-[520px] mx-auto h-auto">
            </div>
        </div>
    </div>
</section>
