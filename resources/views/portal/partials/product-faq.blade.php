@php
    $isAr = session('locale', 'ar') === 'ar';

    $faqs = [
        [
            'q_ar' => 'ما هي إعلانات المنتجات؟',
            'q_en' => 'What are Product Ads?',
            'a_ar' => 'تعد إعلانات المنتجات جزءًا من مجموعتنا الإعلانية ذاتية الخدمة. وهي تعتمد على نية العميل في العثور على المنتجات ذات الصلة التي تظهر عادةً عندما يبحث المستخدمون عن منتجات أو فئات منتجات معينة على نون. تعمل على ميزة عروض أسعار الكلمات الرئيسية/الفئة حيث يقوم المعلنون بتقديم عروض أسعار على الكلمات الرئيسية والفئات ويتم عرض إعلاناتهم في حالة فوزهم بالعرض.',
            'a_en' => 'Product Ads are self-serve ads that are based on customer intent and appear when users search for specific products or product categories on noon.com. They work on a keyword/category bidding feature where advertisers bid on keywords and categories and their ads are displayed if they win the bid.',
        ],
        [
            'q_ar' => 'من يمكنه تشغيل حملات إعلانات المنتجات؟',
            'q_en' => 'Who can run Product Ads campaigns?',
            'a_ar' => 'يمكن لأي شريك، بما في ذلك العلامة التجارية أو الموزع أو البائع المسجل على منصة شركاء نون الخاصة بنا، من الوصول لعرض هذه الإعلانات.',
            'a_en' => 'Any partner, including a brand, distributor or seller who is registered on our noon Partners platform will have access to run these ads.',
        ],
        [
            'q_ar' => 'كيف يمكنني البدء في استخدام إعلانات المنتجات؟',
            'q_en' => 'How can I get started with Product Ads?',
            'a_ar' => "إذا كان لديك بالفعل حق الوصول إلى لوحة تحكم شركاء نون، فما عليك سوى الضغط على 'إنشاء حملة' واختيار إعلانات المنتج كنوع إعلانك وملء الحقول المطلوبة.",
            'a_en' => 'Click on the "Start Now" button at the top right of the page and register as a noon Partner to have access to our ads manager. If you are already a noon Partner, login and you will be automatically directed to the ads manager where you can select your campaign type as "Product Ads".',
        ],
        [
            'q_ar' => 'ما هو نموذج التسعير لإعلانات المنتجات وما هي تكلفتها؟',
            'q_en' => 'What is the pricing model for Product Ads and how much do they cost?',
            'a_ar' => 'يمكنك تقديم عرض سعر لكل كلمة رئيسية و/أو فئة/فئة فرعية تختار استهدافها عبر حملة إعلانات المنتجات الخاصة بك. إذا كنت تقوم بإعداد حملتك الأولى لإعلانات المنتجات، فاستفد من عرض السعر المقترح الموضح ضمن الكلمات الرئيسية/الفئات المحددة.',
            'a_en' => 'Product Ads use a CPC (cost per click) model where when a customer clicks on your ad, you will be charged the bid amount that is set for the targets in your campaign.',
        ],
        [
            'q_ar' => 'هل يمكنني تعديل حملة مباشرة؟',
            'q_en' => 'Can I edit a live campaign?',
            'a_ar' => "نعم، يمكنك تعديل حملة مباشرة. في لوحة تحكم شركاء نون، قم بالتمرير للأسفل إلى قائمة حملاتك. حدد الحملة التي تريد تعديلها واضغط على 'تعديل'. بمجرد الرضا، اضغط فوق 'حفظ وإطلاق' لتنشيط الحملة مرة أخرى.",
            'a_en' => 'Yes, live campaigns can be edited directly through the ads manager. Scroll to the campaigns list and select the one you want to edit, the edit option should appear. Do not forget to click on "Save and Launch" to get the campaign running again.',
        ],
        [
            'q_ar' => 'أين ستكون إعلانات المنتجات مرئية؟',
            'q_en' => 'Where will Product Ads be visible?',
            'a_ar' => 'تظهر إعلانات المنتجات بين نتائج البحث في صفحات قائمة المنتجات وفي صفحات وصف المنتج.',
            'a_en' => 'Product Ads will be visible in the noon app and on web in between search results for the product listing pages and product description pages.',
        ],
        [
            'q_ar' => 'ما هو نوع تقارير الأداء المتاحة؟',
            'q_en' => 'What type of performance reporting is available?',
            'a_ar' => 'تضع لوحة التحكم ذاتية الخدمة جميع المقاييس الرئيسية في متناول يدك، مما يسمح لك بمراقبة الأداء في الوقت الفعلي. تعمق أكثر مع مقتطفات البيانات الدقيقة. قم بتنزيل البيانات وتحليلها في أداتك المفضلة للحصول على رؤى متقدمة وتحسين الحملة.',
            'a_en' => 'Our self-service dashboard puts all major metrics at your fingertips, allowing you to monitor performance in real-time. Dive deeper with granular data extracts. Download and analyze data in your preferred tool for advanced insights and campaign optimization.',
        ],
        [
            'q_ar' => 'كيف يمكنني الوصول إلى لوحة التحكّم؟',
            'q_en' => 'How can I get access to the dashboard?',
            'a_ar' => 'يمكن لأي شخص الوصول إلى منصة شريك نون والتسجيل في لوحة تحكم ذاتية الخدمة.',
            'a_en' => 'Anyone can access the noon Partner platform and register for a self-serve dashboard.',
        ],
    ];
@endphp

<section class="bg-white pb-16 lg:pb-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-gray-900 text-center mb-8 lg:mb-10">
            {{ $isAr ? 'الأسئلة الشائعة' : 'Frequently asked questions' }}
        </h2>

        <div class="divide-y divide-gray-100 border-y border-gray-100" x-data="{ openIndex: 0 }">
            @foreach($faqs as $idx => $faq)
                <div class="py-5">
                    <button @click="openIndex = openIndex === {{ $idx }} ? null : {{ $idx }}"
                            class="w-full flex items-center justify-between gap-4 {{ $isAr ? 'text-right' : 'text-left' }} focus:outline-none group">
                        <span class="font-bold text-gray-900 group-hover:text-gray-600 transition-colors text-pretty">
                            {{ $isAr ? $faq['q_ar'] : $faq['q_en'] }}
                        </span>
                        <span class="shrink-0 w-5 h-5 flex items-center justify-center">
                            <svg x-show="openIndex === {{ $idx }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-gray-700">
                                <path stroke-linecap="round" d="M5 12h14" />
                            </svg>
                            <svg x-show="openIndex !== {{ $idx }}" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-gray-700">
                                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                    </button>

                    <div x-show="openIndex === {{ $idx }}" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-3 {{ $isAr ? 'text-right' : 'text-left' }}">
                        <p class="text-gray-600 leading-relaxed text-pretty">
                            {{ $isAr ? $faq['a_ar'] : $faq['a_en'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
