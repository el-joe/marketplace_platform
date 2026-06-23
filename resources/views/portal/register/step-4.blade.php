{{-- Step 4: Document Uploads --}}
<div class="space-y-5">
    <div>
        <h2 class="text-lg font-bold text-white">رفع الوثائق الرسمية</h2>
        <p class="text-sm text-gray-400 mt-1">الصيغ المقبولة: PDF، JPG، PNG — الحجم الأقصى: 5MB لكل ملف.</p>
    </div>

    {{-- Business License (Required) --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-sm font-semibold text-white">السجل التجاري <span class="text-red-400">*</span></p>
                <p class="text-xs text-gray-400">نسخة سارية المفعول من السجل التجاري</p>
            </div>
            <span x-show="documents.business_license" class="text-green-400 text-xs flex items-center gap-1" x-cloak>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تم الرفع
            </span>
        </div>
        <input type="file" id="doc_business_license" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            @change="uploadDocument($event, 'business_license')">
        <div @click="$refs.docBL.click()" x-ref="docBL" class="hidden"></div>
        <label for="doc_business_license"
            class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-5 px-4 cursor-pointer transition-colors"
            :class="documents.business_license ? 'border-green-600 bg-green-900/20' : 'border-gray-600 hover:border-yellow-400/60 bg-gray-800'">
            <template x-if="!documents.business_license">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto text-gray-500 mb-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="text-sm text-gray-400">انقر لاختيار الملف</span>
                </div>
            </template>
            <template x-if="documents.business_license">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-green-400 font-medium"
                        x-text="docFilenames.business_license || 'تم الرفع'"></span>
                    <button type="button" @click.stop="removeDocument('business_license')"
                        class="block mx-auto mt-1 text-xs text-red-400 hover:underline">حذف</button>
                </div>
            </template>
        </label>
        <p x-show="docErrors.business_license" x-text="docErrors.business_license" class="mt-1 text-xs text-red-400"
            x-cloak></p>
        <div x-show="docUploading.business_license" class="mt-2 flex items-center gap-2 text-xs text-yellow-400"
            x-cloak>
            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            جارٍ الرفع…
        </div>
    </div>

    {{-- Owner ID (Required) --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-sm font-semibold text-white">هوية المالك <span class="text-red-400">*</span></p>
                <p class="text-xs text-gray-400">صورة واضحة من وجه بطاقة الهوية أو الجواز</p>
            </div>
            <span x-show="documents.owner_id" class="text-green-400 text-xs flex items-center gap-1" x-cloak>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تم الرفع
            </span>
        </div>
        <input type="file" id="doc_owner_id" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            @change="uploadDocument($event, 'owner_id')">
        <label for="doc_owner_id"
            class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-5 px-4 cursor-pointer transition-colors"
            :class="documents.owner_id ? 'border-green-600 bg-green-900/20' : 'border-gray-600 hover:border-yellow-400/60 bg-gray-800'">
            <template x-if="!documents.owner_id">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto text-gray-500 mb-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="text-sm text-gray-400">انقر لاختيار الملف</span>
                </div>
            </template>
            <template x-if="documents.owner_id">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-green-400 font-medium"
                        x-text="docFilenames.owner_id || 'تم الرفع'"></span>
                    <button type="button" @click.stop="removeDocument('owner_id')"
                        class="block mx-auto mt-1 text-xs text-red-400 hover:underline">حذف</button>
                </div>
            </template>
        </label>
        <p x-show="docErrors.owner_id" x-text="docErrors.owner_id" class="mt-1 text-xs text-red-400" x-cloak></p>
        <div x-show="docUploading.owner_id" class="mt-2 flex items-center gap-2 text-xs text-yellow-400" x-cloak>
            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            جارٍ الرفع…
        </div>
    </div>

    {{-- Tax Certificate (Optional) --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-sm font-semibold text-white">الشهادة الضريبية <span
                        class="text-gray-500 text-xs font-normal">(اختياري)</span></p>
                <p class="text-xs text-gray-400">شهادة التسجيل الضريبي إن وُجدت</p>
            </div>
            <span x-show="documents.tax_certificate" class="text-green-400 text-xs flex items-center gap-1" x-cloak>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تم الرفع
            </span>
        </div>
        <input type="file" id="doc_tax_certificate" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            @change="uploadDocument($event, 'tax_certificate')">
        <label for="doc_tax_certificate"
            class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-4 px-4 cursor-pointer transition-colors"
            :class="documents.tax_certificate ? 'border-green-600 bg-green-900/20' : 'border-gray-600 hover:border-yellow-400/60 bg-gray-800'">
            <template x-if="!documents.tax_certificate">
                <span class="text-sm text-gray-400">انقر لاختيار الملف (اختياري)</span>
            </template>
            <template x-if="documents.tax_certificate">
                <div class="text-center">
                    <span class="text-sm text-green-400 font-medium"
                        x-text="docFilenames.tax_certificate || 'تم الرفع'"></span>
                    <button type="button" @click.stop="removeDocument('tax_certificate')"
                        class="block mx-auto mt-1 text-xs text-red-400 hover:underline">حذف</button>
                </div>
            </template>
        </label>
    </div>

    {{-- VAT Registration (Optional) --}}
    <div class="bg-gray-800/50 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-sm font-semibold text-white">شهادة تسجيل ضريبة القيمة المضافة <span
                        class="text-gray-500 text-xs font-normal">(اختياري)</span></p>
                <p class="text-xs text-gray-400">إن كنت مسجلاً في نظام الضريبة</p>
            </div>
            <span x-show="documents.vat_registration" class="text-green-400 text-xs flex items-center gap-1" x-cloak>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تم الرفع
            </span>
        </div>
        <input type="file" id="doc_vat_registration" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            @change="uploadDocument($event, 'vat_registration')">
        <label for="doc_vat_registration"
            class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl py-4 px-4 cursor-pointer transition-colors"
            :class="documents.vat_registration ? 'border-green-600 bg-green-900/20' : 'border-gray-600 hover:border-yellow-400/60 bg-gray-800'">
            <template x-if="!documents.vat_registration">
                <span class="text-sm text-gray-400">انقر لاختيار الملف (اختياري)</span>
            </template>
            <template x-if="documents.vat_registration">
                <div class="text-center">
                    <span class="text-sm text-green-400 font-medium"
                        x-text="docFilenames.vat_registration || 'تم الرفع'"></span>
                    <button type="button" @click.stop="removeDocument('vat_registration')"
                        class="block mx-auto mt-1 text-xs text-red-400 hover:underline">حذف</button>
                </div>
            </template>
        </label>
    </div>

    {{-- Required docs error --}}
    <p x-show="errors.documents" x-text="errors.documents?.[0]" class="text-sm text-red-400 font-medium" x-cloak></p>
</div>