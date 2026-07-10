{{-- Step 5: Review & Submit --}}
@php $isAr = $isAr ?? (session('locale', 'ar') === 'ar'); @endphp
<div class="space-y-5">
    <div>
        <h2 class="text-lg font-bold text-white">{{ $isAr ? 'مراجعة البيانات وتأكيد الطلب' : 'Review & Confirm Your Application' }}</h2>
        <p class="text-sm text-gray-400 mt-1">{{ $isAr ? 'تحقق من بياناتك قبل الإرسال. يمكنك العودة للتعديل.' : 'Review your information before submitting. You can go back to make changes.' }}</p>
    </div>

    {{-- Account Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">{{ $isAr ? 'معلومات الحساب' : 'Account Information' }}</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">{{ $isAr ? 'الاسم' : 'Name' }}</div>
            <div class="text-white font-medium" x-text="form.name || '—'"></div>
            <div class="text-gray-400">{{ $isAr ? 'البريد الإلكتروني' : 'Email' }}</div>
            <div class="text-white font-medium break-all" x-text="form.email || '—'"></div>
            <div class="text-gray-400">{{ $isAr ? 'رقم الهاتف' : 'Phone Number' }}</div>
            <div class="text-white font-medium" x-text="form.phone || '—'"></div>
        </div>
    </div>

    {{-- Store Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">{{ $isAr ? 'معلومات المتجر' : 'Store Information' }}</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">{{ $isAr ? 'اسم المتجر' : 'Store Name' }}</div>
            <div class="text-white font-medium" x-text="form.store_name || '—'"></div>
            <div class="text-gray-400">{{ $isAr ? 'الرابط المختصر' : 'Store Slug' }}</div>
            <div class="text-white font-medium" x-text="form.store_slug || '—'"></div>
            <div class="text-gray-400">{{ $isAr ? 'نوع النشاط' : 'Business Type' }}</div>
            <div class="text-white font-medium" x-text="businessTypeLabel(form.business_type)"></div>
            <div class="text-gray-400">{{ $isAr ? 'الاسم التجاري' : 'Business Name' }}</div>
            <div class="text-white font-medium" x-text="form.business_name || '—'"></div>
        </div>
    </div>

    {{-- Address Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">{{ $isAr ? 'العنوان التجاري' : 'Business Address' }}</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">{{ $isAr ? 'الشارع' : 'Street' }}</div>
            <div class="text-white font-medium" x-text="form.street_address || '—'"></div>
            <div class="text-gray-400">{{ $isAr ? 'المدينة' : 'City' }}</div>
            <div class="text-white font-medium" x-text="selectedCityName()"></div>
        </div>
    </div>

    {{-- Documents Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">{{ $isAr ? 'الوثائق المرفوعة' : 'Uploaded Documents' }}</h3>
        <ul class="space-y-1.5 text-sm">
            <template x-for="doc in docTypes" :key="doc.code">
                <li class="flex items-center gap-2">
                    <span :class="documents[doc.code] ? 'text-green-400' : (doc.requirement_level === 'mandatory' ? 'text-red-400' : 'text-gray-500')">
                        <template x-if="documents[doc.code]">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="!documents[doc.code] && doc.requirement_level === 'mandatory'">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </template>
                        <template x-if="!documents[doc.code] && doc.requirement_level === 'optional'">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </template>
                    </span>
                    <span :class="doc.requirement_level === 'mandatory' ? 'text-gray-300' : 'text-gray-400'" x-text="doc.name_ar"></span>
                    <span x-show="!documents[doc.code] && doc.requirement_level === 'mandatory'" class="text-red-400 text-xs">{{ $isAr ? '(مطلوب)' : '(required)' }}</span>
                    <span x-show="!documents[doc.code] && doc.requirement_level === 'optional'" class="text-gray-500 text-xs">{{ $isAr ? '(اختياري)' : '(optional)' }}</span>
                </li>
            </template>
        </ul>
    </div>

    {{-- Missing required docs warning --}}
    <div x-show="docTypes.some(d => d.requirement_level === 'mandatory' && !documents[d.code])"
        class="bg-amber-900/30 border border-amber-700 rounded-xl p-3 text-sm text-amber-300 flex items-center gap-2"
        x-cloak>
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span>{{ $isAr ? 'يرجى العودة للخطوة السابقة ورفع الوثائق المطلوبة قبل الإرسال.' : 'Please go back to the previous step and upload the required documents before submitting.' }}</span>
    </div>

    <div class="border-t border-gray-700 pt-4 space-y-3">
        <h3 class="text-sm font-semibold text-white">{{ $isAr ? 'الموافقة على الشروط' : 'Agree to Terms' }}</h3>

        {{-- Terms --}}
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" x-model="form.terms_agreed"
                class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-800 text-yellow-400 focus:ring-yellow-400 focus:ring-offset-gray-900">
            <span class="text-sm text-gray-300 group-hover:text-white transition-colors leading-relaxed">
                @if($isAr)
                    أوافق على
                    <a href="#" class="text-yellow-400 hover:underline">الشروط والأحكام</a>
                    الخاصة ببائعي نون
                @else
                    I agree to noon's
                    <a href="#" class="text-yellow-400 hover:underline">Terms and Conditions</a>
                    for sellers
                @endif
            </span>
        </label>
        <p x-show="errors.terms_agreed" x-text="errors.terms_agreed?.[0]" class="text-xs text-red-400 mr-7" x-cloak></p>

        {{-- Privacy --}}
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" x-model="form.privacy_agreed"
                class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-800 text-yellow-400 focus:ring-yellow-400 focus:ring-offset-gray-900">
            <span class="text-sm text-gray-300 group-hover:text-white transition-colors leading-relaxed">
                @if($isAr)
                    أوافق على
                    <a href="#" class="text-yellow-400 hover:underline">سياسة الخصوصية</a>
                    وأذن لنون بمعالجة بياناتي التجارية
                @else
                    I agree to the
                    <a href="#" class="text-yellow-400 hover:underline">Privacy Policy</a>
                    and authorize noon to process my business data
                @endif
            </span>
        </label>
        <p x-show="errors.privacy_agreed" x-text="errors.privacy_agreed?.[0]" class="text-xs text-red-400 mr-7" x-cloak>
        </p>
    </div>
</div>