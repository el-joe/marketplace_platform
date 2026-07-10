@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<footer class="bg-gray-50 border-t border-gray-100">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-center">
        <p class="text-xs sm:text-sm text-gray-500 order-2 sm:order-1">
            &copy; {{ date('Y') }} {{ $isAr ? 'نون. جميع الحقوق محفوظة' : 'noon. All Rights Reserved' }}
        </p>
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs sm:text-sm font-medium text-gray-500 order-1 sm:order-2">
            <a href="{{ route('portal.register') }}" class="hover:text-gray-900">{{ $isAr ? 'بيع معنا' : 'Sell with us' }}</a>
            <a href="https://www.noon.com/uae-en/terms-of-use/" target="_blank" rel="noopener" class="hover:text-gray-900">{{ $isAr ? 'شروط الاستخدام' : 'Terms of Use' }}</a>
            <a href="https://www.noon.com/uae-en/terms-of-sale/" target="_blank" rel="noopener" class="hover:text-gray-900">{{ $isAr ? 'شروط البيع' : 'Terms of Sale' }}</a>
            <a href="https://www.noon.com/uae-en/privacy-policy/" target="_blank" rel="noopener" class="hover:text-gray-900">{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}</a>
        </div>
    </div>
</footer>
