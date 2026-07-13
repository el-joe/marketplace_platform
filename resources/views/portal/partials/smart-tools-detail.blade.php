@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<div class="flex flex-col gap-y-[48px] lg:gap-y-[64px] pt-[48px] lg:pt-[64px] pb-[48px] lg:pb-[64px]">

    {{-- noon Ads --}}
    <section id="ads" class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="rounded-2xl border-2 border-[#1c1c1c] p-6 lg:p-12 flex flex-col lg:grid lg:grid-cols-[1fr_auto] items-center gap-8 lg:gap-16">
            <div class="order-2 lg:order-1 w-full">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/logo-noon-ads.svg" alt="noon Ads"
                     class="h-7 w-auto my-2">
                <p class="text-[16px] font-medium text-gray-300">
                    {{ $isAr
                        ? 'إعلانات تشتغل بجهدك نفسه. إعلانات نون مصممة للبائعين الجادين في النمو والتوسّع.'
                        : 'Ads that work as hard as you do. noon Ads is built for sellers serious about growth and expansion.' }}
                </p>
                <p class="mt-2 text-[16px] font-medium text-gray-300">
                    {{ $isAr
                        ? 'وصل لعدد أكبر من العملاء، زوّد مبيعاتك، ووسّع شغلك بشكل أسرع.'
                        : 'Reach more customers, grow your sales, and scale your business faster.' }}
                </p>
                <p class="text-[#feee00] font-black text-xs uppercase tracking-wider mt-5">
                    {{ $isAr ? 'مع حلولنا متعددة الصيغ، ستحصل على إمكانية الوصول إلى:' : 'With our multi-format solutions, you get access to:' }}
                </p>
                <ul class="mt-2 text-[16px] font-medium text-gray-300 list-disc list-inside space-y-1">
                    <li>{{ $isAr ? 'إعلانات المنتجات - للترويج للمنتجات الفردية وتحسين التحويل' : 'Product ads - to promote individual products and improve conversion' }}</li>
                    <li>{{ $isAr ? 'إعلانات العرض - للترويج للحملات مثل الإطلاق الجديد، التصفية أو العروض الموسمية' : 'Display ads - to promote campaigns like new launches, clearance, or seasonal offers' }}</li>
                </ul>
                <div class="mt-6">
                    <a href="{{ route('portal.sellers', $country ?? 'ae') }}"
                       class="inline-flex items-center gap-2 text-[#feee00] font-bold text-sm">
                        {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" class="{{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
            <div class="order-1 lg:order-2 shrink-0">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-learn-more.png" alt="{{ $isAr ? 'محرك النمو الخاص بك' : 'Your growth engine' }}"
                     class="w-full max-w-[280px] md:max-w-[320px] mx-auto lg:mx-0">
            </div>
        </div>
    </section>

    {{-- Fee structure --}}
    <section id="fees" class="bg-[#1c1c1c] py-12 lg:py-16 w-full">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-[#feee00] font-black text-xl lg:text-2xl">{{ $isAr ? 'هيكل الرسوم' : 'Fee structure' }}</h2>
            <h3 class="text-white font-black text-lg lg:text-xl mt-1 mb-8 text-pretty">
                {{ $isAr
                    ? 'هيكل رسوم شفاف وبسيط بدون أي تكاليف خفية – مصمم للنمو المستدام.'
                    : 'A transparent, simple fee structure with no hidden costs - built for sustainable growth.' }}
            </h3>

            <div class="lg:grid lg:grid-cols-[1.5fr_2fr] lg:gap-10 xl:gap-14">
                <div class="relative aspect-[4/3] sm:aspect-[2/1] lg:aspect-auto lg:h-full rounded-2xl overflow-hidden">
                    <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-selling.jpg"
                         alt="{{ $isAr ? 'رجل يحمل صندوقًا في المستودع' : 'A man holding a box in the warehouse' }}"
                         class="absolute inset-0 w-full h-full object-cover">
                </div>
                <div class="pt-7 pb-2 lg:py-4">
                    <p class="text-[16px] font-bold mb-2">{{ $isAr ? 'تعتمد تكاليفك على فئة منتجك ونموذج التنفيذ.' : 'Your costs depend on your product category and fulfilment model.' }}</p>
                    <p class="text-[16px] font-medium text-gray-300">{{ $isAr ? 'الإعدادات شفافة، مرنة، ومصممة لتنمو معك.' : 'Fees are transparent, flexible, and designed to grow with you.' }}</p>
                    <div class="mt-8">
                        <a href="https://support.noon.partners/portal/{{ $isAr ? 'ar' : 'en' }}/kb/search/{{ $isAr ? 'رسوم' : 'fees' }}" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center w-full sm:w-auto bg-[#feee00] hover:bg-[#e5d600] text-black
                                  font-black text-sm px-6 py-3 rounded-full transition-colors">
                            {{ $isAr ? 'اعرف أكثر' : 'Learn more' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Transparent fees cards --}}
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <h3 class="text-2xl sm:text-[26px] font-bold leading-tight mb-6 lg:mb-8">{{ $isAr ? 'الرسوم الشفافة' : 'Transparent Fees' }}</h3>

        <div class="grid gap-4 md:grid-cols-3 md:gap-4 lg:gap-6"
             x-data="{ shown: false }"
             x-init="
                 const observer = new IntersectionObserver((entries) => {
                     shown = entries[0].isIntersecting;
                 }, { threshold: 0.1 });
                 observer.observe($el);
             ">
            @php
                $feeCards = [
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-transparent-fees-01.jpg',
                        'title_ar' => 'رسوم الاحالة', 'title_en' => 'Referral Fees',
                        'sub_ar' => 'نحن نربح فقط عندما تحقق انت ربحا', 'sub_en' => 'We only earn when you do.',
                        'desc_ar' => 'تطبق رسوم الاحالة علي كل عملية بيع - وفقا لفئة منتجك', 'desc_en' => 'A referral fee applies per sale — based on your product category.',
                        'links' => [
                            ['label_ar' => 'اعرف أكثر', 'label_en' => 'Learn more', 'url' => 'https://support.noon.partners/portal/' . ($isAr ? 'ar' : 'en') . '/kb/articles/referral-fees-in-noon-mena'],
                        ],
                    ],
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-transparent-fees-02.jpg',
                        'title_ar' => 'رسوم التنفيذ', 'title_en' => 'Fulfilment Fees',
                        'sub_ar' => 'شحنك، خيارك', 'sub_en' => 'Your shipping, your call.',
                        'desc_ar' => 'تدفع مقابل ما تستخدمه فقط، بدون أي رسوم خفية – الرسوم تختلف حسب طريقة التنفيذ التي تختارها.', 'desc_en' => "You pay for what you use, no hidden charges - fee varies based on how you choose to fulfil.",
                        'links' => [
                            ['label_ar' => 'تعرف على رسوم FBN', 'label_en' => 'Learn About FBN Fees', 'url' => 'https://support.noon.partners/portal/' . ($isAr ? 'ar' : 'en') . '/kb/articles/fbn-fees'],
                            ['label_ar' => 'تعرف على رسوم FBP', 'label_en' => 'Learn About FBP Fees', 'url' => 'https://support.noon.partners/portal/' . ($isAr ? 'ar' : 'en') . '/kb/articles/fbp-fees'],
                        ],
                    ],
                    [
                        'image' => 'https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-transparent-fees-03.jpg',
                        'title_ar' => 'مصاريف اخري', 'title_en' => 'Other Costs',
                        'sub_ar' => 'تطبق فقط اذا اخترت الاستفادة منها', 'sub_en' => 'Only if you opt in.',
                        'desc_ar' => 'تُضاف رسوم إضافية لأمور مثل التخزين طويل الأمد وغيرها – وتُطبق عند اختيارك استخدام هذه الخدمات.', 'desc_en' => 'Extra fees apply for things like long-term storage etc. — which are applied when you choose to avail these services.',
                        'links' => [
                            ['label_ar' => 'اعرف أكثر', 'label_en' => 'Learn more', 'url' => 'https://advertise.noon.com/' . ($isAr ? 'ar' : 'en') . '/'],
                        ],
                    ],
                ];
            @endphp

            @foreach($feeCards as $card)
                <div class="relative rounded-2xl overflow-hidden h-full min-h-[280px] md:min-h-[300px] transition-all duration-700 ease-out"
                     :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                     style="transition-delay: {{ $loop->index * 200 }}ms;">
                    <img src="{{ $card['image'] }}" alt="{{ $isAr ? $card['title_ar'] : $card['title_en'] }}"
                         class="absolute inset-0 w-full h-full object-cover object-bottom">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-black/10"></div>
                    <div class="relative h-full flex flex-col justify-end px-5 pb-5 pt-8 lg:px-6 lg:pb-6">
                        <h4 class="text-white font-black text-lg lg:text-xl text-pretty">{{ $isAr ? $card['title_ar'] : $card['title_en'] }}</h4>
                        <p class="mt-1 text-white font-semibold text-[15px] whitespace-nowrap">{{ $isAr ? $card['sub_ar'] : $card['sub_en'] }}</p>
                        <p class="mt-1.5 text-gray-300 text-sm font-medium text-pretty">{{ $isAr ? $card['desc_ar'] : $card['desc_en'] }}</p>
                        <div class="flex flex-col gap-1 mt-3">
                            @foreach($card['links'] as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-[#feee00] font-bold text-sm">
                                    {{ $isAr ? $link['label_ar'] : $link['label_en'] }}
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" class="{{ $isAr ? '-scale-x-100' : '' }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Payouts --}}
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="rounded-2xl border-2 border-[#1c1c1c] p-6 lg:p-12 grid grid-cols-1 md:grid-cols-[1fr_auto] items-center gap-6 lg:gap-16">
            <div>
                <h2 class="text-2xl sm:text-[26px] font-bold leading-tight mb-4 lg:mb-6">
                    {{ $isAr ? 'استلام' : 'Receiving' }} <span class="text-[#feee00]">{{ $isAr ? 'الأرباح' : 'Payouts' }}</span>
                </h2>
                <h3 class="text-[16px] font-bold leading-tight">{{ $isAr ? 'كل اللي عليك تربط حسابك البنكي.' : 'All you have to do is link your bank account.' }}</h3>
                <p class="text-[14px] font-medium mt-3 text-gray-300">
                    {{ $isAr
                        ? 'أرباحك توصل مباشرة بعد خصم الرسوم — بكل بساطة وسرعة، من غير تعقيد.'
                        : 'Your earnings land directly after fees are deducted - simple, fast, no hassle.' }}
                </p>
            </div>
            <div class="mt-2 md:mt-0">
                <a href="https://support.noon.partners/portal/{{ $isAr ? 'ar' : 'en' }}/kb/articles/how-do-i-receive-my-payouts" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center w-full sm:w-auto bg-[#feee00] hover:bg-[#e5d600] text-black
                          font-black text-sm px-6 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'اكتشف كيف' : 'Discover how' }}
                </a>
            </div>
        </div>
    </section>

    {{-- Insights --}}
    <section id="insights" class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <section class="rounded-2xl border-2 border-[#1c1c1c] overflow-hidden md:grid md:grid-cols-[1.5fr_2fr] lg:gap-x-6">
            <div class="relative aspect-[4/3] md:aspect-auto">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-paid.jpg"
                     alt="{{ $isAr ? 'شاحنة نون في الشارع' : 'A noon van in the street' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-8 px-6 pb-10 md:px-8">
                <h2 class="text-[#feee00] font-black text-xl lg:text-2xl">{{ $isAr ? 'التحليلات والرؤى' : 'Analytics & insights' }}</h2>
                <h3 class="text-white font-black text-lg lg:text-xl mt-3 mb-3 leading-tight">
                    {{ $isAr
                        ? 'تابع سلوك عملائك وأداء مبيعاتك وحسّن عملك باستخدام تحليلات قوية عبر:'
                        : 'Track your customer behaviour and sales performance, and improve your business with powerful analytics across:' }}
                </h3>
                <ul class="space-y-2">
                    @foreach([
                        [$isAr ? 'مسار التحويل' : 'Conversion funnel'],
                        [$isAr ? 'أداء المبيعات' : 'Sales performance'],
                        [$isAr ? 'التقارير المالية' : 'Financial reports'],
                        [$isAr ? 'مراقبة صحة الحساب' : 'Account health monitoring'],
                        [$isAr ? 'إدارة السمعة' : 'Reputation management'],
                    ] as $item)
                        <li class="flex items-center gap-3 text-gray-300 font-medium text-[15px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                            <span>{{ $item[0] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-[16px] font-semibold mt-4">
                    {{ $isAr
                        ? 'يمكنك الوصول إلى مجموعة كاملة من التقارير ولوحات المعلومات في أي وقت عبر مركز البائعين.'
                        : 'Access a full suite of reports and dashboards any time through the Seller Hub.' }}
                </p>
            </div>
        </section>

        <div class="mt-6 rounded-2xl border-2 border-[#1c1c1c] p-6 lg:p-8">
            <h2 class="text-xl font-bold leading-tight mb-4">{{ $isAr ? 'فانتج للتحليلات – مركز القيادة لنموك' : 'Vantage Analytics - your growth command centre' }}</h2>
            <p class="text-[16px] font-medium text-gray-300">
                {{ $isAr
                    ? 'فانتج هو منصة نون المتقدمة للتحليلات والنمو، مصممة للبائعين الذين يرغبون في التوسع بذكاء. تخيّله كمنصتك المميزة للذكاء والتحليلات، مع مجموعة من الرؤى المجانية المتاحة لجميع البائعين.'
                    : 'Vantage is noon\'s advanced analytics and growth platform, built for sellers who want to scale smart. Think of it as your premium intelligence and analytics platform, with a set of free insights available to all sellers.' }}
            </p>
        </div>
    </section>

</div>
