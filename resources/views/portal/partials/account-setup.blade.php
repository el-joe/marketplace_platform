@php
    $isAr = session('locale', 'ar') === 'ar';

    $steps = [
        [
            'title_ar' => 'قم بتجهيز بياناتك',
            'title_en' => 'Get your details ready',
            'caption_ar' => 'قبل أن تبدأ، احرص على توفر ما يلي',
            'caption_en' => 'BEFORE YOU START, HAVE THE FOLLOWING HANDY',
            'items' => [
                [
                    'text_ar' => 'عنوان بريد إلكتروني ورقم هاتف',
                    'text_en' => 'Email address and a phone number',
                ],
                [
                    'text_ar' => 'السجل التجاري / رخصة التجارة',
                    'text_en' => 'Commercial Registration / Trade License',
                    'link_ar' => 'لست متأكد ما هذا؟ تعرف علي المزيد',
                    'link_en' => "Not sure what this is? Learn more",
                    'link_route' => 'portal.faq',
                    'link_arrow' => true,
                ],
                [
                    'text_ar' => 'إثبات الهوية — جواز السفر / بطاقة الهوية الإماراتية',
                    'text_en' => 'Identity proof — Passport / Emirates ID',
                ],
            ],
        ],
        [
            'title_ar' => 'قم بالتسجيل كبائع',
            'title_en' => 'Sign up as a seller',
            'caption_ar' => 'إنشاء حساب البائع الخاص بك سريع وسهل',
            'caption_en' => 'CREATING YOUR SELLER ACCOUNT IS QUICK AND EASY',
            'items' => [
                [
                    'text_ar' => 'اضغط على',
                    'text_en' => 'Click on',
                    'inline_link_ar' => 'سجل الآن',
                    'inline_link_en' => 'Sign up now',
                    'inline_link_route' => 'portal.register',
                    'inline_link_arrow' => true,
                ],
                [
                    'text_ar' => 'زودنا ببعض التفاصيل',
                    'text_en' => 'Provide us with a few details',
                ],
                [
                    'text_ar' => 'قم بالتحقق منها',
                    'text_en' => 'Verify them',
                ],
            ],
            'after_list_text_ar' => 'وهكذا، أصبحت الآن جزءاً من عائلة نون',
            'after_list_text_en' => 'And just like that, you are now part of the noon family',
        ],
        [
            'title_ar' => 'إعداد متجرك',
            'title_en' => 'Set up your store',
            'caption_ar' => 'قم بإنشاء متجرك وإضافة مستندات عملك',
            'caption_en' => 'CREATE YOUR STORE AND ADD YOUR BUSINESS DOCUMENTS',
            'paragraphs' => [
                [
                    'text_ar' => 'قم بإنشاء متجرك للبلد الذي ترغب في البيع فيه وأضف مستندات عملك للحصول على موافقة سريعة وسلسة',
                    'text_en' => 'Create your store for the country that you wish to sell in and add your business documents for a quick and seamless approval',
                ],
                [
                    'text_ar' => 'بمجرد إنشاء متجرك، يصبح مختبر البائع مركز التحكم الخاص بك لكل ما يتعلق بنون',
                    'text_en' => 'Once you create your store, seller lab becomes your control center for everything noon',
                ],
            ],
        ],
    ];
@endphp

<section id="registering" class="bg-[#1c1c1c] py-10 lg:py-16">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 lg:gap-16 items-start">
            <div class="relative aspect-[4/3] rounded-2xl overflow-hidden">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-selling.jpg"
                     alt="{{ $isAr ? 'خطوات التسجيل' : 'Registration steps' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>

            <div>
            <h2 class="text-[32px] sm:text-[40px] lg:text-[48px] font-black mb-6 lg:mb-8 text-white leading-tight">
                {{ $isAr ? 'إعداد حسابك' : 'Set up your account' }}
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
                                <span class="text-[12px] font-bold uppercase tracking-wider text-[#feee00]">
                                    {{ $isAr ? $step['caption_ar'] : $step['caption_en'] }}
                                </span>
                            </div>
                        </button>

                        <div x-show="openStep === {{ $i }}" x-cloak x-transition
                             class="px-4 pb-4">
                            @if(isset($step['items']))
                                <ul class="space-y-3">
                                    @foreach($step['items'] as $index => $item)
                                        <li class="flex items-start gap-3 text-gray-200 text-[14px] animate-fade-in-up"
                                            style="animation-delay: {{ $index * 50 }}ms; animation-fill-mode: both;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                            <div class="flex flex-col gap-1 w-full">
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span>{{ $isAr ? $item['text_ar'] : $item['text_en'] }}</span>
                                                    @if(isset($item['inline_link_route']))
                                                        <a href="{{ route($item['inline_link_route']) }}" class="text-[#feee00] font-bold text-[13px] hover:underline flex items-center gap-1 group">
                                                            {{ $isAr ? $item['inline_link_ar'] : $item['inline_link_en'] }}
                                                            @if(isset($item['inline_link_arrow']) && $item['inline_link_arrow'])
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" class="animate-bounce-x {{ $isAr ? '-scale-x-100' : '' }}">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                                </svg>
                                                            @endif
                                                        </a>
                                                    @endif
                                                </div>
                                                @if(isset($item['link_route']))
                                                    <a href="{{ route($item['link_route']) }}" class="text-[#feee00] font-bold text-[13px] hover:underline flex items-center gap-1 group">
                                                        {{ $isAr ? $item['link_ar'] : $item['link_en'] }}
                                                        @if(isset($item['link_arrow']) && $item['link_arrow'])
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" class="animate-bounce-x {{ $isAr ? '-scale-x-100' : '' }}">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                            </svg>
                                                        @endif
                                                    </a>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if(isset($step['after_list_text_en']))
                                <p class="mt-4 text-[14px] text-gray-200">
                                    {{ $isAr ? $step['after_list_text_ar'] : $step['after_list_text_en'] }}
                                </p>
                            @endif

                            @if(isset($step['paragraphs']))
                                <div class="space-y-4 text-gray-200 text-[14px]">
                                    @foreach($step['paragraphs'] as $para)
                                        <p>{{ $isAr ? $para['text_ar'] : $para['text_en'] }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>
</section>
