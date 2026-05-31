@extends('layouts.partner')

@section('title', 'إضافة قائمة منتج')
@section('page-title', 'إضافة قائمة منتج')

@push('scripts')
    @vite('resources/js/partner/listings.js')
    <script>
        window.LISTINGS_CREATE = {
            productSearchUrl: '{{ route('partner.listings.product-search') }}',
            storeUrl: '{{ route('partner.listings.store') }}',
            csrf: '{{ csrf_token() }}',
        };
    </script>
@endpush

@section('content')

    <div class="mb-4">
        <a href="{{ route('partner.listings.index') }}"
            class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 w-fit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            العودة للقوائم
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- LEFT: Step 1 — Product search --}}
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-6">
                <h3 class="font-semibold text-gray-800 mb-4">① ابحث عن المنتج</h3>
                <p class="text-xs text-gray-500 mb-4">ابحث عن منتج موجود في الكتالوج لإضافته كقائمة. لا يمكن للبائعين إنشاء
                    منتجات جديدة — يقوم الإدارة بذلك فقط.</p>

                <div class="relative mb-4">
                    <input type="text" id="product-search-input" placeholder="ابحث بالاسم، الرقم النموذجي..."
                        autocomplete="off"
                        class="w-full border border-gray-200 rounded-xl pr-4 pl-10 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                    <svg class="w-4 h-4 text-gray-400 absolute top-3 left-3 pointer-events-none" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Search results --}}
                <div id="product-search-results" class="space-y-2 max-h-96 overflow-y-auto">
                    <p class="text-xs text-gray-400 text-center py-6">ابدأ الكتابة للبحث...</p>
                </div>
            </div>
        </div>

        {{-- RIGHT: Step 2 — Listing form (shown after selecting a product+variant) --}}
        <div class="lg:col-span-7">

            {{-- Placeholder before selection --}}
            <div id="listing-form-placeholder"
                class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 flex flex-col items-center justify-center text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m8 4v10" />
                    </svg>
                </div>
                <p class="text-gray-500 text-sm font-medium">اختر منتجاً ونسخة من القائمة</p>
                <p class="text-gray-400 text-xs mt-1">سيظهر نموذج الإضافة هنا بعد الاختيار</p>
            </div>

            {{-- Actual form (hidden until product selected) --}}
            <div id="listing-form-container" class="hidden">
                <form id="listing-create-form" class="space-y-5">
                    {{-- Selected product info card --}}
                    <div class="bg-white rounded-2xl border border-gray-200 p-5">
                        <div id="selected-product-info" class="flex items-start gap-4">
                            <div id="selected-img"
                                class="w-14 h-14 rounded-xl border border-gray-100 bg-gray-50 overflow-hidden shrink-0 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m8 4v10" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p id="selected-product-name" class="font-semibold text-gray-900 text-sm"></p>
                                <p id="selected-variant-name" class="text-xs text-gray-500 mt-0.5"></p>
                                <p id="selected-sku" class="text-xs font-mono text-gray-400 mt-0.5"></p>
                            </div>
                            <button type="button" id="change-product-btn"
                                class="text-xs text-blue-600 hover:underline shrink-0">تغيير</button>
                        </div>
                        <input type="hidden" id="form-product-variant-id" name="product_variant_id">
                    </div>

                    {{-- Pricing & Details --}}
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
                        <h4 class="font-semibold text-gray-800 text-sm mb-2">② بيانات القائمة</h4>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">السعر <span
                                        class="text-red-500">*</span></label>
                                <input type="number" name="price" step="0.01" min="0.01" required
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">الدولة <span
                                        class="text-red-500">*</span></label>
                                <select name="country_id" required
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                                    <option value="">اختر...</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ $country->id === $countryId ? 'selected' : '' }}>
                                            {{ $country->name_ar ?: $country->name_en }} ({{ $country->currency_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">الحالة <span
                                        class="text-red-500">*</span></label>
                                <select name="condition" required
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                                    @foreach($conditions as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">نموذج التنفيذ <span
                                        class="text-red-500">*</span></label>
                                <select name="fulfillment_model" required
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                                    @foreach($fulfillmentModels as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">SKU الخاص بك</label>
                                <input type="text" name="vendor_sku" maxlength="100"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                                    placeholder="اختياري">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">الحد الأقصى للطلب</label>
                                <input type="number" name="max_order_quantity" min="1" max="9999"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                                    placeholder="بلا حد">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">حد المخزون المنخفض</label>
                            <input type="number" name="low_stock_threshold" min="0" value="5"
                                class="w-32 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                            <p class="text-xs text-gray-400 mt-1">سيظهر تحذير عندما يصل المخزون المتاح لهذا الرقم أو أقل</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                            <textarea name="vendor_notes" rows="2" maxlength="1000"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40 resize-none"
                                placeholder="ملاحظات داخلية (لن تُعرض للعملاء)"></textarea>
                        </div>
                    </div>

                    {{-- Warehouse & Initial Stock --}}
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
                        <h4 class="font-semibold text-gray-800 text-sm mb-2">③ المستودع والمخزون الأولي</h4>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">المستودع <span
                                    class="text-red-500">*</span></label>
                            <select name="warehouse_id" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                                <option value="">اختر المستودع...</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                        @if($warehouse->code) ({{ $warehouse->code }}) @endif
                                        —
                                        {{ match ($warehouse->type) { 'vendor' => 'مستودع خاص', 'platform' => 'مستودع المنصة', default => $warehouse->type} }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">الكمية الأولية <span
                                    class="text-red-500">*</span></label>
                            <input type="number" name="initial_quantity" min="0" value="0" required
                                class="w-40 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                            <p class="text-xs text-gray-400 mt-1">يمكنك إضافة المخزون لاحقاً من صفحة المنتج</p>
                        </div>
                    </div>

                    <div id="create-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-4"></div>

                    <button type="submit" id="create-submit-btn"
                        class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold py-3 rounded-xl transition-colors text-sm">
                        إنشاء القائمة
                    </button>

                </form>
            </div>
        </div>

    </div>

@endsection