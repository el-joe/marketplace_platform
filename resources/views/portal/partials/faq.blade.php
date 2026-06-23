@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-900 py-24" id="faq">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'الأسئلة الشائعة' : 'FAQ' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                {{ $isAr ? 'أسئلة يسألها البائعون' : 'Questions Sellers Ask' }}
            </h2>
            <p class="mt-4 text-gray-400">
                {{ $isAr ? 'إجابات لأكثر الأسئلة شيوعاً حول البيع على نون.' : 'Answers to the most common questions about selling on Noon.' }}
            </p>
        </div>

        @php
            $faqs = [
                [
                    'q_ar' => 'كم تبلغ عمولة نون على المبيعات؟',
                    'q_en' => 'What is Noon\'s commission on sales?',
                    'a_ar' => 'تتراوح عمولة نون بين ٥٪ و١٥٪ حسب الفئة والمنتج. يمكنك استخدام حاسبة العمولات في لوحة التحكم لمعرفة تكاليف منتجاتك المحددة بدقة قبل النشر.',
                    'a_en' => 'Noon\'s commission ranges from 5% to 15% depending on the category and product. You can use the commission calculator in the dashboard to accurately determine your specific product costs before listing.',
                ],
                [
                    'q_ar' => 'متى أستطيع سحب أرباحي؟',
                    'q_en' => 'When can I withdraw my earnings?',
                    'a_ar' => 'تتم مدفوعات البائعين دورياً — أسبوعياً أو نصف شهرياً أو شهرياً حسب حجم المبيعات والاتفاق. يتم التحويل مباشرة إلى حسابك البنكي المسجل بعد التحقق منه.',
                    'a_en' => 'Seller payments are made periodically — weekly, bi-weekly, or monthly depending on sales volume and agreement. Funds are transferred directly to your verified registered bank account.',
                ],
                [
                    'q_ar' => 'هل يمكنني البيع في أكثر من دولة؟',
                    'q_en' => 'Can I sell in more than one country?',
                    'a_ar' => 'نعم! تتيح لك نون البيع في الإمارات والمملكة العربية السعودية ومصر من حساب واحد. يمكنك ضبط الأسعار والمخزون لكل سوق بشكل مستقل من خلال لوحة التحكم.',
                    'a_en' => 'Yes! Noon allows you to sell in UAE, Saudi Arabia, and Egypt from a single account. You can set prices and inventory for each market independently through the control panel.',
                ],
                [
                    'q_ar' => 'ما هي المستندات المطلوبة للتسجيل؟',
                    'q_en' => 'What documents are required for registration?',
                    'a_ar' => 'تحتاج إلى: بريد إلكتروني تجاري، ترخيص تجاري ساري، هوية صاحب العمل، وحساب بنكي مفعّل. بعض الفئات قد تتطلب شهادات إضافية مثل شهادة الجودة أو التسجيل الضريبي.',
                    'a_en' => 'You need: a business email, valid trade license, owner ID, and active bank account. Some categories may require additional certificates such as quality certification or tax registration.',
                ],
                [
                    'q_ar' => 'كم يستغرق قبول طلب التسجيل؟',
                    'q_en' => 'How long does the registration approval take?',
                    'a_ar' => 'عادةً تستغرق مراجعة الطلب من ٢ إلى ٥ أيام عمل بعد تقديم جميع المستندات المطلوبة. ستتلقى إشعاراً بالبريد الإلكتروني فور البت في طلبك.',
                    'a_en' => 'Registration review typically takes 2 to 5 business days after submitting all required documents. You will receive an email notification as soon as a decision is made on your application.',
                ],
                [
                    'q_ar' => 'هل هناك رسوم اشتراك شهرية؟',
                    'q_en' => 'Are there monthly subscription fees?',
                    'a_ar' => 'التسجيل مجاني تماماً. نون تأخذ فقط عمولة على كل عملية بيع تتم فعلياً. لا توجد رسوم اشتراك شهرية أو سنوية.',
                    'a_en' => 'Registration is completely free. Noon only takes a commission on each actual sale. There are no monthly or annual subscription fees.',
                ],
                [
                    'q_ar' => 'كيف أتعامل مع المرتجعات؟',
                    'q_en' => 'How do I handle returns?',
                    'a_ar' => 'في نموذج FBN، تتولى نون إدارة المرتجعات بالكامل. في نموذج SFS، تتلقى إشعاراً بالطلب المُرجع وتقوم بالتنسيق وفق سياسة الإرجاع المحددة في متجرك.',
                    'a_en' => 'In the FBN model, Noon handles returns completely. In the SFS model, you receive a notification about the returned order and coordinate according to the return policy set in your store.',
                ],
                [
                    'q_ar' => 'هل يمكنني تعديل أسعار منتجاتي في أي وقت؟',
                    'q_en' => 'Can I update my product prices at any time?',
                    'a_ar' => 'نعم، يمكنك تحديث الأسعار والمخزون في أي وقت من خلال لوحة تحكم البائع. التغييرات تنعكس على المنصة في غضون دقائق.',
                    'a_en' => 'Yes, you can update prices and inventory at any time through the seller dashboard. Changes reflect on the platform within minutes.',
                ],
            ];
        @endphp

        <div class="space-y-3" x-data="{ openIndex: null }">
            @foreach($faqs as $idx => $faq)
                <div class="bg-gray-800/50 border border-gray-700 rounded-2xl overflow-hidden
                            hover:border-gray-600 transition-colors">
                    <button @click="openIndex = openIndex === {{ $idx }} ? null : {{ $idx }}" class="w-full flex items-center justify-between gap-4 px-6 py-5
                               text-{{ $isAr ? 'right' : 'left' }} focus:outline-none group">
                        <span class="font-semibold text-white group-hover:text-yellow-400 transition-colors">
                            {{ $isAr ? $faq['q_ar'] : $faq['q_en'] }}
                        </span>
                        <span class="shrink-0 w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center
                                     transition-colors group-hover:bg-yellow-400/20">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-yellow-400 transition-all duration-200"
                                :class="openIndex === {{ $idx }} ? 'rotate-45' : ''" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                    </button>

                    <div x-show="openIndex === {{ $idx }}" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5">
                        <p class="text-gray-400 leading-relaxed {{ $isAr ? 'text-right' : 'text-left' }}">
                            {{ $isAr ? $faq['a_ar'] : $faq['a_en'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Still have questions CTA --}}
        <div class="mt-12 text-center bg-gray-800/50 border border-gray-700 rounded-3xl p-8">
            <div class="text-3xl mb-3">🤔</div>
            <h3 class="text-xl font-black text-white mb-2">
                {{ $isAr ? 'لديك سؤال آخر؟' : 'Have Another Question?' }}
            </h3>
            <p class="text-gray-400 mb-6">
                {{ $isAr
    ? 'فريق الدعم المخصص لدينا متاح ٢٤/٧ للإجابة على جميع استفساراتك.'
    : 'Our dedicated support team is available 24/7 to answer all your inquiries.' }}
            </p>
            <a href="mailto:sellers@noon.com" class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-gray-950
                      font-bold px-6 py-3 rounded-xl transition-colors">
                {{ $isAr ? 'تواصل مع الدعم' : 'Contact Support' }}
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
            </a>
        </div>

    </div>
</section>