{{-- Step 5: Review & Submit --}}
<div class="space-y-5">
    <div>
        <h2 class="text-lg font-bold text-white">مراجعة البيانات وتأكيد الطلب</h2>
        <p class="text-sm text-gray-400 mt-1">تحقق من بياناتك قبل الإرسال. يمكنك العودة للتعديل.</p>
    </div>

    {{-- Account Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">معلومات الحساب</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">الاسم</div>
            <div class="text-white font-medium" x-text="form.name || '—'"></div>
            <div class="text-gray-400">البريد الإلكتروني</div>
            <div class="text-white font-medium break-all" x-text="form.email || '—'"></div>
            <div class="text-gray-400">رقم الهاتف</div>
            <div class="text-white font-medium" x-text="form.phone || '—'"></div>
        </div>
    </div>

    {{-- Store Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">معلومات المتجر</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">اسم المتجر</div>
            <div class="text-white font-medium" x-text="form.store_name || '—'"></div>
            <div class="text-gray-400">الرابط المختصر</div>
            <div class="text-white font-medium" x-text="form.store_slug || '—'"></div>
            <div class="text-gray-400">نوع النشاط</div>
            <div class="text-white font-medium" x-text="businessTypeLabel(form.business_type)"></div>
            <div class="text-gray-400">الاسم التجاري</div>
            <div class="text-white font-medium" x-text="form.business_name || '—'"></div>
        </div>
    </div>

    {{-- Address Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700 space-y-2">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">العنوان التجاري</h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div class="text-gray-400">الشارع</div>
            <div class="text-white font-medium" x-text="form.street_address || '—'"></div>
            <div class="text-gray-400">المدينة</div>
            <div class="text-white font-medium" x-text="selectedCityName()"></div>
        </div>
    </div>

    {{-- Documents Summary --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-3">الوثائق المرفوعة</h3>
        <ul class="space-y-1.5 text-sm">
            <li class="flex items-center gap-2">
                <span :class="documents.business_license ? 'text-green-400' : 'text-red-400'">
                    <template x-if="documents.business_license"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></template>
                    <template x-if="!documents.business_license"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></template>
                </span>
                <span class="text-gray-300">السجل التجاري</span>
                <span x-show="!documents.business_license" class="text-red-400 text-xs">(مطلوب)</span>
            </li>
            <li class="flex items-center gap-2">
                <span :class="documents.owner_id ? 'text-green-400' : 'text-red-400'">
                    <template x-if="documents.owner_id"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></template>
                    <template x-if="!documents.owner_id"><svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></template>
                </span>
                <span class="text-gray-300">هوية المالك</span>
                <span x-show="!documents.owner_id" class="text-red-400 text-xs">(مطلوب)</span>
            </li>
            <li class="flex items-center gap-2">
                <span :class="documents.tax_certificate ? 'text-green-400' : 'text-gray-500'">
                    <template x-if="documents.tax_certificate"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></template>
                    <template x-if="!documents.tax_certificate"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg></template>
                </span>
                <span class="text-gray-400">الشهادة الضريبية</span>
                <span x-show="!documents.tax_certificate" class="text-gray-500 text-xs">(اختياري)</span>
            </li>
            <li class="flex items-center gap-2">
                <span :class="documents.vat_registration ? 'text-green-400' : 'text-gray-500'">
                    <template x-if="documents.vat_registration"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></template>
                    <template x-if="!documents.vat_registration"><svg class="w-4 h-4 inline" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg></template>
                </span>
                <span class="text-gray-400">شهادة ضريبة القيمة المضافة</span>
                <span x-show="!documents.vat_registration" class="text-gray-500 text-xs">(اختياري)</span>
            </li>
        </ul>
    </div>

    {{-- Missing required docs warning --}}
    <div x-show="!documents.business_license || !documents.owner_id"
        class="bg-amber-900/30 border border-amber-700 rounded-xl p-3 text-sm text-amber-300 flex items-center gap-2"
        x-cloak>
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span>يرجى العودة للخطوة السابقة ورفع الوثائق المطلوبة قبل الإرسال.</span>
    </div>

    <div class="border-t border-gray-700 pt-4 space-y-3">
        <h3 class="text-sm font-semibold text-white">الموافقة على الشروط</h3>

        {{-- Terms --}}
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" x-model="form.terms_agreed"
                class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-800 text-yellow-400 focus:ring-yellow-400 focus:ring-offset-gray-900">
            <span class="text-sm text-gray-300 group-hover:text-white transition-colors leading-relaxed">
                أوافق على
                <a href="#" class="text-yellow-400 hover:underline">الشروط والأحكام</a>
                الخاصة ببائعي نون
            </span>
        </label>
        <p x-show="errors.terms_agreed" x-text="errors.terms_agreed?.[0]" class="text-xs text-red-400 mr-7" x-cloak></p>

        {{-- Privacy --}}
        <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" x-model="form.privacy_agreed"
                class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-800 text-yellow-400 focus:ring-yellow-400 focus:ring-offset-gray-900">
            <span class="text-sm text-gray-300 group-hover:text-white transition-colors leading-relaxed">
                أوافق على
                <a href="#" class="text-yellow-400 hover:underline">سياسة الخصوصية</a>
                وأذن لنون بمعالجة بياناتي التجارية
            </span>
        </label>
        <p x-show="errors.privacy_agreed" x-text="errors.privacy_agreed?.[0]" class="text-xs text-red-400 mr-7" x-cloak>
        </p>
    </div>
</div>