@php $isAr = session('locale', 'ar') === 'ar'; @endphp

{{-- Fulfilled by noon --}}
<section id="fbn" class="mt-[48px] md:bg-[#1c1c1c] md:py-10 lg:py-12">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-[32px] font-extrabold mb-6 lg:mb-8">
            <span class="text-[#feee00]">{{ $isAr ? 'التنفيذ من قبل نون:' : 'Fulfilled by noon:' }}</span>
            {{ $isAr ? 'مصمم للسرعة' : 'Built for speed' }}
        </h2>

        <div class="bg-[#1c1c1c] rounded-2xl overflow-hidden md:grid md:grid-cols-[1.5fr_2fr] md:gap-10 lg:gap-14">
            <div class="relative aspect-[4/3] sm:aspect-[2/1] md:aspect-auto">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-fbn.jpg"
                     alt="{{ $isAr ? 'موظف نون يعمل في المستودع' : 'A noon employee working in the warehouse' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-7 px-6 pb-10 md:px-0 md:py-6">
                <h3 class="text-white font-black text-lg lg:text-xl">
                    {{ $isAr ? 'العملاء يفضلون التوصيل السريع — ونون إكسبريس يقدمه.' : 'Customers love fast delivery — and noon express delivers it.' }}
                </h3>
                <p class="mt-4 text-gray-300 font-medium">
                    {{ $isAr ? 'خزن منتجاتك في مراكز تنفيذ الطلبات لدينا، وشاهد الطلبات تنطلق.' : 'Store your products in our fulfillment centers, and watch orders take off.' }}
                </p>

                <p class="mt-5 text-[#feee00] font-black text-xs uppercase tracking-wider">{{ $isAr ? 'الإضافة:' : "What's included:" }}</p>
                <p class="mt-1 text-gray-300 font-medium">
                    {{ $isAr
                        ? 'شحن وارد سريع، تخزين شهري في منشآت متطورة، خدمات إزالة المنتجات، ومعالجة المرتجعات.'
                        : 'Fast inbound shipping, monthly storage in state-of-the-art facilities, product removal services, and returns processing.' }}
                </p>

                <p class="mt-4 text-[#feee00] font-black text-xs uppercase tracking-wider">{{ $isAr ? 'مثالي لـ:' : 'Ideal for:' }}</p>
                <p class="mt-1 text-gray-300 font-medium">
                    {{ $isAr ? 'المنتجات الأكثر مبيعا، المنتجات الجديدة، وكل بائع جاهز للنمو.' : 'Best-selling products, new products, and any seller ready to grow.' }}
                </p>

                <a href="{{ route('portal.register') }}"
                   class="inline-flex items-center mt-8 bg-[#feee00] hover:bg-[#e5d600] text-black
                          font-black text-sm px-6 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'ابدأ مع التنفيذ من قبل نون' : 'Get started with Fulfilled by noon' }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- FBN testimonial --}}
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 mt-[24px] md:mt-[48px]">
    <section class="rounded-2xl border-2 border-[#1c1c1c] overflow-hidden md:flex md:flex-row-reverse md:items-center md:gap-10 md:p-10">
        <div class="mb-6 md:flex-1 md:mb-0">
            <div class="aspect-video rounded-xl overflow-hidden">
                <iframe class="w-full h-full" src="https://www.youtube.com/embed/soM79P27Hm4" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
        <div class="px-6 pb-8 md:flex-1 md:p-0">
            <h2 class="text-white font-black text-lg lg:text-xl">{{ $isAr ? 'قصة نجاح بائع' : 'Seller success story' }}</h2>
            <p class="mt-4 text-gray-300 text-sm font-medium">
                {{ $isAr
                    ? 'نون بالذات عملت إنجاز كبير أنا أخزن عندها وفرت علي أشياء كثيرة بهذه النقطة'
                    : "noon itself has been a huge achievement — I store with them and it's saved me so much at this point" }}
            </p>
            <p class="mt-4 text-gray-300 text-sm font-medium">
                {{ $isAr
                    ? 'هي تحملت التكلفة هذه وخففت العبء عني لعبت دور كبير بفضل الله في أنها نجحت مشروعي ونجحتني في عملي'
                    : "They took on this cost and eased the burden on me — by God's grace, they played a huge role in the success of my project and my work" }}
            </p>
            <p class="mt-4 text-xs font-bold text-[#feee00]">- {{ $isAr ? 'أفلاك الثريا' : 'Aflak Al Thurayya' }}</p>
        </div>
    </section>
