@php $isAr = session('locale', 'ar') === 'ar'; @endphp

@push('head')
<style>
    @keyframes blink-four-times {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    .animate-blink-4 {
        animation: blink-four-times 0.6s ease-in-out 4 forwards;
    }
</style>
@endpush

<section id="listings" class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl sm:text-3xl font-black text-white mb-6 lg:mb-8">
        {!! $isAr ? 'جهز قوائم منتجاتك' : 'Get Your <span class="text-[#feee00] animate-blink-4 inline-block">Listings Ready</span>' !!}
    </h2>

    <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] lg:grid lg:grid-cols-[1.5fr_2fr]">
        <div class="relative aspect-[4/3] lg:aspect-auto">
            <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-listings-ready.jpg"
                 alt="{{ $isAr ? 'مندوب توصيل نون' : 'A noon delivery agent scanning a package' }}"
                 class="absolute inset-0 w-full h-full object-cover">
        </div>
        <div class="pt-8 px-6 pb-8 md:pt-10 md:px-10 md:pb-10">
            <h3 class="text-white font-black text-xl lg:text-2xl text-pretty leading-tight">
                {{ $isAr
                    ? 'إدراج منتجاتك على نون سهل جداً مع أداة "كتالوجي" القوية.'
                    : "Listing your products on noon is easy with our powerful 'My catalog' tool." }}
            </h3>
            <p class="mt-4 text-gray-300 text-[14px] font-medium">
                {{ $isAr
                    ? 'إثراء الكتالوج الخاص بك يعزز اكتشاف منتجاتك ويزيد من نسبة التحويل.'
                    : 'Enriching your catalog boosts discovery and conversion of your products.' }}
            </p>

            <h4 class="text-[#feee00] font-black text-sm mt-6 mb-3">{{ $isAr ? 'خيارات التحميل:' : 'Upload options:' }}</h4>
            <ul class="space-y-3 mb-6">
                @foreach([
                    $isAr ? 'منتج واحد — للكتالوجات المنسقة' : 'Single SKU — for curated catalogues',
                    $isAr ? 'التحميل الجماعي — للكتالوجات الكبيرة' : 'Bulk upload — for larger catalogues',
                    $isAr ? 'API — للأنظمة المؤتمتة' : 'API — for automated systems',
                ] as $item)
                    <li class="flex items-start gap-3 text-gray-200 text-[15px] font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="text-gray-400 text-[14px] font-medium mb-8">
                {{ $isAr
                    ? 'يمكنك أيضًا تحديد الأسعار وتحديث المخزون مباشرة من كتالوجي — كل ذلك في مكان واحد'
                    : 'You can also set pricing and update stock directly from My Catalogue — all in one place' }}
            </p>

            <a target="_blank" rel="noopener" title="{{ $isAr ? 'يفتح في نافذة جديدة' : 'Opens in a new window' }}"
               href="https://www.youtube.com/@noonsellerlab7442/videos"
               class="inline-block border border-[#feee00] text-[#feee00] hover:bg-[#feee00] hover:text-black text-sm font-bold px-6 py-2 rounded-full transition-colors">
                {{ $isAr ? 'شاهد فيديوهات الشرح' : 'Watch The Tutorials' }}
            </a>
        </div>
    </div>

    <h3 class="text-white font-black text-lg lg:text-xl mt-8 lg:mt-10 mb-4">
        {{ $isAr ? 'تفاصيل العلامة التجارية' : 'Brand details' }}
    </h3>

    <div class="grid gap-6 lg:gap-8 lg:grid-cols-[2fr_1fr] lg:items-start">
        {{-- Brand ownership --}}
        <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] lg:grid lg:grid-cols-[1.5fr_2fr] h-full">
            <div class="relative aspect-[4/3] lg:aspect-auto">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-brand-details.jpg"
                     alt="{{ $isAr ? 'ألعاب على رف' : 'Toys on top of a cabinet' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-8 px-6 pb-8 md:pt-10 md:px-10 md:pb-10">
                <p class="text-[#feee00] font-black text-xs uppercase tracking-wider">
                    {{ $isAr ? 'تملك علامة تجارية أو تمثل واحدة؟' : 'Own a brand or represent one?' }}
                </p>
                <h4 class="text-white font-black text-lg mt-1 mb-6 text-pretty">
                    {{ $isAr
                        ? 'يمنحك سجل العلامات التجارية في نون الأدوات لحماية حقوق الملكية الفكرية وبناء الثقة مع العملاء.'
                        : 'noon\'s brand registry gives you the tools to protect your intellectual property rights and build trust with customers.' }}
                </h4>

                <h5 class="text-[#feee00] font-black text-sm mb-2">{{ $isAr ? 'وكيل معتمد؟' : 'Authorised agent?' }}</h5>
                <p class="text-gray-300 text-[14px] font-medium mb-2">{{ $isAr ? 'أرسل لنا المستندات الصحيحة للبدء:' : 'Send us the right documents to get started:' }}</p>
                <ul class="space-y-2 mb-6">
                    @foreach([
                        $isAr ? 'خطاب تفويض العلامة التجارية' : 'Brand authorisation letter',
                        $isAr ? 'ترخيص المصنع' : 'Manufacturer licence',
                        $isAr ? 'مستندات تسجيل المنتج أو العلامة التجارية' : 'Product or brand registration documents',
                    ] as $doc)
                        <li class="flex items-start gap-3 text-gray-200 text-[14px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                            <span>{{ $doc }}</span>
                        </li>
                    @endforeach
                </ul>

                <h5 class="text-[#feee00] font-black text-sm mb-2">{{ $isAr ? 'موزع؟' : 'Distributor?' }}</h5>
                <p class="text-gray-300 text-[14px] font-medium mb-6">
                    {{ $isAr ? 'انتقل مباشرة إلى معلومات المنتج والفئة.' : 'Skip ahead to product and category information.' }}
                </p>

                <h5 class="text-[#feee00] font-black text-sm mb-2">{{ $isAr ? 'قدمت طلبك؟' : 'Already applied?' }}</h5>
                <p class="text-gray-300 text-[14px] font-medium mb-8">
                    {{ $isAr
                        ? 'خلّك مستعد — انتظر حتى يتم الانتهاء من تسجيل علامتك التجارية قبل البدء في إضافة المنتجات لضمان عرضها تحت العلامة التجارية الصحيحة.'
                        : "Sit tight — wait until your brand registration is complete before adding products, to make sure they're listed under the right brand." }}
                </p>

                <a href="{{ route('portal.faq') }}"
                   class="inline-block bg-[#feee00] hover:bg-[#e5d600] text-black font-black text-sm px-6 py-2.5 rounded-full transition-colors">
                    {{ $isAr ? 'اقرأ المزيد عن تسجيل العلامة التجارية' : 'Read more about brand registration' }}
                </a>
            </div>
        </div>

        {{-- Category info --}}
        <div class="rounded-2xl overflow-hidden border-2 border-[#1c1c1c] h-full">
            <div class="aspect-[4/3]">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-category-info.jpg"
                     alt="{{ $isAr ? 'موظف نون في المستودع' : 'A noon employee walking through the warehouse' }}"
                     class="w-full h-full object-cover">
            </div>
            <div class="pt-7 px-6 pb-8">
                <h4 class="text-white font-black text-lg">{{ $isAr ? 'معلومات المنتج والفئة' : 'Product and category information' }}</h4>
                <p class="mt-4 text-gray-300 text-[14px] font-medium">
                    {{ $isAr
                        ? 'حضر معلومات المنتج والفئة قبل ما تبدأ في إضافة المنتجات — سيوفر لك الوقت ويجنبك الأخطاء.'
                        : "Prepare your product and category information before you start adding products — it'll save you time and help you avoid mistakes." }}
                </p>
                <p class="text-[#feee00] font-black text-xs uppercase tracking-wider mt-5">{{ $isAr ? 'تنبيه:' : 'Heads up:' }}</p>
                <p class="text-gray-300 text-[14px] font-medium">
                    {{ $isAr
                        ? 'بعض الفئات تتطلب الموافقة أولًا، بما في ذلك التحقق من المستندات وأداء البائع.'
                        : "Some categories require approval first, including document checks and seller performance review." }}
                </p>
                <a href="{{ route('portal.faq') }}"
                   class="inline-block mt-8 bg-[#feee00] hover:bg-[#e5d600] text-black font-black text-sm px-6 py-2.5 rounded-full transition-colors">
                    {{ $isAr ? 'اقرأ الدليل' : 'Read the guide' }}
                </a>
            </div>
        </div>
    </div>
</section>
