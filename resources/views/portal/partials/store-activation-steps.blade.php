@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl sm:text-3xl font-black text-white mb-6 lg:mb-8">
        {{ $isAr ? 'خطوات تفعيل متجرك' : 'Steps to activate your store' }}
    </h2>

    <div class="rounded-2xl overflow-hidden bg-[#1c1c1c] grid md:grid-cols-[1.5fr_2fr]">
        <div class="relative aspect-[4/3] md:aspect-auto">
            <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-steps-go-live.jpg"
                 alt="{{ $isAr ? 'موظفو نون يعبئون الصناديق' : 'noon Employees packing items into crates' }}"
                 class="absolute inset-0 w-full h-full object-cover">
        </div>
        <div class="pt-8 px-6 pb-8 md:pt-10 md:px-10 md:pb-10">
            <h3 class="text-white font-black text-lg lg:text-xl mb-4">
                {{ $isAr ? 'إليك ما تحتاجه لتبدأ البيع على نون:' : "Here's what you need to start selling on noon:" }}
            </h3>
            <ul class="space-y-3 mb-6">
                @foreach([
                    [$isAr ? 'سجل كبائع' : 'Register as a seller'],
                    [$isAr ? 'اختر استراتيجية التنفيذ' : 'Choose your fulfilment strategy'],
                    [$isAr ? 'جهز قوائم منتجاتك' : 'Prepare your product listings'],
                ] as $item)
                    <li class="flex items-start gap-3 text-gray-200 text-[15px] font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F3E008" width="16" class="shrink-0 mt-1 {{ $isAr ? '-scale-x-100' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <span>{{ $item[0] }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="text-gray-400 text-sm font-medium">
                {{ $isAr ? 'اتبع هذه الخطوات البسيطة لبدء أعمالك بسرعة.' : 'Follow these simple steps to get your business up and running quickly.' }}
            </p>
        </div>
    </div>
</section>
