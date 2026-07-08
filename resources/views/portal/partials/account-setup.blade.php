@php
    $isAr = session('locale', 'ar') === 'ar';

    $steps = [
        [
            'title_ar' => 'قم بتجهيز بياناتك',
            'title_en' => 'Get your documents ready',
            'caption_ar' => 'قبل ان تبدأ, احرص علي توفر ما يلي',
            'caption_en' => 'Before you start, make sure you have the following',
            'items' => [
                [
                    'text_ar' => 'عنوان البريد الالكتروني (للاستخدام التجاري او الشخصي) و رقم الهاتف',
                    'text_en' => 'A business or personal email address and a phone number',
                ],
                [
                    'text_ar' => 'السجل التجاري / رخصة التجارة',
                    'text_en' => 'Trade licence / commercial registration',
                    'link_ar' => 'لست متأكد ما هذا؟ تعرف علي المزيد',
                    'link_en' => "Not sure what this is? Learn more",
                    'link_route' => 'portal.faq',
                ],
                [
                    'text_ar' => 'الهوية الشخصية - جواز السفر / بطاقة الهوية الاماراتية',
                    'text_en' => 'A valid ID - passport or Emirates ID',
                ],
            ],
        ],
        [
            'title_ar' => 'قم بالتسجيل كبائع',
            'title_en' => 'Sign up as a seller',
            'caption_ar' => 'عمل حسابك كبائع سريع و سهل',
            'caption_en' => 'Creating your seller account is quick and easy',
            'items' => [
                ['text_ar' => 'سجل باستخدام بريدك الإلكتروني ورقم هاتفك خلال دقائق', 'text_en' => 'Sign up with your email and phone number in minutes'],
                ['text_ar' => 'أدخل بيانات نشاطك التجاري الأساسية', 'text_en' => 'Enter your basic business details'],
                ['text_ar' => 'وافق على شروط وأحكام البائعين لتفعيل حسابك', 'text_en' => 'Accept the seller terms & conditions to activate your account'],
            ],
            'cta_ar' => 'سجل الآن',
            'cta_en' => 'Register now',
            'cta_route' => 'portal.register',
        ],
        [
            'title_ar' => 'انشئ متجرك',
            'title_en' => 'Create your store',
            'caption_ar' => 'قم بانشاء متجرك و اضف اوراقك التجارية',
            'caption_en' => 'Set up your store and add your trade documents',
            'items' => [
                ['text_ar' => 'اختر اسم متجرك واجعله مميزًا لعملائك', 'text_en' => 'Choose your store name and make it memorable'],
                ['text_ar' => 'ارفع مستنداتك التجارية والهوية للتحقق', 'text_en' => 'Upload your trade licence and ID for verification'],
                ['text_ar' => 'انتظر الموافقة لتبدأ في إضافة منتجاتك', 'text_en' => 'Wait for approval to start adding your products'],
            ],
        ],
    ];
@endphp

<section id="registering" class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 md:grid-cols-[2fr_3fr] gap-8 md:gap-10">
        <div class="relative aspect-[4/3] md:aspect-auto rounded-2xl overflow-hidden">
            <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-selling.jpg"
                 alt="{{ $isAr ? 'خطوات التسجيل' : 'Registration steps' }}"
                 class="absolute inset-0 w-full h-full object-cover">
        </div>

        <div>
            <h2 class="text-2xl sm:text-3xl font-black text-white mb-6">
                {{ $isAr ? 'جهّز حسابك' : 'Set up your account' }}
            </h2>

            <div x-data="{ openStep: 0 }" class="flex flex-col gap-3">
                @foreach($steps as $i => $step)
                    <div class="rounded-lg bg-black overflow-hidden">
                        <button type="button" @click="openStep = openStep === {{ $i }} ? null : {{ $i }}"
                                class="w-full flex items-center justify-between gap-4 px-4 py-4 {{ $isAr ? 'text-right' : 'text-left' }}">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-1 text-white font-bold text-[16px] leading-tight">
                                    <span>{{ $isAr ? 'الخطوة '.$i.':' : 'Step '.$i.':' }}</span>
                                    <span>{{ $isAr ? $step['title_ar'] : $step['title_en'] }}</span>
                                </div>
                                <span class="text-[12px] font-bold uppercase tracking-wider text-yellow-400">
                                    {{ $isAr ? $step['caption_ar'] : $step['caption_en'] }}
                                </span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                 class="h-5 w-5 shrink-0 text-yellow-400 transition-transform"
                                 :style="openStep === {{ $i }} ? 'transform:rotate(180deg)' : ''">
                                <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 0 1-1.06 0l-7.5-7.5a.75.75 0 0 1 1.06-1.06L12 14.69l6.97-6.97a.75.75 0 1 1 1.06 1.06l-7.5 7.5Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="openStep === {{ $i }}" x-cloak x-transition
                             class="px-4 pb-4">
                            <ul class="space-y-3">
                                @foreach($step['items'] as $item)
                                    <li class="flex items-start gap-3 text-gray-200 text-[14px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3E008" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                        <span class="flex flex-col gap-1">
                                            <span>{{ $isAr ? $item['text_ar'] : $item['text_en'] }}</span>
                                            @if(isset($item['link_route']))
                                                <a href="{{ route($item['link_route']) }}" class="text-yellow-400 font-bold text-[13px] hover:underline">
                                                    {{ $isAr ? $item['link_ar'] : $item['link_en'] }}
                                                </a>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            @if(isset($step['cta_route']))
                                <a href="{{ route($step['cta_route']) }}"
                                   class="inline-block mt-4 bg-yellow-400 hover:bg-yellow-300 text-black font-black text-sm px-6 py-2.5 rounded-full transition-colors">
                                    {{ $isAr ? $step['cta_ar'] : $step['cta_en'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
