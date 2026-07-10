@extends('layouts.portal')

@php $isAr = session('locale', 'ar') === 'ar'; @endphp

@section('title', $isAr ? 'تم إرسال طلبك — نون للبائعين' : 'Application Submitted — noon for Sellers')

@section('content')
    <div class="min-h-screen bg-gray-950 flex items-center justify-center py-16 px-4" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="max-w-lg w-full text-center">

            {{-- Success Icon --}}
            <div
                class="w-20 h-20 bg-yellow-400/10 border-2 border-yellow-400/40 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-3xl font-black text-white mb-3">{{ $isAr ? 'شكراً لتسجيلك!' : 'Thank you for registering!' }}</h1>
            <p class="text-gray-400 text-base mb-8 leading-relaxed">
                @if($isAr)
                    استلمنا طلبك بنجاح. سيقوم فريقنا بمراجعة بياناتك ووثائقك خلال
                    <span class="text-yellow-400 font-semibold">٣–٥ أيام عمل</span>،
                    وسنتواصل معك عبر البريد الإلكتروني فور اتخاذ القرار.
                @else
                    We've received your application successfully. Our team will review your information and documents within
                    <span class="text-yellow-400 font-semibold">3–5 business days</span>,
                    and we'll reach out via email as soon as a decision is made.
                @endif
            </p>

            {{-- Steps --}}
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-8 {{ $isAr ? 'text-right' : 'text-left' }}">
                <h2 class="text-sm font-semibold text-gray-300 mb-4 text-center">{{ $isAr ? 'ماذا سيحدث بعد ذلك؟' : 'What happens next?' }}</h2>
                <ul class="space-y-4">
                    <li class="flex items-start gap-4">
                        <span
                            class="shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">{{ $isAr ? '١' : '1' }}</span>
                        <div>
                            <p class="text-sm text-white font-medium">{{ $isAr ? 'مراجعة الطلب' : 'Application review' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $isAr ? 'سيراجع فريقنا بياناتك ووثائقك خلال ٣–٥ أيام عمل' : 'Our team will review your information and documents within 3–5 business days' }}</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span
                            class="shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">{{ $isAr ? '٢' : '2' }}</span>
                        <div>
                            <p class="text-sm text-white font-medium">{{ $isAr ? 'إشعار القرار' : 'Decision notification' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $isAr ? 'ستتلقى بريداً إلكترونياً يتضمن قرار القبول أو طلب معلومات إضافية' : 'You will receive an email with the approval decision or a request for additional information' }}</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span
                            class="shrink-0 w-8 h-8 bg-yellow-400/20 rounded-full flex items-center justify-center text-yellow-400 font-bold text-sm">{{ $isAr ? '٣' : '3' }}</span>
                        <div>
                            <p class="text-sm text-white font-medium">{{ $isAr ? 'ابدأ البيع!' : 'Start selling!' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $isAr ? 'بعد الموافقة تحصل على وصول كامل للوحة تحكم البائع لإضافة منتجاتك' : 'Once approved, you get full access to the seller dashboard to add your products' }}</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- CTA --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('portal.home') }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold rounded-xl text-sm transition-colors">
                    {{ $isAr ? 'العودة للرئيسية' : 'Back to Home' }}
                </a>
                <a href="mailto:vendors@noon.com"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium rounded-xl text-sm transition-colors">
                    {{ $isAr ? 'تواصل مع الدعم' : 'Contact Support' }}
                </a>
            </div>

        </div>
    </div>
@endsection