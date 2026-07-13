@php $isAr = session('locale', 'ar') === 'ar'; @endphp

@push('head')
<style>
    @keyframes blink-four-times {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    .animate-blink-4 {
        animation: blink-four-times 0.6s ease-in-out 4 forwards;
    }
</style>
@endpush

<section class="bg-black relative pt-8 pb-10 lg:pb-12 md:py-10 lg:py-12">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <h2 class="text-[32px] sm:text-[40px] lg:text-[48px] font-black mb-6 lg:mb-8 text-white leading-tight">
            {{ $isAr ? 'خطوات لـ ' : 'Steps to ' }}<span class="text-[#feee00] animate-blink-4 inline-block">{{ $isAr ? 'البدء' : 'Go Live' }}</span>
        </h2>

        <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] md:bg-transparent md:grid md:grid-cols-[1.5fr_2fr] md:gap-10 lg:gap-14">
            <div class="relative aspect-[4/3] sm:aspect-[2/1] md:aspect-auto md:rounded-2xl md:overflow-hidden">
                <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-steps-go-live.jpg"
                     alt="{{ $isAr ? 'موظفو نون يعبئون الصناديق' : 'noon Employees packing items into crates' }}"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="pt-8 px-6 pb-10 md:px-0 md:py-6">
                <h3 class="text-white font-black text-xl lg:text-2xl">
                    {{ $isAr ? 'ابدأ البيع على نون بثلاث خطوات سهلة' : 'Start selling on noon in three easy steps' }}
                </h3>

                <ul class="mt-6 space-y-3">
                    @foreach([
                        [$isAr ? 'قم بإعداد حسابك' : 'Set up your account'],
                        [$isAr ? 'جهز قوائم منتجاتك' : 'Get your listings ready'],
                        [$isAr ? 'اختر نموذج التنفيذ الخاص بك' : 'Choose your fulfilment model'],
                    ] as $item)
                        <li class="flex items-start gap-3 text-white font-bold text-[15px]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#feee00" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                            <span>{{ $item[0] }}</span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-8 text-gray-300 font-medium text-sm">
                    {{ $isAr ? 'هذا كل شيء — أنت مستعد لتلقي أول طلب لك' : "That's it — you're ready to receive your first order" }}
                </p>
            </div>
        </div>
    </div>
</section>
