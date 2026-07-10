@php
    $isAr = session('locale', 'ar') === 'ar';
    $locale = $isAr ? 'ar' : 'en';
    $country = $country ?? 'ae';

    $solutions = [
        [
            'title_ar' => 'إعلانات المنتجات',
            'title_en' => 'Product Ads',
            'desc_ar' => 'قم بتعزيز رؤية منتجاتك في الأسفل باستخدام الإعلانات المستهدفة التي تصل إلى قاعدة عملاء أكبر وتمكّن النمو. سيعمل هذا المنتج على زيادة ظهور منتجاتك بشكل كبير، والوصول إلى العملاء، وإمكانات التحويل.',
            'desc_en' => "Amplify your products visibility on the lower funnel with targeted ads that reach a larger customer base and enable growth. This feature will significantly increase product visibility, customer reach and conversion potential.",
            'link_label_ar' => 'اعرف أكثر',
            'link_label_en' => 'Learn More',
            'link' => route('portal.advertise.product', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/productAds_ar.png',
        ],
        [
            'title_ar' => 'إعلانات العرض',
            'title_en' => 'Display Ads',
            'desc_ar' => 'قم بتسليط الضوء على الحملات المحددة التي ترغب في الترويج لها، بما في ذلك عمليات الإطلاق الجديدة أو منتجات التصفية أو العروض الموسمية، لاستهداف الجماهير التي من المحتمل أن تكون مهتمة.',
            'desc_en' => 'Highlight specific campaigns you want to push, including new launches, clearance items, or seasonal offerings to target audiences likely to be interested.',
            'link_label_ar' => 'اعرف أكثر',
            'link_label_en' => 'Learn More',
            'link' => route('portal.advertise.display', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/productPhone_ar.png',
        ],
        [
            'title_ar' => 'إدارة العلاقات مع العملاء ووسائل التواصل الاجتماعي',
            'title_en' => 'CRM and Social Media',
            'desc_ar' => 'تواصل مع جمهورك من خلال إشعارات الدفع المستهدفة وحملات وسائل التواصل الاجتماعي باستخدام شبكتنا الواسعة من المسوقين والمؤثرين.',
            'desc_en' => 'Engage your audience with targeted push notifications and social media campaigns leveraging our wide network of marketers and influencers.',
            'link_label_ar' => 'اتصل بنا لمعرفة المزيد',
            'link_label_en' => 'Contact us to learn more',
            'link' => route('portal.advertise.request', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/crm.png',
        ],
        [
            'title_ar' => 'إعلانات العلامة التجارية',
            'title_en' => 'Brand Ads',
            'desc_ar' => 'قم بالترويج لمنتجاتك وعلامتك التجارية بطريقة بصرية جذابة وبارزة داخل صفحات نون للتصفح، البحث، وصفحات تفاصيل المنتج ذات الصلة. بإمكانك أيضا عرض منتجات متعددة داخل إعلان واحد، وإبراز مجموعة واسعة من منتجاتك وجذب جمهور أكبر.',
            'desc_en' => "Promote your products and brand in a visually appealing and prominent way within noon's browse, search, and relevant product detail pages. Display multiple products within one ad, showcasing a wider range of offerings and potentially attracting a broader audience.",
            'link_label_ar' => 'اعرف أكثر',
            'link_label_en' => 'Learn More',
            'link' => route('portal.advertise.brands', $country),
            'internal' => true,
            'image' => 'https://advertise.noon.com/images/brandproduct_ar.png',
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
