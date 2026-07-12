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
                <div class="max-w-[480px] {{ $isAr ? 'text-right' : 'text-left' }}"
                     x-data="{ 
                         text: @js(portal_content('home', 'hero', 'title', 'Start selling on noon today!', 'ابدأ البيع على نون اليوم!')),
                         displayed: '',
                         typewriter() {
                             let i = 0;
                             let interval = setInterval(() => {
                                 this.displayed += this.text.charAt(i);
                                 i++;
                                 if (i >= this.text.length) {
                                     clearInterval(interval);
                                 }
                             }, 50);
                         }
                     }"
                     x-init="setTimeout(() => typewriter(), 300)">
                    <h1 class="text-white font-extrabold leading-[1.1] text-[50px] relative">
                        <span class="opacity-0">{{ portal_content('home', 'hero', 'title', 'Start selling on noon today!', 'ابدأ البيع على نون اليوم!') }}</span>
                        <span class="absolute top-0 {{ $isAr ? 'right-0' : 'left-0' }} w-full" x-text="displayed"></span>
                    </h1>
                    @php($heroCta = portal_link('home', 'hero', 'cta_button', 'Sign Up Now', 'سجل الآن', route('portal.register')))
                    <a href="{{ $heroCta['url'] }}"
                       class="group inline-flex items-center justify-center gap-2 mt-6 bg-[#f3e00b] hover:opacity-90 text-black
                              font-bold text-base min-w-[222px] h-10 px-12 rounded-full transition-all duration-300 capitalize">
                        <span>{{ $heroCta['label'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20"
                             class="{{ $isAr ? '-scale-x-100' : '' }} mt-[1px] transition-transform duration-300 group-hover:translate-x-1 {{ $isAr ? 'group-hover:-translate-x-1' : '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
