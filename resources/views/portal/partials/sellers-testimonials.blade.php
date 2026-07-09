@php
    $isAr = session('locale', 'ar') === 'ar';

    $testimonials = [
        [
            'logo' => 'https://advertise.noon.com/images/unicharm.png',
            'quote_ar' => "'لعبت إعلانات نون دوراً فعالاً في تعزيز أنشطتنا مع نون. استثماراتنا كان ولا تزال، تأثيرها مادياً وإيجابياً في تنمية مبيعاتنا وتحقيق أهدافنا التجارية المتبادلة. لا زلنا نرى نون وجهة رئيسية في عالم التجارة الإلكترونية في المنطقة. وهذا ما يؤكده المستوى العالي من الدعم والتفاني الذي يقدمه فريق نون بأكمله.'",
            'quote_en' => "'Noon Ads played an instrumental role in enhancing our activities with Noon. Our investments have had, and continue to have, a material and positive impact to grow our sales and achieve our mutual business goals. We continue to see Noon as a key player in the e-commerce industry in the region. This is underscored by the high degree of support and dedication by the entire Noon team.'",
            'name_ar' => 'تيام كاتبي',
            'name_en' => 'Tayyam Katbe',
            'position_ar' => 'مدير تنفيذي أول، شركة يونيتشارم جلف لصناعات الصحة',
            'position_en' => 'Senior Executive, Unicharm Gulf Hygienic Industries',
        ],
        [
            'logo' => 'https://advertise.noon.com/images/funMoment.png',
            'quote_ar' => "'لقد عقدنا شراكة مع نون لقيادة جهودنا الإعلانية لإطلاق منتجاتنا. لم تمنحنا الحملة رؤية كبيرة في الموقع وخارجي فحسب، بل ساعدت في تعزيز شراكتنا من خلال تعزيز نون وجهة رئيسية لمنتجات لحظة فرح. وتتجلى النتائج في عدد الزيارات والكلمات الشفوية وأرقام المبيعات القياسية التي شهدناها حتى الآن.'",
            'quote_en' => "'We partnered with noon to lead our advertising efforts for the launch of our products. The campaign not only gave us significant visibility in offline & onsite but helped cement our partnership by reinforcing noon as a key destination for funmoment products. The results are evident in the traffic generated, word of mouth & record sales number that we have witnessed so far.'",
            'name_ar' => 'سالم حبتور',
            'name_en' => 'Salem Habtoor',
            'position_ar' => 'المدير المباشر، لحظة متعة',
            'position_en' => 'Direct manager, Fun moment',
        ],
        [
            'logo' => 'https://advertise.noon.com/images/abdul1.png',
            'quote_ar' => "'لقد كانت تجربتنا في التعامل مع نون في خدمات الإعلان على موقعهم الإلكتروني استثنائية. منذ اللحظة التي تعاملنا فيها مع فريقهم، لاحظنا زيادة كبيرة في رؤية علامتنا التجارية وأداء المبيعات. إن التعامل مع نون لم يؤدِ إلى تحسين مبيعاتنا فحسب، بل أدى أيضاً إلى خلق حضور مستدام للعلامة التجارية وتردد صداه مع العملاء.'",
            'quote_en' => "'Our experience collaborating with Noon on their website advertising services has been exceptional. From the moment we engaged with their team, we noticed a significant boost in our brand visibility and sales performance. Collaborating with Noon has not only improved our sales but also created a sustainable brand presence that resonates with customers.'",
            'name_ar' => 'حسن غطاس',
            'name_en' => 'Hassan Ghattas',
            'position_ar' => 'مدير التسويق عبد الواحد',
            'position_en' => 'Marketing Manager, Abdul Wahed',
        ],
    ];
@endphp

<section class="bg-gray-50 py-12 lg:py-16">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-yellow-500 font-black text-xs sm:text-sm uppercase tracking-wider mb-8 lg:mb-10">
            {{ $isAr ? 'استمع إلى آراء عملائنا' : 'Hear from our satisfied customers' }}
        </p>

        <div x-data="{ active: 0, count: {{ count($testimonials) }} }" class="relative">
            <div class="overflow-hidden">
                <div class="flex transition-transform duration-500 ease-out"
                     :style="`transform: translateX(${ {{ $isAr ? '1' : '-1' }} * active * 100}%)`">
                    @foreach($testimonials as $t)
                        <div class="w-full shrink-0 px-2 sm:px-10 text-center">
                            <img src="{{ $t['logo'] }}" alt="{{ $isAr ? $t['name_ar'] : $t['name_en'] }}"
                                 loading="lazy" class="h-8 mx-auto mb-6 object-contain">
                            <p class="text-gray-700 text-base sm:text-lg max-w-[70ch] mx-auto leading-relaxed text-pretty">
                                {{ $isAr ? $t['quote_ar'] : $t['quote_en'] }}
                            </p>
                            <p class="mt-6 font-extrabold text-gray-900">{{ $isAr ? $t['name_ar'] : $t['name_en'] }}</p>
                            <p class="text-sm text-gray-500">{{ $isAr ? $t['position_ar'] : $t['position_en'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Prev / next arrows --}}
            <button type="button" @click="active = (active - 1 + count) % count"
                    class="hidden sm:flex absolute {{ $isAr ? '-right-2 lg:-right-6' : '-left-2 lg:-left-6' }} top-1/2 -translate-y-1/2
                           w-9 h-9 items-center justify-center rounded-full bg-white shadow hover:bg-gray-100 text-gray-600"
                    aria-label="{{ $isAr ? 'السابق' : 'Previous' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.75 8.25 7 12m0 0 3.75 3.75M7 12h10" />
                </svg>
            </button>
            <button type="button" @click="active = (active + 1) % count"
                    class="hidden sm:flex absolute {{ $isAr ? '-left-2 lg:-left-6' : '-right-2 lg:-right-6' }} top-1/2 -translate-y-1/2
                           w-9 h-9 items-center justify-center rounded-full bg-white shadow hover:bg-gray-100 text-gray-600"
                    aria-label="{{ $isAr ? 'التالي' : 'Next' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.25 8.25 17 12m0 0-3.75 3.75M17 12H7" />
                </svg>
            </button>

            {{-- Dots --}}
            <div class="flex justify-center gap-2 mt-8">
                @foreach($testimonials as $i => $t)
                    <button type="button" @click="active = {{ $i }}"
                            :class="active === {{ $i }} ? 'w-6 bg-gray-900' : 'w-4 bg-gray-300'"
                            class="h-1.5 rounded-full transition-all"
                            aria-label="{{ $isAr ? 'الشريحة' : 'Slide' }} {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </div>
</section>
