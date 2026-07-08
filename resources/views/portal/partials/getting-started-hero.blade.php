@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="relative overflow-hidden bg-black">
    <div class="h-[420px] sm:h-[480px] lg:h-[444px] relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/02-hero.jpg"
             alt="{{ $isAr ? 'موظفو نون في العمل' : 'noon employees working' }}"
             class="absolute inset-0 w-full h-full object-cover {{ $isAr ? '-scale-x-100' : '' }}">

        <div class="absolute inset-0 {{ $isAr ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black via-black/85 to-transparent lg:via-black/70"></div>
        <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black to-transparent"></div>

        <div class="absolute inset-0 flex items-center">
            <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-[560px] {{ $isAr ? 'text-right' : 'text-left' }}">
                    <h1 class="text-white font-black leading-[1.1] text-[36px] sm:text-[44px] lg:text-[50px]">
                        {{ $isAr ? 'مرحبا بك في' : 'Welcome to' }}
                        <span class="block text-yellow-400">{{ $isAr ? 'البيع على نون!' : 'selling on noon!' }}</span>
                    </h1>
                    <p class="mt-4 text-gray-200 font-semibold text-[15px] sm:text-[17px] leading-relaxed">
                        {{ $isAr
                            ? 'حول النقرات إلى أموال. ابدأ بقوة من خلال إدراج منتجاتك بالطريقة الصحيحة — وشاهد مبيعاتك تنطلق.'
                            : 'Turn clicks into cash. Start strong by listing your products the right way — and watch your sales take off.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
