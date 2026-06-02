@extends('layouts.partner')

@section('title', 'نظام التوصيل')

@push('styles')
    @vite(['resources/css/app.css', 'resources/js/partner/app.js'])
@endpush

@section('content')

<div class="max-w-6xl mx-auto py-6 px-4" dir="rtl" x-data="fulfillmentApp()">

    {{-- ─── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">نظام التوصيل والتخزين</h1>
        <p class="text-sm text-gray-500 mt-0.5">إدارة طريقة تخزين وشحن منتجاتك عبر المنصة.</p>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-indigo-500 uppercase tracking-wide mb-1">اكسبريس (FBN)</p>
            <p class="text-2xl font-extrabold text-indigo-700">{{ $stats['fbn_count'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">منتج</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-blue-500 uppercase tracking-wide mb-1">نظام التاجر (FBP)</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ $stats['fbp_count'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">منتج</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-green-500 uppercase tracking-wide mb-1">نظام الماركت</p>
            <p class="text-2xl font-extrabold text-green-700">{{ $stats['marketplace_count'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">منتج</p>
        </div>
        <div class="bg-white rounded-2xl border border-yellow-100 bg-yellow-50 p-4 text-center">
            <p class="text-xs text-yellow-600 uppercase tracking-wide mb-1">طلبات معلقة</p>
            <p class="text-2xl font-extrabold text-yellow-700">{{ $stats['pending_requests'] }}</p>
            <p class="text-xs text-yellow-500 mt-0.5">طلبات FBN</p>
        </div>
    </div>

    {{-- ─── Tab Nav ──────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-6 w-fit">
        <button @click="tab='fbn'"
                :class="tab==='fbn' ? 'bg-white shadow text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-sm transition-all">
            🚀 اكسبريس (FBN)
        </button>
        <button @click="tab='fbp'"
                :class="tab==='fbp' ? 'bg-white shadow text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-sm transition-all">
            🏪 نظام التاجر (FBP)
        </button>
        <button @click="tab='marketplace'"
                :class="tab==='marketplace' ? 'bg-white shadow text-green-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-sm transition-all">
            🌐 نظام الماركت
        </button>
        <button @click="tab='fees'; loadFees()"
                :class="tab==='fees' ? 'bg-white shadow text-orange-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-sm transition-all">
            💰 رسوم التخزين
        </button>
    </div>

    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    {{-- TAB: FBN                                                                --}}
    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    <div x-show="tab==='fbn'" x-cloak class="space-y-5">

        {{-- FBN explainer --}}
        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex gap-3">
            <span class="text-2xl">🚀</span>
            <div>
                <p class="font-semibold text-indigo-800">اكسبريس FBN — Fulfilled By Noon</p>
                <p class="text-sm text-indigo-600 mt-0.5">
                    أرسل منتجاتك إلى مستودعاتنا وسنتولى التخزين والشحن. يتطلب موافقة الإدارة على كل طلب شحن.
                </p>
            </div>
        </div>

        {{-- Submit inbound request button --}}
        <div class="flex justify-between items-center">
            <h2 class="text-base font-bold text-gray-700">طلبات الإرسال للمستودع</h2>
            @if($fbnListings->isNotEmpty())
                <button type="button" @click="showFbnForm=true" class="btn btn-primary btn-sm">
                    + طلب إرسال جديد
                </button>
            @endif
        </div>

        {{-- FBN listings overview --}}
        @if($fbnListings->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-10 text-center text-gray-400">
                <p class="text-2xl mb-2">📦</p>
                <p class="font-medium">لا توجد قوائم FBN حتى الآن.</p>
                <p class="text-sm mt-1">قم بتحويل قائمة إلى نظام "اكسبريس" من صفحة القوائم.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($fbnListings as $listing)
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $listing->productVariant?->product?->name_ar ?? $listing->productVariant?->product?->name_en ?? 'منتج' }}</p>
                            <p class="text-xs text-gray-400">SKU: {{ $listing->vendor_sku ?? '—' }}</p>
                        </div>
                        <span class="badge badge-indigo text-xs">FBN</span>
                    </div>
                    @foreach($listing->warehouseInventories as $inv)
                        @if($inv->warehouse?->type === 'platform_fbn')
                        <div class="grid grid-cols-3 gap-1 text-center text-xs mt-2 bg-gray-50 rounded-lg p-2">
                            <div><p class="text-gray-400">في المستودع</p><p class="font-bold text-gray-800">{{ $inv->quantity_on_hand }}</p></div>
                            <div><p class="text-gray-400">في الطريق</p><p class="font-bold text-blue-600">{{ $inv->quantity_inbound }}</p></div>
                            <div><p class="text-gray-400">متاح</p><p class="font-bold text-green-600">{{ $inv->quantity_available }}</p></div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endforeach
            </div>

            {{-- Inbound requests table --}}
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">طلبات الإرسال</div>
                <div id="fbn-requests-container" class="p-4 text-center text-gray-400 text-sm">
                    جارٍ التحميل…
                </div>
            </div>
        @endif

        {{-- FBN Submit form (Alpine toggle) --}}
        <div x-show="showFbnForm" x-cloak class="bg-white rounded-2xl border border-indigo-100 p-5">
            <h3 class="font-bold text-gray-800 mb-4">طلب إرسال جديد للمستودع</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label-sm">القائمة (المنتج) <span class="text-red-500">*</span></label>
                    <select id="fbn-listing" class="form-select w-full text-sm">
                        <option value="">— اختر قائمة —</option>
                        @foreach($fbnListings as $listing)
                            <option value="{{ $listing->id }}">
                                {{ $listing->productVariant?->product?->name_ar ?? $listing->productVariant?->product?->name_en }}
                                ({{ $listing->vendor_sku ?? substr($listing->id, 0, 8) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-sm">المستودع <span class="text-red-500">*</span></label>
                    <select id="fbn-warehouse" class="form-select w-full text-sm">
                        <option value="">— اختر مستودع —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-sm">الكمية المطلوب إرسالها <span class="text-red-500">*</span></label>
                    <input type="number" id="fbn-qty" class="form-input w-full text-sm" min="1" placeholder="مثال: 50">
                </div>
                <div>
                    <label class="label-sm">ملاحظات (اختياري)</label>
                    <input type="text" id="fbn-notes" class="form-input w-full text-sm" placeholder="أي تعليمات خاصة...">
                </div>
            </div>
            <div class="flex gap-3 justify-end mt-4">
                <button type="button" @click="showFbnForm=false" class="btn btn-ghost btn-sm">إلغاء</button>
                <button type="button" @click="submitFbnRequest()" class="btn btn-primary btn-sm">إرسال الطلب</button>
            </div>
        </div>

    </div>{{-- end FBN tab --}}

    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    {{-- TAB: FBP                                                                --}}
    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    <div x-show="tab==='fbp'" x-cloak class="space-y-5">

        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex gap-3">
            <span class="text-2xl">🏪</span>
            <div>
                <p class="font-semibold text-blue-800">نظام التاجر FBP — Fulfilled By Partner</p>
                <p class="text-sm text-blue-600 mt-0.5">
                    منتجاتك معروضة على المنصة لكن تخزينها وشحنها مسؤوليتك. أنت المتحكم في المخزون.
                </p>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <h2 class="text-base font-bold text-gray-700">مخزون FBP</h2>
            <button type="button" @click="loadFbpInventory()" class="btn btn-ghost btn-xs">🔄 تحديث</button>
        </div>

        @if($fbpListings->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-10 text-center text-gray-400">
                <p class="text-2xl mb-2">🏪</p>
                <p class="font-medium">لا توجد قوائم FBP حتى الآن.</p>
            </div>
        @else
            <div id="fbp-inventory-container">
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-right">المنتج</th>
                                <th class="px-4 py-3 text-right">المستودع</th>
                                <th class="px-4 py-3 text-right">في المخزن</th>
                                <th class="px-4 py-3 text-right">متاح</th>
                                <th class="px-4 py-3 text-right">محجوز</th>
                                <th class="px-4 py-3 text-right">الموقع</th>
                                <th class="px-4 py-3 text-right">نقطة إعادة الطلب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fbpListings as $listing)
                            @foreach($listing->warehouseInventories as $inv)
                            <tr class="border-t border-gray-50 hover:bg-gray-50/50
                                {{ $inv->reorder_point && $inv->quantity_available <= $inv->reorder_point ? 'bg-red-50/30' : '' }}">
                                <td class="px-4 py-3 font-medium text-gray-800 text-right text-xs">
                                    {{ $listing->productVariant?->product?->name_ar ?? $listing->productVariant?->product?->name_en }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 text-right">{{ $inv->warehouse?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900">{{ $inv->quantity_on_hand }}</td>
                                <td class="px-4 py-3 text-center font-bold text-green-600">{{ $inv->quantity_available }}</td>
                                <td class="px-4 py-3 text-center text-orange-500">{{ $inv->quantity_reserved }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400 text-right">{{ $inv->bin_location ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-xs">
                                    @if($inv->reorder_point)
                                        <span class="{{ $inv->quantity_available <= $inv->reorder_point ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                                            {{ $inv->reorder_point }}
                                            @if($inv->quantity_available <= $inv->reorder_point)
                                                ⚠️
                                            @endif
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>{{-- end FBP tab --}}

    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    {{-- TAB: Marketplace                                                         --}}
    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    <div x-show="tab==='marketplace'" x-cloak class="space-y-5">

        <div class="bg-green-50 border border-green-100 rounded-2xl p-4 flex gap-3">
            <span class="text-2xl">🌐</span>
            <div>
                <p class="font-semibold text-green-800">نظام الماركت — Marketplace</p>
                <p class="text-sm text-green-600 mt-0.5">
                    للمنتجات الكبيرة أو الثقيلة أو التي تحتاج تبريد أو شحن خاص.
                    تنطبق عمولات وأسعار توصيل مختلفة على هذه المنتجات.
                </p>
            </div>
        </div>

        @if($marketplaceListings->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-10 text-center text-gray-400">
                <p class="text-2xl mb-2">🌐</p>
                <p class="font-medium">لا توجد قوائم ماركت حتى الآن.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($marketplaceListings as $listing)
                @php $rule = $listing->marketplaceShippingRule ?? null; @endphp
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="font-semibold text-gray-800 text-sm mb-1">
                        {{ $listing->productVariant?->product?->name_ar ?? $listing->productVariant?->product?->name_en ?? 'منتج' }}
                    </p>
                    @if($rule)
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            @if($rule->requires_special_vehicle)
                                <span class="badge badge-warning text-xs">سيارة خاصة</span>
                            @endif
                            @if($rule->requires_refrigeration)
                                <span class="badge badge-primary text-xs">تبريد</span>
                            @endif
                            @if($rule->max_weight_kg)
                                <span class="badge badge-secondary text-xs">{{ $rule->max_weight_kg }} كج</span>
                            @endif
                        </div>
                        <dl class="grid grid-cols-2 gap-1 text-xs">
                            <dt class="text-gray-400">نوع العمولة</dt>
                            <dd class="font-medium text-gray-700">
                                {{ match($rule->commission_type) { 'fixed' => 'ثابتة', 'percentage' => 'نسبة مئوية', 'mixed' => 'مختلطة', default => $rule->commission_type } }}
                            </dd>
                            <dt class="text-gray-400">قيمة العمولة</dt>
                            <dd class="font-semibold text-gray-900">{{ $rule->commissionLabel() }}</dd>
                            @if($rule->extra_delivery_fee_cents > 0)
                                <dt class="text-gray-400">رسوم توصيل إضافية</dt>
                                <dd class="font-medium text-orange-600">{{ $rule->extraFeeFormatted() }}</dd>
                            @endif
                        </dl>
                        @if($rule->special_handling_notes)
                            <p class="text-xs text-gray-500 mt-2 bg-gray-50 rounded p-2">{{ $rule->special_handling_notes }}</p>
                        @endif
                    @else
                        <p class="text-xs text-gray-400 italic">لا توجد قواعد شحن مخصصة (تُطبق الإعدادات الافتراضية).</p>
                    @endif
                </div>
                @endforeach
            </div>
        @endif

    </div>{{-- end Marketplace tab --}}

    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    {{-- TAB: Storage Fees                                                       --}}
    {{-- ─────────────────────────────────────────────────────────────────────── --}}
    <div x-show="tab==='fees'" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">رسوم التخزين الشهرية (FBN)</div>
            <div id="fees-container">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">الشهر</th>
                            <th class="px-4 py-3 text-right">الوحدات المخزنة</th>
                            <th class="px-4 py-3 text-right">المبلغ</th>
                            <th class="px-4 py-3 text-right">الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="fees-tbody">
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">اضغط تبويب "رسوم التخزين" لتحميل البيانات.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>{{-- end Fees tab --}}

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script>
function fulfillmentApp() {
    return {
        tab: 'fbn',
        showFbnForm: false,
        feesLoaded: false,

        init() {
            this.loadFbnRequests();
        },

        async loadFbnRequests() {
            const res = await fetch('{{ route('partner.fulfillment.fbn.requests') }}', {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();
            const container = document.getElementById('fbn-requests-container');
            if (!container) return;

            if (!data.data?.length) {
                container.innerHTML = '<p class="py-6 text-gray-400">لا توجد طلبات إرسال حتى الآن.</p>';
                return;
            }

            let html = `<table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-right">رقم الطلب</th>
                        <th class="px-3 py-2 text-right">المنتج</th>
                        <th class="px-3 py-2 text-right">المستودع</th>
                        <th class="px-3 py-2 text-center">الكمية</th>
                        <th class="px-3 py-2 text-right">الحالة</th>
                        <th class="px-3 py-2 text-right">التتبع</th>
                        <th class="px-3 py-2 text-right">الوصول المتوقع</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead><tbody>`;

            data.data.forEach(r => {
                const cancelBtn = r.can_cancel
                    ? `<button class="btn btn-xs btn-danger" onclick="cancelFbnRequest('${r.id}')">إلغاء</button>`
                    : '';
                html += `<tr class="border-t border-gray-50 hover:bg-gray-50/50">
                    <td class="px-3 py-2 font-mono text-right text-gray-700">${r.request_number}</td>
                    <td class="px-3 py-2 text-right text-gray-800">${r.product}</td>
                    <td class="px-3 py-2 text-right text-gray-500">${r.warehouse}</td>
                    <td class="px-3 py-2 text-center">${r.qty_requested} / <span class="text-green-600 font-bold">${r.qty_received}</span></td>
                    <td class="px-3 py-2 text-right"><span class="badge badge-${r.status_color} text-xs">${r.status_label}</span></td>
                    <td class="px-3 py-2 text-right text-gray-400">${r.tracking_number ?? '—'}</td>
                    <td class="px-3 py-2 text-right text-gray-500">${r.expected_arrival ?? '—'}</td>
                    <td class="px-3 py-2">${cancelBtn}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        },

        async submitFbnRequest() {
            const listing_id   = document.getElementById('fbn-listing').value;
            const warehouse_id = document.getElementById('fbn-warehouse').value;
            const qty          = document.getElementById('fbn-qty').value;
            const notes        = document.getElementById('fbn-notes').value;

            if (!listing_id || !warehouse_id || !qty) {
                window.Toast.error('يرجى تعبئة جميع الحقول المطلوبة.');
                return;
            }

            const res = await fetch('{{ route('partner.fulfillment.fbn.submit') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ vendor_listing_id: listing_id, warehouse_id, quantity_requested: parseInt(qty), vendor_notes: notes }),
            });
            const data = await res.json();
            if (data.success) {
                window.Toast.success(data.message);
                this.showFbnForm = false;
                this.loadFbnRequests();
            } else {
                window.Toast.error(data.message ?? 'حدث خطأ');
            }
        },

        async loadFees() {
            if (this.feesLoaded) return;
            const res = await fetch('{{ route('partner.fulfillment.storage-fees') }}', {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();
            const tbody = document.getElementById('fees-tbody');
            if (!data.data?.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">لا توجد رسوم تخزين حتى الآن.</td></tr>';
            } else {
                tbody.innerHTML = data.data.map(f => `<tr class="border-t border-gray-50">
                    <td class="px-4 py-3 text-right font-medium text-gray-700">${f.month}</td>
                    <td class="px-4 py-3 text-center text-gray-800">${f.units.toLocaleString()}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">${f.total}</td>
                    <td class="px-4 py-3 text-right"><span class="badge badge-${f.status_color} text-xs">${f.status}</span></td>
                </tr>`).join('');
            }
            this.feesLoaded = true;
        },

        async loadFbpInventory() {
            const res = await fetch('{{ route('partner.fulfillment.fbp.inventory') }}', {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();
            window.Toast.info('المخزون محدّث.');
        },
    };
}

async function cancelFbnRequest(id) {
    if (!confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) return;
    const res = await fetch(`{{ url('partner/fulfillment/fbn') }}/${id}/cancel`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: '{}',
    });
    const data = await res.json();
    if (data.success) { window.Toast.success(data.message); location.reload(); }
    else { window.Toast.error(data.message ?? 'حدث خطأ'); }
}
</script>
@endpush
