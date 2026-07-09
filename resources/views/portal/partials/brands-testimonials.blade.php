@php
    $isAr = session('locale', 'ar') === 'ar';

    $testimonials = [
        [
            'logo' => 'https://advertise.noon.com/images/samsungLogo.png',
            'quote_ar' => "'لقد حققت شراكتنا مع نون خلال يوم الجمعة الصفراء نجاحًا ملحوظًا، حيث أظهرت نمونا وابتكارنا. نحن فخورون بأن نقدم حصريًا الجيل الثاني من جهاز العرض فري ستايل في نون، ونتوقع نتائج رائعة. معًا، نحن نقود الطريق في تقديم التكنولوجيا المتطورة لعملائنا الكرام.'",
            'quote_en' => "'Our partnership with noon during Yellow Friday has been a remarkable success, showcasing our growth and innovation. We are proud to exclusively offer our 2nd generation Freestyle Projector through noon, and we anticipate outstanding results. Together, we are leading the way in delivering cutting-edge technology to our valued customers.'",
            'name_ar' => 'احمد سلطان',
            'name_en' => 'Ahmed Sultan',
            'position_ar' => 'مدير تسويق المنتجات سامسونج',
            'position_en' => 'Product Marketing Manager, Samsung',
        ],
        [
            'logo' => 'https://advertise.noon.com/images/lorealLogo.png',
            'quote_ar' => "'منذ دمج لوحتي بيانات نون الجديدتين في عامي 2022 و 2023، تجاوزت قدراتنا في مشاركة البيانات، مما أدى إلى تعزيز عائد الاستثمار في جميع النفقات الإعلامية للحساب. لقد قامت حلول نون المبتكرة حقًا بتحسين شراكتنا ورفع قراراتنا الاستراتيجية إلى مستويات نجاح غير مسبوقة.'",
            'quote_en' => "'Since integrating noon's two new data dashboards in 2022 and 2023, our data sharing capabilities have soared, leading to enhanced ROI across all account media spends. noon's innovative solutions have truly optimized our partnership and elevated our strategic decisions to unprecedented levels of success'",
            'name_ar' => 'دانيا الحسين',
            'name_en' => 'Dania Elhussein',
            'position_ar' => "مدير التجارة الإلكترونية، قسم L'Oreal LDB",
            'position_en' => "E-Com Manager, L'Oreal LDB Division",
        ],
        [
            'logo' => 'https://advertise.noon.com/images/motorola.png',
            'quote_ar' => "'تعاونا مع نون لقيادة إعلاناتنا لإطلاق جهازنا الرئيسي. لم تمنح الحملة لنا فقط رؤية ملحوظة في الموقع وخارجه، بل ساعدت أيضًا في ترسيخ شراكتنا من خلال تعزيز نون كوجهة أساسية لمنتجات موتورولا. كانت النتائج واضحة في رفع حركة المرور، والترويج الشفوي، وأرقام المبيعات القياسية التي شهدناها حتى الآن.'",
            'quote_en' => "'We partnered with noon to lead our advertising efforts for the launch of our flagship device. The campaign not only gave us significant visibility offline & onsite but helped cement our partnership by reinforcing noon as a key destination for Motorola products. The results are evident in the traffic generated, word of mouth & record sales number that we have witnessed so far'",
            'name_ar' => 'فيناياك شينوي',
            'name_en' => 'Vinayak Shenoy',
            'position_ar' => 'مدير التسويق بشركة موتورولا للهواتف المحمولة',
            'position_en' => 'Marketing Director, Motorola Mobiles',
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
