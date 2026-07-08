@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl border-2 border-[#2a2a2a] p-6 md:p-8 grid md:grid-cols-[1fr_auto] items-center gap-6">
        <div class="text-center md:text-start">
            <h2 class="text-white font-black text-lg lg:text-xl mb-1">
                {{ $isAr ? 'هل ما زلت بحاجة للمساعدة؟' : 'Still need help?' }}
            </h2>
            <p class="text-gray-400 text-sm">
                {{ $isAr
                    ? 'حمّل دليل البائع أو شاهد فيديوهات الشرح، أو تواصل مع فريق دعم البائعين.'
                    : 'Download the seller guide or watch our how-to videos, or reach out to our seller support team.' }}
            </p>
        </div>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('portal.faq') }}"
               class="border border-gray-600 hover:border-yellow-400 text-white text-sm font-bold px-5 py-2.5 rounded-full transition-colors">
                {{ $isAr ? 'حمل الدليل' : 'Download the guide' }}
            </a>
            <a href="{{ route('portal.how-it-works') }}"
               class="bg-yellow-400 hover:bg-yellow-300 text-black text-sm font-black px-5 py-2.5 rounded-full transition-colors">
                {{ $isAr ? 'شاهد فيديوهات الشرح' : 'Watch how-to videos' }}
            </a>
        </div>
    </div>
</section>
