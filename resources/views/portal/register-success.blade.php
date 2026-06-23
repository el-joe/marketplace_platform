@extends('layouts.portal')

@section('title', 'تم إرسال طلبك — نون للبائعين')

@section('content')
    <div class="min-h-screen bg-gray-950 flex items-center justify-center py-16 px-4" dir="rtl">
        <div class="max-w-lg w-full text-center">

            {{-- Success Icon --}}
            <div
                class="w-20 h-20 bg-yellow-400/10 border-2 border-yellow-400/40 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-3xl font-black text-white mb-3">شكراً لتسجيلك!</h1>
            <p class="text-gray-400 text-base mb-8 leading-relaxed">
                استلمنا طلبك بنجاح. سيقوم فريقنا بمراجعة بياناتك ووثائقك خلال
                <span class="text-yellow-400 font-semibold">٣–٥ أيام عمل</span>،
                وسنتواصل معك عبر البريد الإلكتروني فور اتخاذ القرار.
            </p>

            {{-- Steps --}}
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-8 text-right">
                <h2 class="text-sm font-semibold text-gray-300 mb-4 text-center">ماذا سيحدث بعد ذلك؟</h2>
                <ul class="space-y-4">
                    <li class="flex items-start gap-4">
                        <span
                            class="flex-shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">١</span>
                        <div>
                            <p class="text-sm text-white font-medium">مراجعة الطلب</p>
                            <p class="text-xs text-gray-400 mt-0.5">سيراجع فريقنا بياناتك ووثائقك خلال ٣–٥ أيام عمل</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span
                            class="flex-shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">٢</span>
                        <div>
                            <p class="text-sm text-white font-medium">إشعار القرار</p>
                            <p class="text-xs text-gray-400 mt-0.5">ستتلقى بريداً إلكترونياً يتضمن قرار القبول أو طلب
                                معلومات إضافية</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span
                            class="flex-shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">٣</span>
                        <div>
                            <p class="text-sm text-white font-medium">ابدأ البيع!</p>
                            <p class="text-xs text-gray-400 mt-0.5">بعد الموافقة تحصل على وصول كامل للوحة تحكم البائع لإضافة
                                منتجاتك</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- CTA --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('portal.home') }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold rounded-xl text-sm transition-colors">
                    العودة للرئيسية
                </a>
                <a href="mailto:vendors@noon.com"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium rounded-xl text-sm transition-colors">
                    تواصل مع الدعم
                </a>
            </div>

        </div>
    </div>
@endsection