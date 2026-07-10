@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="relative overflow-hidden bg-black">
    {{-- Background photo --}}
    <div class="h-[560px] sm:h-[620px] lg:h-[500px] relative">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-hero-back-sm.jpg"
             alt="{{ $isAr ? 'مندوب توصيل نون يحمل طرداً في دبي' : 'A noon delivery agent carrying a box for delivery in Dubai' }}"
             class="absolute inset-0 w-full h-full object-cover md:hidden {{ $isAr ? '-scale-x-100' : '' }}">
        <img src="https://f.nooncdn.com/s/app/pr-comms/sell-with-us/01-hero-back.jpg"
             alt="{{ $isAr ? 'مندوب توصيل نون يحمل طرداً في دبي' : 'A noon delivery agent carrying a box for delivery in Dubai' }}"
             class="absolute inset-0 w-full h-full object-cover object-[75%_center] hidden md:block {{ $isAr ? '-scale-x-100' : '' }}">

        {{-- Gradient overlay: dark on the text side, transparent toward the photo --}}
        <div class="absolute inset-0 {{ $isAr ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black via-black/85 to-transparent lg:via-black/70"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black to-transparent"></div>

        {{-- Content --}}
        <div class="absolute inset-0 flex items-center">
            <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-[560px] {{ $isAr ? 'text-right' : 'text-left' }}">
                    <h1 class="text-white font-black leading-[1.1] text-[42px] sm:text-[52px] lg:text-[56px]">
                        {{ portal_content('home', 'hero', 'title', 'Start selling on noon today!', 'ابدأ البيع على نون اليوم!') }}
                    </h1>
                    @php($heroCta = portal_link('home', 'hero', 'cta_button', 'Register now', 'سجل الآن', route('portal.register')))
                    <a href="{{ $heroCta['url'] }}"
                       class="group inline-flex items-center gap-2 mt-8 bg-yellow-400 hover:bg-yellow-300 text-black
                              font-black text-base px-7 py-3.5 rounded-full transition-colors">
                        {{ $heroCta['label'] }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20"
                             class="{{ $isAr ? '-scale-x-100' : '' }} transition-transform group-hover:translate-x-1 {{ $isAr ? 'group-hover:-translate-x-1' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
