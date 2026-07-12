{{-- Step 1: Account Credentials --}}
@php $isAr = $isAr ?? (session('locale', 'ar') === 'ar'); @endphp
<div class="space-y-4">
    <h2 class="text-lg font-bold text-white mb-4">{{ $isAr ? 'معلومات الحساب' : 'Account Information' }}</h2>

    {{-- Name --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'الاسم الكامل' : 'Full Name' }} <span
                class="text-red-400">*</span></label>
        <input type="text" x-model="form.name" autocomplete="name" placeholder="{{ $isAr ? 'محمد العلي' : 'John Smith' }}"
            class="w-full bg-gray-800 border text-white placeholder-gray-500 rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors"
            :class="errors.name ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
        <p x-show="errors.name" x-text="errors.name?.[0]" class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'البريد الإلكتروني التجاري' : 'Business Email' }} <span
                class="text-red-400">*</span></label>
        <input type="email" x-model="form.email" autocomplete="email" placeholder="example@business.com" dir="ltr"
            class="w-full bg-gray-800 border text-white placeholder-gray-500 rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors text-left"
            :class="errors.email ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
        <p x-show="errors.email" x-text="errors.email?.[0]" class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>

    {{-- Phone --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'رقم الهاتف' : 'Phone Number' }} <span
                class="text-red-400">*</span></label>
        <input type="tel" x-model="form.phone" autocomplete="tel" placeholder="+971 50 000 0000" dir="ltr"
            class="w-full bg-gray-800 border text-white placeholder-gray-500 rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors text-left"
            :class="errors.phone ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
        <p x-show="errors.phone" x-text="errors.phone?.[0]" class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>

    {{-- Country --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'الدولة' : 'Country' }} <span class="text-red-400">*</span></label>
        <select x-model="form.country_id"
            class="w-full bg-gray-800 border text-white rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors"
            :class="errors.country_id ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
            <option value="">{{ $isAr ? '— اختر الدولة —' : '— Select Country —' }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}">{{ $country->flag_emoji }} {{ $isAr ? $country->name_ar : ($country->name_en ?? $country->name_ar) }}</option>
            @endforeach
        </select>
        <p x-show="errors.country_id" x-text="errors.country_id?.[0]" class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>

    {{-- Password --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'كلمة المرور' : 'Password' }} <span
                class="text-red-400">*</span></label>
        <input type="password" x-model="form.password" autocomplete="new-password" placeholder="{{ $isAr ? '٨ أحرف على الأقل' : 'At least 8 characters' }}"
            dir="ltr"
            class="w-full bg-gray-800 border text-white placeholder-gray-500 rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors"
            :class="errors.password ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
        <p x-show="errors.password" x-text="errors.password?.[0]" class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>

    {{-- Password Confirmation --}}
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ $isAr ? 'تأكيد كلمة المرور' : 'Confirm Password' }} <span
                class="text-red-400">*</span></label>
        <input type="password" x-model="form.password_confirmation" autocomplete="new-password"
            placeholder="{{ $isAr ? 'أعد إدخال كلمة المرور' : 'Re-enter your password' }}" dir="ltr"
            class="w-full bg-gray-800 border text-white placeholder-gray-500 rounded-xl px-4 py-3 text-sm focus:outline-none transition-colors"
            :class="errors.password_confirmation ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-[#feee00]'">
        <p x-show="errors.password_confirmation" x-text="errors.password_confirmation?.[0]"
            class="mt-1 text-xs text-red-400" x-cloak></p>
    </div>
</div>