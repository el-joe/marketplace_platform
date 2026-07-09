@php
    $isAr = session('locale', 'ar') === 'ar';

    $faqs = [
        [
            'q_ar' => 'ما هي إعلانات العرض؟',
            'q_en' => 'What are Display Ads?',
            'a_ar' => 'إعلانات العرض هي بانرز بتصميم جرافيك ثابتة يتم عرضها على موقع وتطبيق نون.',
            'a_en' => 'Display Ads are static graphic banners displayed on the noon website and app.',
        ],
        [
            'q_ar' => 'ما هو نموذج التسعير وكم تكلفته؟',
            'q_en' => 'What is the pricing model and how much do Display Ads cost?',
            'a_ar' => 'تتبع إعلانات العرض نموذج عروض الأسعار المستند إلى التكلفة لكل ألف ظهور (CPM). أنت تقدم عرضًا مقابل كل 1000 مشاهدة. ستكون الحملة الإعلانية ذات العرض الفائز هي الحملة التي سيتم عرضها على نون.',
            'a_en' => 'Display ads follow a CPM model (Cost-per-mille or Cost-per-thousand) based bidding. You bid for every 1000 views. The ad campaign with the winning bid will be the one displayed on noon.',
        ],
        [
            'q_ar' => 'كيف تختلف إعلانات العرض عن إعلانات العرض المُدارة؟ ما هي تكلفة الإعلانات المُدارة من نون؟',
            'q_en' => "How are Display Ads different from managed Display Ads? How much does noon's managed ads cost?",
            'a_ar' => 'عادةً ما تُدار الإعلانات المُدارة لدى نون من قِبل فريق إعلانات نون بدلًا من المعلن نفسه، وتتطلب حدًا أدنى للإنفاق قدره 1,500 دولار أمريكي.',
            'a_en' => "noon's Display Ads are self managed by advertisers whereas managed Display Ads are handled by the noon ads team. Managed Display Ads require a minimum spend of $1,500 USD.",
        ],
        [
            'q_ar' => 'من يمكنه تشغيل حملات إعلانات العرض ذاتية الخدمة؟',
            'q_en' => 'Who can run Display Ads campaigns?',
            'a_ar' => 'إعلانات العرض متاحة لجميع البائعين والموزعين والعلامات التجارية النشطة على نون. سيتم منحك حق الوصول إلى لوحة التحكم ذاتية الخدمة لإنشاء حملاتك وتعديلها وتتبعها.',
            'a_en' => 'Display Ads are open to all sellers, distributors and brands that are active on noon. You will be given access to the self-serve dashboard to create, edit and track your campaigns.',
        ],
        [
            'q_ar' => 'كيف يمكنني البدء في استخدام إعلانات العرض؟',
            'q_en' => 'How can I get started with Display Ads?',
            'a_ar' => "إذا كان لديك بالفعل حق الوصول إلى لوحة تحكم شركاء نون، فما عليك سوى الضغط على 'إنشاء حملة' واختيار إعلانات العرض كنوع إعلانك وملء الحقول المطلوبة.",
            'a_en' => 'Click on the "Start Now" button at the top right of the page and register as a noon Partner to have access to our ads manager. If you are already a noon Partner, login and you will be automatically directed to the ads manager where you can select your campaign type as "Display Ads".',
        ],
        [
            'q_ar' => 'هل يمكنني تعديل حملة مباشرة؟',
            'q_en' => 'Can I edit a live campaign?',
            'a_ar' => "نعم، يمكنك تعديل حملة مباشرة. في لوحة تحكم شركاء نون، قم بالتمرير للأسفل إلى قائمة حملاتك. حدد الحملة التي تريد تعديلها واضغط على 'تعديل'. بمجرد الرضا، اضغط فوق 'حفظ وإطلاق' لتنشيط الحملة مرة أخرى.",
            'a_en' => 'Yes, live campaigns can be edited directly through the ads manager. Scroll to the campaigns list and select the one you want to edit, the edit option should appear. Do not forget to click on "Save and Launch" to get the campaign running again.',
        ],
        [
            'q_ar' => 'أين ستكون إعلاناتي مرئية؟',
            'q_en' => 'Where will my ads be visible?',
            'a_ar' => 'تظهر إعلانات العرض على شكل لافتات شريطية على الصفحة الرئيسية وصفحات الفئات الخاصة بنون.',
            'a_en' => "Display Ads are visible as strip banners on noon's Homepage and Category Pages.",
        ],
        [
            'q_ar' => 'كيف يمكنني الوصول إلى لوحة التحكّم؟',
            'q_en' => 'How can I get access to the dashboard?',
            'a_ar' => 'يمكن لأي شخص الوصول إلى منصة شريك نون والتسجيل في لوحة تحكم ذاتية الخدمة.',
            'a_en' => 'Anyone can access the noon Partner platform and register for a self-serve dashboard.',
        ],
        [
            'q_ar' => 'ما هو نوع تقارير الأداء المتاحة؟',
            'q_en' => 'What type of performance reporting is available?',
            'a_ar' => 'تضع لوحة التحكم ذاتية الخدمة جميع المقاييس الرئيسية في متناول يدك، مما يسمح لك بمراقبة الأداء في الوقت الفعلي. تعمق أكثر مع مقتطفات البيانات الدقيقة. قم بتنزيل البيانات وتحليلها في أداتك المفضلة للحصول على رؤى متقدمة وتحسين الحملة.',
            'a_en' => 'Our self-service dashboard puts all major metrics at your fingertips, allowing you to monitor performance in real-time. Dive deeper with granular data extracts. Download and analyze data in your preferred tool for advanced insights and campaign optimization.',
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
