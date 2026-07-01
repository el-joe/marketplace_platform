@extends('layouts.partner')

@section('title', 'عرض تسويقي جديد')
@section('page-title', 'عروض الحملات التسويقية')

@push('scripts')
    @vite('resources/js/partner/campaign-offers.js')
    <script>
        window.CAMPAIGN_OFFER_CONFIG = {
            storeUrl:      "{{ route('partner.campaign-offers.store') }}",
            listingsUrl:   "{{ route('partner.listings.datatable') }}",
            mode:          'create',
        };
    </script>
@endpush

@section('content')
<div class="px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('partner.campaign-offers.index') }}" class="hover:text-primary-600">عروض الحملات</a>
        <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        <span class="text-gray-800 font-medium">عرض جديد</span>
    </nav>

    <div class="max-w-3xl mx-auto">

        {{-- Step indicators --}}
        <div class="mb-8 flex items-center justify-between" id="step-indicators">
            @foreach ([
                ['num' => 1, 'label' => 'تفاصيل الحملة'],
                ['num' => 2, 'label' => 'العمولة والميزانية'],
                ['num' => 3, 'label' => 'المنتجات'],
            ] as $step)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <div class="step-circle h-9 w-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors
                                    {{ $loop->first ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-300 bg-white text-gray-400' }}"
                             data-step="{{ $step['num'] }}">
                            {{ $step['num'] }}
                        </div>
                        <span class="text-sm font-medium step-label
                                     {{ $loop->first ? 'text-primary-600' : 'text-gray-400' }}"
                              data-step="{{ $step['num'] }}">{{ $step['label'] }}</span>
                    </div>
                    @if (!$loop->last)
                        <div class="step-connector flex-1 mx-4 h-0.5 bg-gray-200 transition-colors" data-after="{{ $step['num'] }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="campaign-offer-form" method="POST" action="{{ route('partner.campaign-offers.store') }}" class="space-y-6" novalidate>
            @csrf

            {{-- ═══ STEP 1: Campaign Details ═══ --}}
            <div class="step-panel" data-panel="1">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                    <div class="px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-900">تفاصيل الحملة</h2>
                    </div>
                    <div class="px-6 py-5 space-y-5">

                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="name">
                                اسم الحملة <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="255" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                   placeholder="مثال: رمضان 2025 – ترويج المنتجات الجديدة">
                        </div>

                        {{-- Campaign Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="campaign_type">
                                نوع الحملة <span class="text-red-500">*</span>
                            </label>
                            <select id="campaign_type" name="campaign_type" required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="">— اختر النوع —</option>
                                <option value="product_promotion" {{ old('campaign_type') === 'product_promotion' ? 'selected' : '' }}>ترويج منتج</option>
                                <option value="store_promotion"   {{ old('campaign_type') === 'store_promotion'   ? 'selected' : '' }}>ترويج المتجر</option>
                                <option value="brand_deal"        {{ old('campaign_type') === 'brand_deal'        ? 'selected' : '' }}>اتفاقية براند</option>
                                <option value="product_specific"  {{ old('campaign_type') === 'product_specific'  ? 'selected' : '' }}>منتج محدد</option>
                                <option value="flash_sale"        {{ old('campaign_type') === 'flash_sale'        ? 'selected' : '' }}>تخفيض سريع (Flash Sale)</option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="description">
                                وصف الحملة
                            </label>
                            <textarea id="description" name="description" rows="3" maxlength="2000"
                                      class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                      placeholder="اشرح هدف الحملة للمسوّق…">{{ old('description') }}</textarea>
                        </div>

                        {{-- Requirements --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="requirements">
                                متطلبات المسوّق
                            </label>
                            <textarea id="requirements" name="requirements" rows="3" maxlength="2000"
                                      class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                      placeholder="مثال: يجب أن يكون لدى المسوّق 10,000 متابع على الأقل…">{{ old('requirements') }}</textarea>
                        </div>

                        {{-- Dates row --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="starts_at">
                                    تاريخ البداية <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" required
                                       min="{{ date('Y-m-d') }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="ends_at">
                                    تاريخ الانتهاء <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" required
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="invitation_deadline">
                                    آخر موعد للقبول
                                </label>
                                <input type="date" id="invitation_deadline" name="invitation_deadline" value="{{ old('invitation_deadline') }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <p class="mt-1 text-xs text-gray-400">يجب أن يكون قبل تاريخ البداية</p>
                            </div>
                        </div>

                        {{-- Attribution + WhatsApp row --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="attribution_model">
                                    نموذج الإسناد <span class="text-red-500">*</span>
                                </label>
                                <select id="attribution_model" name="attribution_model" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    <option value="last_click"  {{ old('attribution_model', 'last_click') === 'last_click'  ? 'selected' : '' }}>Last Click – آخر نقرة</option>
                                    <option value="first_click" {{ old('attribution_model') === 'first_click' ? 'selected' : '' }}>First Click – أول نقرة</option>
                                    <option value="linear"      {{ old('attribution_model') === 'linear'      ? 'selected' : '' }}>Linear – توزيع متساوٍ</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-3 pt-6">
                                <button type="button" id="whatsapp-toggle" role="switch"
                                        aria-checked="{{ old('whatsapp_sharing_enabled') ? 'true' : 'false' }}"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                                               {{ old('whatsapp_sharing_enabled') ? 'bg-primary-600' : 'bg-gray-200' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform
                                                 {{ old('whatsapp_sharing_enabled') ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                                <input type="hidden" name="whatsapp_sharing_enabled" id="whatsapp_sharing_enabled_input" value="{{ old('whatsapp_sharing_enabled') ? '1' : '0' }}">
                                <label class="text-sm font-medium text-gray-700 cursor-pointer" for="whatsapp-toggle">
                                    تفعيل المشاركة عبر واتساب
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ═══ STEP 2: Commission & Budget ═══ --}}
            <div class="step-panel hidden" data-panel="2">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                    <div class="px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-900">العمولة والميزانية</h2>
                    </div>
                    <div class="px-6 py-5 space-y-5">

                        {{-- Commission rate --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="offered_commission_rate">
                                نسبة العمولة المقدَّمة للمسوّق <span class="text-red-500">*</span>
                            </label>
                            <div class="relative max-w-xs">
                                <input type="number" id="offered_commission_rate" name="offered_commission_rate"
                                       value="{{ old('offered_commission_rate') }}"
                                       min="0" max="100" step="0.01" required
                                       class="w-full rounded-lg border border-gray-300 ps-3 pe-10 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <span class="absolute inset-y-0 end-3 flex items-center text-gray-400 text-sm">%</span>
                            </div>
                            <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                أنت تقدّم <strong id="commission-preview">0%</strong> من قيمة كل عملية بيع للمسوّق.
                                عمولة المنصة القياسية تُطبَّق بشكل منفصل فوق هذه النسبة.
                            </p>
                        </div>

                        {{-- Commission type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                نوع العمولة <span class="text-red-500">*</span>
                            </label>
                            <div class="flex flex-wrap gap-3">
                                @foreach ([
                                    ['value' => 'percentage',      'label' => 'نسبة مئوية من المبيعات'],
                                    ['value' => 'flat_per_order',  'label' => 'مبلغ ثابت لكل طلب'],
                                    ['value' => 'flat_per_click',  'label' => 'مبلغ ثابت لكل نقرة'],
                                ] as $ct)
                                    <label class="flex items-center gap-2 cursor-pointer rounded-lg border px-4 py-2.5 text-sm transition-colors has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                        <input type="radio" name="commission_type" value="{{ $ct['value'] }}"
                                               {{ old('commission_type', 'percentage') === $ct['value'] ? 'checked' : '' }}
                                               class="text-primary-600 focus:ring-primary-500">
                                        {{ $ct['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Budgets --}}
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="budget_per_marketer_cents_display">
                                    ميزانية لكل مسوّق (اختياري)
                                </label>
                                <div class="relative">
                                    <input type="number" id="budget_per_marketer_cents_display" name="budget_per_marketer_cents_display"
                                           value="{{ old('budget_per_marketer_cents_display') }}"
                                           min="0" step="0.01"
                                           class="w-full rounded-lg border border-gray-300 ps-3 pe-14 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                           placeholder="0.00">
                                    <span class="absolute inset-y-0 end-3 flex items-center text-gray-400 text-sm">ر.س</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="total_budget_cents_display">
                                    إجمالي الميزانية (اختياري)
                                </label>
                                <div class="relative">
                                    <input type="number" id="total_budget_cents_display" name="total_budget_cents_display"
                                           value="{{ old('total_budget_cents_display') }}"
                                           min="0" step="0.01"
                                           class="w-full rounded-lg border border-gray-300 ps-3 pe-14 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                           placeholder="0.00">
                                    <span class="absolute inset-y-0 end-3 flex items-center text-gray-400 text-sm">ر.س</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ═══ STEP 3: Products ═══ --}}
            <div class="step-panel hidden" data-panel="3">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                    <div class="px-6 py-4">
                        <h2 class="text-base font-semibold text-gray-900">اختر المنتجات</h2>
                        <p class="text-xs text-gray-500 mt-0.5">ابحث عن منتجاتك النشطة وأضفها للحملة. يمكنك تحديد عمولة مخصصة لكل منتج.</p>
                    </div>
                    <div class="px-6 py-5 space-y-4">

                        {{-- Product search --}}
                        <div class="relative">
                            <input type="text" id="product-search-input" placeholder="ابحث باسم المنتج…"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 ps-10 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                            <svg class="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                        </div>

                        {{-- Search results --}}
                        <div id="product-results" class="hidden rounded-lg border border-gray-200 divide-y divide-gray-100 max-h-60 overflow-y-auto">
                            {{-- Populated by JS --}}
                        </div>

                        {{-- Selected products pills --}}
                        <div id="selected-products" class="space-y-2">
                            {{-- Populated by JS --}}
                        </div>

                        <p id="no-products-msg" class="hidden text-xs text-red-600">يجب إضافة منتج واحد على الأقل.</p>

                        {{-- Hidden inputs populated by JS --}}
                        <div id="listing-inputs"></div>
                        <div id="commission-override-inputs"></div>

                    </div>
                </div>

                {{-- Review summary --}}
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-6 py-5 space-y-2 text-sm">
                    <h3 class="font-semibold text-gray-800 mb-3">ملخص العرض</h3>
                    <div class="grid grid-cols-2 gap-2 text-gray-600">
                        <span>الاسم:</span>        <span id="summary-name" class="font-medium text-gray-900">—</span>
                        <span>النوع:</span>         <span id="summary-type" class="font-medium text-gray-900">—</span>
                        <span>العمولة:</span>       <span id="summary-commission" class="font-medium text-gray-900">—</span>
                        <span>فترة الحملة:</span>   <span id="summary-dates" class="font-medium text-gray-900">—</span>
                        <span>عدد المنتجات:</span>  <span id="summary-products" class="font-medium text-gray-900">—</span>
                    </div>
                </div>
            </div>

            {{-- Navigation buttons --}}
            <div class="flex items-center justify-between pt-2">
                <button type="button" id="btn-prev"
                        class="hidden inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                    </svg>
                    السابق
                </button>
                <div class="flex items-center gap-3 ms-auto">
                    <button type="button" id="btn-next"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                        التالي
                        <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>
                    <button type="submit" id="btn-save-draft"
                            class="hidden inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        حفظ كمسودة
                    </button>
                    <button type="submit" id="btn-save-invite"
                            class="hidden inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                        حفظ ودعوة مسوّقين
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection
