@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-900 py-24" id="how-it-works">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'البدء' : 'Getting Started' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                {{ $isAr ? 'جاهز لتبدأ البيع؟' : 'Ready to Start Selling?' }}
            </h2>
            <p class="mt-4 text-gray-400 text-lg">
                {{ $isAr ? 'أربع خطوات بسيطة وتبدأ رحلتك مع نون.' : 'Four simple steps and your journey with Noon begins.' }}
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-12 items-start">

            {{-- Left: Requirements + Steps --}}
            <div class="space-y-10">

                {{-- Required documents --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-3xl p-8">
                    <h3 class="text-lg font-black text-white mb-6 flex items-center gap-2">
                        <span
                            class="w-8 h-8 bg-yellow-400/20 text-yellow-400 rounded-full flex items-center justify-center text-sm">📋</span>
                        {{ $isAr ? 'المستندات المطلوبة' : 'Required Documents' }}
                    </h3>
                    <ul class="space-y-4">
                        @php
                            $docs = [
                                ['📧', 'عنوان البريد الإلكتروني للاستخدام التجاري', 'Business email address'],
                                ['📄', 'السجل التجاري / رخصة التجارة', 'Trade license / Commercial registration'],
                                ['🪪', 'الهوية الشخصية / بطاقة الهوية الإماراتية', 'National ID / Emirates ID'],
                                ['🏦', 'حساب بنكي ساري', 'Active bank account'],
                            ];
                        @endphp
                        @foreach($docs as $doc)
                            <li class="flex items-start gap-3">
                                <span class="text-xl mt-0.5">{{ $doc[0] }}</span>
                                <div>
                                    <span class="text-gray-300 font-medium text-sm">
                                        {{ $isAr ? $doc[1] : $doc[2] }}
                                    </span>
                                </div>
                                <svg class="{{ $isAr ? 'mr-auto' : 'ml-auto' }} w-5 h-5 text-green-400 shrink-0 mt-0.5"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20,6 9,17 4,12" />
                                </svg>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- CTA --}}
                <a href="{{ route('portal.register') }}" class="flex items-center justify-center gap-3 bg-yellow-400 hover:bg-yellow-300
                          text-gray-950 font-black text-lg py-4 px-8 rounded-2xl transition-all
                          hover:scale-[1.02] shadow-lg shadow-yellow-400/20">
                    {{ $isAr ? 'ابدأ التسجيل مجاناً' : 'Start Free Registration' }}
                    <svg class="w-5 h-5 {{ $isAr ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <path d="M5 12h14m-7-7 7 7-7 7" />
                    </svg>
                </a>

            </div>

            {{-- Right: Steps timeline --}}
            <div class="relative">
                {{-- Vertical line --}}
                <div class="absolute {{ $isAr ? 'right-6' : 'left-6' }} top-8 bottom-8 w-px bg-gray-700"></div>

                <div class="space-y-6">
                    @php
                        $steps = [
                            [
                                '1',
                                'قم بالتسجيل مجاناً',
                                'Register for Free',
                                'أنشئ حسابك في دقائق باستخدام بريدك الإلكتروني وكلمة مرور.',
                                'Create your account in minutes using your email and password.',
                                '#facc15'
                            ],
                            [
                                '2',
                                'أضف تفاصيل متجرك',
                                'Add Your Store Details',
                                'أدخل معلومات عملك ورفع المستندات المطلوبة للتحقق.',
                                'Enter your business information and upload verification documents.',
                                '#60a5fa'
                            ],
                            [
                                '3',
                                'أضف منتجاتك',
                                'Add Your Products',
                                'أنشئ قوائم منتجاتك بالصور والأسعار والتفاصيل الكاملة.',
                                'Create your product listings with images, prices, and full details.',
                                '#34d399'
                            ],
                            [
                                '4',
                                'ابدأ البيع!',
                                'Start Selling!',
                                'بعد الموافقة، يبدأ متجرك بالنشاط وتستقبل طلباتك الأولى.',
                                'After approval, your store goes live and you start receiving orders.',
                                '#f97316'
                            ],
                        ];
                    @endphp

                    @foreach($steps as $idx => $step)
                        <div class="relative flex gap-6 {{ $isAr ? 'flex-row-reverse' : '' }}">
                            {{-- Step number bubble --}}
                            <div class="relative z-10 w-12 h-12 rounded-full flex items-center justify-center
                                        font-black text-gray-950 text-sm shrink-0 shadow-lg"
                                style="background-color: {{ $step[5] }}">
                                {{ $step[0] }}
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 bg-gray-800/50 border border-gray-700 rounded-2xl p-5
                                        hover:border-gray-600 transition-colors {{ $isAr ? 'text-right' : 'text-left' }}">
                                <h4 class="font-black text-white mb-1">
                                    {{ $isAr ? $step[1] : $step[2] }}
                                </h4>
                                <p class="text-sm text-gray-400">
                                    {{ $isAr ? $step[3] : $step[4] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>
</section>