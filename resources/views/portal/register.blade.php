@extends('layouts.portal')

@section('title', session('locale', 'ar') === 'ar' ? 'سجّل كبائع' : 'Register as Seller')

@section('content')
    @php $isAr = session('locale', 'ar') === 'ar'; @endphp

    <section class="min-h-screen bg-gray-950 flex items-center justify-center py-16 px-4">
        <div class="w-full max-w-lg">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <a href="{{ route('portal.home') }}" class="inline-flex items-center gap-2">
                    <span class="bg-yellow-400 text-gray-950 font-black text-2xl px-3 py-1 rounded">noon</span>
                    <span class="text-white text-base font-semibold">{{ $isAr ? 'للبائعين' : 'Sellers' }}</span>
                </a>
                <h1 class="mt-6 text-2xl font-black text-white">
                    {{ $isAr ? 'سجّل كبائع على نون' : 'Register as a Noon Seller' }}
                </h1>
                <p class="mt-2 text-gray-400 text-sm">
                    {{ $isAr ? 'مجاني تماماً. ابدأ في دقائق.' : 'Completely free. Start in minutes.' }}
                </p>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-900/50 border border-green-500/40 text-green-400 rounded-2xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Form card --}}
            <div class="bg-gray-900 border border-gray-800 rounded-3xl p-8">
                <form method="POST" action="{{ route('portal.register.submit') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5 {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'اسمك الكامل' : 'Full Name' }}
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="{{ $isAr ? 'محمد العلي' : 'John Doe' }}" class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                      rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-400
                                      focus:ring-1 focus:ring-yellow-400 {{ $isAr ? 'text-right' : '' }}">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5 {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'البريد الإلكتروني التجاري' : 'Business Email' }}
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            placeholder="{{ $isAr ? 'example@business.com' : 'example@business.com' }}" class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                      rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-400
                                      focus:ring-1 focus:ring-yellow-400" dir="ltr">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5 {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'رقم الهاتف' : 'Phone Number' }}
                        </label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+971 50 000 0000" class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                      rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-400
                                      focus:ring-1 focus:ring-yellow-400" dir="ltr">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5 {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'اسم المتجر' : 'Store Name' }}
                        </label>
                        <input type="text" name="store_name" value="{{ old('store_name') }}"
                            placeholder="{{ $isAr ? 'متجر الأناقة' : 'My Awesome Store' }}" class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                      rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-400
                                      focus:ring-1 focus:ring-yellow-400 {{ $isAr ? 'text-right' : '' }}">
                        @error('store_name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5 {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'كلمة المرور' : 'Password' }}
                        </label>
                        <input type="password" name="password" required autocomplete="new-password"
                            placeholder="{{ $isAr ? '٨ أحرف على الأقل' : 'At least 8 characters' }}" class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                      rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-400
                                      focus:ring-1 focus:ring-yellow-400" dir="ltr">
                        @error('password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-start gap-3 {{ $isAr ? 'flex-row-reverse' : '' }}">
                        <input type="checkbox" name="terms" id="terms" required class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-800 text-yellow-400
                                      focus:ring-yellow-400 focus:ring-offset-gray-900">
                        <label for="terms" class="text-xs text-gray-400 leading-relaxed {{ $isAr ? 'text-right' : '' }}">
                            {{ $isAr ? 'أوافق على' : 'I agree to the' }}
                            <a href="#"
                                class="text-yellow-400 hover:underline">{{ $isAr ? 'الشروط والأحكام' : 'Terms & Conditions' }}</a>
                            {{ $isAr ? 'و' : 'and' }}
                            <a href="#"
                                class="text-yellow-400 hover:underline">{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}</a>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black
                                   py-3.5 rounded-xl transition-colors text-base shadow-lg shadow-yellow-400/20">
                        {{ $isAr ? 'إنشاء الحساب' : 'Create Account' }}
                    </button>

                </form>

                <p class="mt-6 text-center text-sm text-gray-500">
                    {{ $isAr ? 'لديك حساب بالفعل؟' : 'Already have an account?' }}
                    <a href="{{ route('partner.login') }}" class="text-yellow-400 hover:underline font-medium">
                        {{ $isAr ? 'تسجيل الدخول' : 'Sign In' }}
                    </a>
                </p>
            </div>

        </div>
    </section>

    @include('portal.partials.cta-footer')
@endsection