</div>

{{-- Fulfilled by partner --}}
<section id="fbp" class="mt-[48px] md:bg-[#1c1c1c] md:py-10 lg:py-12">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-[32px] font-extrabold mb-6 lg:mb-8">
            <span class="text-[#feee00]">{{ $isAr ? 'التنفيذ من قبل الشريك:' : 'Fulfilled by partner:' }}</span>
            {{ $isAr ? 'مصمم للمرونة' : 'Built for flexibility' }}
        </h2>

        <div class="bg-[#1c1c1c] rounded-2xl overflow-hidden md:grid md:grid-cols-[1.5fr_2fr] md:gap-10 lg:gap-14">
            <div class="relative aspect-[4/3] sm:aspect-[2/1] md:aspect-auto">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/03-fbp.jpg"
                     alt="{{ $isAr ? 'صناديق نون' : 'noon boxes' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-7 px-6 pb-10 md:px-0 md:py-6">
                <h3 class="text-white font-black text-lg lg:text-xl mb-4">
                    {{ $isAr
                        ? 'بعض المنتجات تحتاج إلى خطة مختلفة. نماذج FBP من نون تمنحك خيارات:'
                        : "Some products need a different plan. noon's FBP models give you options:" }}
                </h3>
                <ul class="space-y-3">
                    @foreach([
                        [$isAr ? 'الشحن المباشر: أنت تغلّف، ونحن نوصّل.' : 'Direct shipping: You pack, we deliver.'],
                        [$isAr ? 'التوصيل المباشر: الأنسب للمنتجات ذات القيمة العالية أو اللي تحتاج تركيب.' : 'Direct delivery: Best for high-value products or those that need installation.'],
                    ] as $item)
                        <li class="flex items-start gap-3 text-gray-300 font-medium text-[15px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#feee00" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                            <span>{{ $item[0] }}</span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-5 text-[#feee00] font-black text-xs uppercase tracking-wider">{{ $isAr ? 'مثالي لـ:' : 'Ideal for:' }}</p>
                <p class="mt-1 text-gray-300 font-medium">
                    {{ $isAr ? 'المنتجات الثقيلة، الكبيرة، أو المتخصصة التي تتطلب لمسة شخصية.' : 'Heavy, bulky, or specialized products that require a personal touch.' }}
                </p>

                <a href="{{ route('portal.register') }}"
                   class="inline-flex items-center mt-8 bg-[#feee00] hover:bg-[#e5d600] text-black
                          font-black text-sm px-6 py-3 rounded-full transition-colors">
                    {{ $isAr ? 'ابدأ مع التنفيذ من قبل الشريك' : 'Get started with Fulfilled by partner' }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- FBP testimonial --}}
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 mt-[24px] md:mt-[48px] mb-[96px]">
    <section class="rounded-2xl border-2 border-[#1c1c1c] overflow-hidden md:flex md:flex-row-reverse md:items-center md:gap-10 md:p-10">
        <div class="mb-6 md:flex-1 md:mb-0">
            <div class="aspect-video rounded-xl overflow-hidden">
                <iframe class="w-full h-full" src="https://www.youtube.com/embed/8W9HHs8WcH0" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
        <div class="px-6 pb-8 md:flex-1 md:p-0">
            <h2 class="text-white font-black text-lg lg:text-xl">{{ $isAr ? 'قصة نجاح بائع' : 'Seller success story' }}</h2>
            <p class="mt-4 text-gray-300 text-sm font-medium">
                {{ $isAr
                    ? 'فريق نون بيتكفّل بكل الخطوات – من استلام المنتجات، لتغليفها بشكل مرتب، وتوصيلها للعميل. كل اللي عليك كَبائع هو تركّز على شغلك وتدير متجرك على المنصة بكل سهولة!'
                    : 'The noon team handles every step – from receiving products, to neatly packaging them, to delivering them to the customer. All you have to do as a seller is focus on your business and manage your store on the platform with ease!' }}
            </p>
            <p class="mt-4 text-xs font-bold text-[#feee00]">- {{ $isAr ? 'حكايات سحاب' : 'Hekayat Sahab' }}</p>
        </div>
    </section>
</div>
