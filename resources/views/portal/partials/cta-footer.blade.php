@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<footer class="text-center">
    <div class="bg-yellow-400 text-black">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-12">
                <p class="font-black text-xs uppercase tracking-wider">{{ $isAr ? 'ابدأ الآن' : 'Start now' }}</p>
                <p class="mt-2 text-2xl sm:text-[32px] font-extrabold mb-3">
                    {{ $isAr ? 'جاهز للبيع؟' : 'Ready to sell?' }}
                </p>
                <p class="text-[16px] font-medium max-w-[60ch] mx-auto">
                    {{ $isAr ? 'انضم إلى آلاف البائعين في جميع أنحاء المنطقة' : 'Join thousands of sellers across the region' }}
                    <br>
                    {{ $isAr ? 'الذين ينمون ويكبرون مع نون.' : 'who are growing and thriving with noon.' }}
                </p>
                <a href="{{ route('portal.register') }}"
                   class="mt-8 inline-flex items-center gap-2 bg-black hover:bg-gray-900 text-white
                          font-black text-base px-8 py-3.5 rounded-full transition-colors">
                    {{ $isAr ? 'سجل الآن' : 'Register now' }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" class="animate-[bounce_1.1s_ease-in-out_infinite] {{ $isAr ? '-scale-x-100' : '' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="flex justify-end px-4 pb-3">
            <p class="text-xs font-medium">
                &copy; {{ date('Y') }} {{ $isAr ? 'نون. جميع الحقوق محفوظة' : 'noon. All rights reserved' }}
            </p>
        </div>
    </div>
</footer>
