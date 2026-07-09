@php
    $isAr = session('locale', 'ar') === 'ar';
    $success = session('advertise_request_success');
    $country = $country ?? 'ae';

    $fieldClass = fn (string $field) => 'w-full rounded-xl border px-4 py-3 text-sm text-gray-900 placeholder-gray-400 '
        . 'focus:outline-none focus:ring-2 focus:ring-yellow-400/30 transition-colors '
        . ($errors->has($field) ? 'border-red-400 focus:border-red-500' : 'border-gray-200 focus:border-yellow-400');
@endphp

<section class="bg-white">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 lg:py-20">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">

            {{-- Left: intro --}}
            <div class="text-center {{ $isAr ? 'lg:text-right' : 'lg:text-left' }} lg:sticky lg:top-28">
                <div class="w-16 h-16 rounded-2xl bg-yellow-50 flex items-center justify-center mx-auto {{ $isAr ? 'lg:mr-0 lg:ml-auto' : 'lg:ml-0 lg:mr-auto' }} mb-6">
                    <img src="https://advertise.noon.com/images/contactUs.png" alt="{{ $isAr ? 'اتصل بنا' : 'Contact us' }}"
                         loading="lazy" class="w-9 h-9 object-contain">
                </div>
                <p class="text-yellow-500 font-black text-xs sm:text-sm uppercase tracking-wider mb-3">
                    {{ $isAr ? 'اتصل بنا' : 'Contact us' }}
                </p>
                <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-gray-900 mb-4 text-pretty">
                    {{ $isAr ? 'لنبدأ محادثة' : "Let's start a conversation" }}
                </h1>
                <p class="text-gray-600 font-medium text-base sm:text-lg max-w-[48ch] mx-auto {{ $isAr ? 'lg:mr-0' : 'lg:ml-0' }}">
                    {{ $isAr
                        ? 'استفد من حلولنا الإعلانية اليوم للوصول إلى أهدافك التسويقية أو البيعية عبر المواقع بغض النظر عن ميزانيتك.'
                        : 'Leverage our ad solutions today to reach your marketing or sales objectives across locations and irrespective of your budget.' }}
                </p>
            </div>

            {{-- Right: form card --}}
            <div class="w-full rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 lg:p-10">

                @if ($success)
                    <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-semibold px-4 py-3">
                        {{ $isAr ? 'تم قبول طلبك. سيتم التواصل معك قريبًا.' : 'Your request has been accepted. We will contact you soon.' }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold px-4 py-3">
                        {{ $isAr ? 'حدث خطأ ما. يرجى المحاولة مرة أخرى لاحقًا.' : 'An error occurred. Please try again later.' }}
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.advertise.request.store', $country) }}" class="space-y-5">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-900 mb-1.5">
                            {{ $isAr ? 'الاسم' : 'Name' }}<span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255"
                               placeholder="{{ $isAr ? 'يرجى إدخال اسمك الكامل!' : 'Please enter your full name!' }}"
                               class="{{ $fieldClass('name') }}">
                        @error('name') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-900 mb-1.5">
                            {{ $isAr ? 'البريد الإلكتروني' : 'Email' }}<span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required dir="ltr" maxlength="255"
                               placeholder="{{ $isAr ? 'يرجى إدخال عنوان بريد إلكتروني صالح!' : 'Please enter a valid email address!' }}"
                               class="{{ $fieldClass('email') }} text-left">
                        @error('email') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Company name --}}
                    <div>
                        <label for="company_name" class="block text-sm font-bold text-gray-900 mb-1.5">
                            {{ $isAr ? 'اسم الشركة' : 'Company Name' }}<span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required maxlength="255"
                               placeholder="{{ $isAr ? 'يرجى إدخال اسم شركتك!' : 'Please enter your company name!' }}"
                               class="{{ $fieldClass('company_name') }}">
                        @error('company_name') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Phone (optional) --}}
                    <div>
                        <label for="phone" class="block text-sm font-bold text-gray-900 mb-1.5">
                            {{ $isAr ? 'رقم الجوال (اختياري)' : 'Phone Number (optional)' }}
                        </label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" maxlength="16"
                               placeholder="{{ $isAr ? 'أدخل رقم جوالك مع رمز الدولة، مثال: 9715XXXXXXXX' : 'Please enter your phone number with country code. eg: 9715XXXXXXXX' }}"
                               class="{{ $fieldClass('phone') }} text-left">
                        @error('phone') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-bold text-gray-900 mb-1.5">
                            {{ $isAr ? 'الوصف' : 'Description' }}<span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" name="description" rows="5" minlength="50" maxlength="1000" required
                                  placeholder="{{ $isAr ? 'يرجى إدخال تفاصيل الطلب في الوصف!' : 'Please enter your request details in the description!' }}"
                                  class="{{ $fieldClass('description') }} resize-none">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center bg-yellow-400 hover:bg-yellow-300 text-black
                                   font-black text-sm sm:text-base px-6 py-3 rounded-full transition-colors">
                        {{ $isAr ? 'قدّم' : 'Submit' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
