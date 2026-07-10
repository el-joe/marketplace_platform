@php
    $isAr = session('locale', 'ar') === 'ar';
    $locale = $isAr ? 'ar' : 'en';
    $country = $country ?? 'ae';

    $solutions = [
        [
            'title_ar' => 'إدارة العرض',
            'title_en' => 'Managed Display Ads',
            'desc_ar' => 'احصل على دعم إضافي من متخصصي إعلانات نون للوصول إلى مواضع متميزة في الموقع والوصول إلى جماهير أوسع بدعم من متخصصينا',
            'desc_en' => 'Obtain additional support from noon ads specialists to access premium onsite placements and reach wider audiences with the support of our specialist.',
            'link_label_ar' => 'اتصل بنا لمعرفة المزيد',
            'link_label_en' => 'Contact us to learn more',
            'link' => route('portal.advertise.request', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/managedDisplayAds_ar.png',
        ],
        [
            'title_ar' => 'حلول خارج الموقع',
            'title_en' => 'Offsite Solutions',
            'desc_ar' => 'قم بجذب الملايين من المتسوقين من خلال حلول الإعلان الخارجي ذات التأثير العالي والتي تم وضعها بشكل استراتيجي في مواقع ذات حركة مرور كبيرة، وهي مصممة خصيصًا لجمهورك المستهدف. أو يمكنك أن تذهب مباشرة إلىيهم عبر تسويق تحت الخط (BTL) الشخصي والمستهدف.',
            'desc_en' => 'Attract millions of eyes with high impact OOH solutions strategically placed in high-traffic locations tailored to your ideal audience or go straight to their doorsteps with personalized and targeted BTL marketing.',
            'link_label_ar' => 'اتصل بنا لمعرفة المزيد',
            'link_label_en' => 'Contact us to learn more',
            'link' => route('portal.advertise.request', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/holdingImage2.png',
        ],
        [
            'title_ar' => 'فرص الرعاية',
            'title_en' => 'Sponsorship Opportunities',
            'desc_ar' => 'شارك في الأحداث والعروض المرتقبة للاستفادة من البيانات مما يمنح علامتك التجارية رؤية إضافية سواء عبر الإنترنت أو خارجه. كن شريكًا معنا وضع علامتك التجارية في طليعة الأحداث على مستوى النظام الأساسي/الفئة وعمليات التعاون المؤثرة.',
            'desc_en' => 'Take part in anticipated events and offerings to benefit from exponential traffic giving your brand extra visibility both online and offline. Partner with us and position your brand at the forefront of platform-wide/ category events and impactful collaborations.',
            'link_label_ar' => 'اتصل بنا لمعرفة المزيد',
            'link_label_en' => 'Contact us to learn more',
            'link' => route('portal.advertise.request', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/sponsored.png',
        ],
        [
            'title_ar' => 'إدارة العلاقات مع العملاء ووسائل التواصل الاجتماعي',
            'title_en' => 'CRM and Social Media',
            'desc_ar' => 'تفاعل مع جمهورك من خلال إشعارات الدفع المستهدفة وحملات وسائل التواصل الاجتماعي باستخدام شبكتنا الواسعة من المسوقين والمؤثرين.',
            'desc_en' => 'Engage your audience with targeted push notifications and social media campaigns leveraging our wide network of marketers and influencers.',
            'link_label_ar' => 'اتصل بنا لمعرفة المزيد',
            'link_label_en' => 'Contact us to learn more',
            'link' => route('portal.advertise.request', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/crm.png',
        ],
    ];
@endphp

<section class="bg-white">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 pb-12 lg:pb-16">
        <div class="grid md:grid-cols-2 gap-6 lg:gap-8">
            @foreach($solutions as $item)
                <div class="rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 lg:p-8
                            flex flex-col sm:flex-row items-center gap-6">
                    <div class="flex-1 {{ $isAr ? 'text-center sm:text-right' : 'text-center sm:text-left' }} order-2 sm:order-1">
                        <h2 class="text-lg lg:text-xl font-extrabold text-gray-900 mb-2 text-pretty">
                            {{ $isAr ? $item['title_ar'] : $item['title_en'] }}
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            {{ $isAr ? $item['desc_ar'] : $item['desc_en'] }}
                        </p>
                        <a href="{{ $item['link'] }}"
                           @if(empty($item['internal'])) target="_blank" rel="noopener" @endif
                           class="mt-4 inline-flex items-center gap-2 text-[#1677ff] font-bold text-sm hover:underline">
                            {{ $isAr ? $item['link_label_ar'] : $item['link_label_en'] }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" class="shrink-0 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                            </svg>
                        </a>
                    </div>
                    <div class="shrink-0 order-1 sm:order-2 w-2/3 sm:w-[160px] lg:w-[200px]">
                        <img src="{{ $item['image'] }}" alt="{{ $isAr ? $item['title_ar'] : $item['title_en'] }}"
                             loading="lazy" class="w-full h-auto object-contain">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
