@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-[1140px] mx-auto rounded-2xl border border-[#2a2a2a] p-6 md:px-[60px] md:py-[44px] grid md:grid-cols-[1fr_auto] items-center gap-6 md:gap-12">
        <div class="text-center md:text-start">
            <h2 class="text-white font-bold text-[22px] lg:text-[26px] leading-tight mb-2">
                {{ $isAr ? 'هل تحتاج للمساعدة؟' : 'Need Help?' }}
            </h2>
            <p class="text-gray-400 text-sm">
                {{ $isAr
                    ? 'حمّل دليل البائع أو شاهد فيديوهات الشرح — كل ما تحتاجه، خطوة بخطوة.'
                    : "Grab our Seller's Guide or check out our video tutorials — everything you need, step-by-step." }}
            </p>
        </div>
        <div class="flex flex-wrap justify-center gap-5">
            <a href="{{ route('portal.faq') }}"
               class="bg-[#feee00] hover:bg-[#e5d600] text-black text-[13px] font-bold px-8 py-3 rounded-full transition-colors tracking-wide">
                {{ $isAr ? 'حمل الدليل' : 'Download The Guide' }}
            </a>
            <a href="{{ route('portal.how-it-works') }}"
               class="border border-[#feee00] hover:bg-[#feee00]/10 text-[#feee00] text-[13px] font-bold px-8 py-3 rounded-full transition-colors tracking-wide">
                {{ $isAr ? 'شاهد الفيديوهات' : 'Watch The Tutorials' }}
            </a>
        </div>
    </div>
</section>
