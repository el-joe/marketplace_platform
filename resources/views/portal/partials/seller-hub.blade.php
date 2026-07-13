@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl border-2 border-[#1c1c1c] overflow-hidden md:overflow-visible md:p-10 lg:grid lg:grid-cols-2 lg:items-center lg:gap-16">
        <div class="relative aspect-[4/3] lg:aspect-auto lg:h-full min-h-[280px] md:rounded-2xl md:overflow-hidden">
            <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/04-transparent-fees-01.jpg"
                 alt="{{ $isAr ? 'مركز البائعين' : 'Seller Hub' }}"
                 class="absolute inset-0 w-full h-full object-cover">
        </div>

        <div class="pt-8 px-5 pb-8 md:p-0 md:mt-8 lg:mt-0 max-w-[60ch]">
            <p class="text-[#feee00] font-black text-xs uppercase tracking-wider">{{ $isAr ? 'مركز البائعين' : 'Seller Hub' }}</p>
            <h2 class="text-white font-black text-xl lg:text-2xl mt-1 mb-6">
                {{ $isAr ? 'مركز قيادتك للبيع على نون' : 'Your command centre for selling on noon' }}
            </h2>

            <ul class="space-y-4 mb-8">
                @foreach([
                    [
                        $isAr ? 'قم بادارة اعمالك' : 'Manage your business',
                        $isAr ? 'قم بإدارة حسابك، الكتالوج، المخزون، والأسعار - كل ذلك من مكان واحد.' : 'Manage your account, catalogue, inventory and pricing - all in one place.',
                    ],
                    [
                        $isAr ? 'تتبع و حصل المدفوعات' : 'Track and collect payments',
                        $isAr ? 'راقب الطلبات، أداء المبيعات، التسويات والمدفوعات في الوقت الفعلي.' : 'Monitor orders, sales performance, settlements and payouts in real time.',
                    ],
                    [
                        $isAr ? 'انمو بثقة' : 'Grow with confidence',
                        $isAr ? 'استخدم الإعلانات والتحليلات لتحسين الأداء والتوسع بشكل أسرع.' : 'Use ads and analytics to improve performance and scale faster.',
                    ],
                ] as $item)
                    <li class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 mt-1.5 {{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-white text-[17px]">{{ $item[0] }}</span>
                            <span class="text-[14px] text-gray-400">{{ $item[1] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>

            <p class="text-gray-300 text-[15px] font-medium">
                {{ $isAr
                    ? 'ستحصل أيضًا على دليل البائع القوي الخاص بنا، المصمم لدعمك من خطواتك الأولى وحتى إدارة عملك بثقة على نون.'
                    : "You'll also get our comprehensive seller guide, designed to support you from your first steps to confidently running your business on noon." }}
            </p>
        </div>
    </div>
</section>
