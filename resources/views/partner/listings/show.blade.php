@extends('layouts.partner')

@php
    $product = $listing->productVariant->product;
    $variant = $listing->productVariant;
    $category = $product->category;
    $primaryImg = $product->images->where('is_primary', true)->first()
        ?? $product->images->first();

    $statusMap = [
        'active' => ['bg-green-100 text-green-700', 'نشط'],
        'paused' => ['bg-gray-100 text-gray-600', 'موقوف مؤقتاً'],
        'pending_review' => ['bg-yellow-100 text-yellow-700', 'قيد المراجعة'],
        'draft' => ['bg-gray-100 text-gray-500', 'مسودة'],
        'rejected' => ['bg-red-100 text-red-700', 'مرفوض'],
        'out_of_stock' => ['bg-red-50 text-red-500', 'نفد المخزون'],
        'archived' => ['bg-gray-100 text-gray-400', 'مؤرشف'],
    ];
    [$statusClass, $statusLabel] = $statusMap[$listing->status] ?? ['bg-gray-100 text-gray-600', $listing->status];

    $fulfillmentLabels = [
        'fbm' => 'FBM — شحن ذاتي',
        'fbn' => 'FBN — تخزين لدينا',
        'cross_dock' => 'Cross Dock',
    ];
    $conditionLabels = [
        'new' => 'جديد',
        'like_new' => 'كالجديد',
        'good' => 'جيد',
        'acceptable' => 'مقبول',
        'refurbished' => 'مُجدَّد',
    ];
    $movementTypeLabels = [
        'inbound' => ['text-green-600', '↑ وارد'],
        'outbound' => ['text-red-600', '↓ صادر'],
        'adjustment_in' => ['text-blue-600', '+ تعديل'],
        'adjustment_out' => ['text-orange-600', '− تعديل'],
        'reservation' => ['text-purple-600', '🔒 حجز'],
        'reservation_release' => ['text-teal-600', '🔓 إلغاء حجز'],
    ];
@endphp

@section('title', $product->name_ar ?: $product->name_en)
@section('page-title', 'تفاصيل القائمة')

@push('scripts')
    @vite('resources/js/partner/listings.js')
    <script>
        window.LISTING_DETAIL = {
            listingId: '{{ $listing->id }}',
            currentStatus: '{{ $listing->status }}',
            currentPrice: '{{ $listing->price / 100 }}',
            updatePriceUrl: '{{ route('partner.listings.update-price', $listing->id) }}',
            toggleStatusUrl: '{{ route('partner.listings.toggle-status', $listing->id) }}',
            adjustStockUrl: '{{ route('partner.listings.adjust-stock', $listing->id) }}',
            csrf: '{{ csrf_token() }}',
        };
    </script>
@endpush

@section('content')

    {{-- Back link --}}
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

        {{-- ── LEFT COLUMN ── --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Product Info Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex items-start gap-5">
                    <div
                        class="w-20 h-20 rounded-xl border border-gray-100 bg-gray-50 overflow-hidden shrink-0 flex items-center justify-center">
                        @if($primaryImg)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk($primaryImg->disk)->url($primaryImg->path) }}"
                                alt="{{ $product->name_en }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m8 4v10" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-bold text-gray-900 text-lg leading-snug">
                                    {{ $product->name_ar ?: $product->name_en }}
                                </h2>
                                @if($product->name_ar && $product->name_en)
                                    <p class="text-sm text-gray-500">{{ $product->name_en }}</p>
                                @endif
                                @if($category)
                                    <p class="text-xs text-gray-400 mt-1">{{ $category->name_ar ?? $category->name_en }}</p>
                                @endif
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }} shrink-0">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                            <div>
                                <span class="text-xs text-gray-400 block">النسخة</span>
                                <span class="font-medium">{{ $variant->variant_name ?: 'الافتراضية' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block">SKU</span>
                                <span class="font-mono text-xs">{{ $variant->sku }}</span>
                            </div>
                            @if($listing->vendor_sku)
                                <div>
                                    <span class="text-xs text-gray-400 block">SKU الخاص بك</span>
                                    <span class="font-mono text-xs">{{ $listing->vendor_sku }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Listing Details --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">تفاصيل القائمة</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 text-sm">
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">السعر</span>
                        <span id="display-price" class="font-bold text-gray-900 text-lg">
                            {{ number_format($listing->price / 100, 2) }}
                        </span>
                        <span class="text-xs text-gray-500 mr-1">{{ $listing->currency }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">الحالة</span>
                        <span class="font-medium">{{ $conditionLabels[$listing->condition] ?? $listing->condition }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">نموذج التنفيذ</span>
                        <span
                            class="font-medium">{{ $fulfillmentLabels[$listing->fulfillment_model] ?? $listing->fulfillment_model }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">إجمالي المباع</span>
                        <span class="font-medium">{{ number_format($listing->total_sold) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">متوسط التقييم</span>
                        <span
                            class="font-medium">{{ $listing->rating_avg ? number_format($listing->rating_avg, 1) . ' ★' : '—' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">حد التنبيه</span>
                        <span class="font-medium">{{ $listing->low_stock_threshold }}</span>
                    </div>
                </div>

                @if($listing->vendor_notes)
                    <div class="mt-4 pt-4 border-t border-gray-50">
                        <span class="text-xs text-gray-400 block mb-1">ملاحظاتك الخاصة</span>
                        <p class="text-sm text-gray-700">{{ $listing->vendor_notes }}</p>
                    </div>
                @endif

                @if($listing->rejection_reason)
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4">
                        <p class="text-xs font-semibold text-red-700 mb-1">سبب الرفض</p>
                        <p class="text-sm text-red-800">{{ $listing->rejection_reason }}</p>
                    </div>
                @endif
            </div>

            {{-- Inventory per Warehouse --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m8 4v10" />
                    </svg>
                    المخزون حسب المستودع
                </h3>

                @if($listing->warehouseInventories->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-6">لا يوجد مخزون مسجل بعد.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 border-b border-gray-100">
                                    <th class="text-right py-2 font-medium">المستودع</th>
                                    <th class="py-2 text-center font-medium">متاح للبيع</th>
                                    <th class="py-2 text-center font-medium">في المخزن</th>
                                    <th class="py-2 text-center font-medium">محجوز</th>
                                    <th class="py-2 text-center font-medium">وارد</th>
                                    <th class="py-2 text-center font-medium">تالف</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($listing->warehouseInventories as $inv)
                                    @php
                                        $available = $inv->quantity_on_hand - $inv->quantity_reserved;
                                        $stockCls = $available <= 0 ? 'text-red-600 font-bold'
                                            : ($available <= $listing->low_stock_threshold ? 'text-orange-500 font-semibold' : 'text-gray-900');
                                    @endphp
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-medium text-gray-800">{{ $inv->warehouse->name }}</p>
                                            @if($inv->bin_location)
                                                <p class="text-xs text-gray-400 font-mono">{{ $inv->bin_location }}</p>
                                            @endif
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="{{ $stockCls }} text-base font-bold"
                                                id="avail-{{ $inv->id }}">{{ $available }}</span>
                                        </td>
                                        <td class="py-3 text-center text-gray-700" id="onhand-{{ $inv->id }}">
                                            {{ $inv->quantity_on_hand }}</td>
                                        <td class="py-3 text-center text-gray-500">{{ $inv->quantity_reserved }}</td>
                                        <td class="py-3 text-center text-blue-500">{{ $inv->quantity_inbound ?? 0 }}</td>
                                        <td class="py-3 text-center text-red-400">{{ $inv->quantity_damaged ?? 0 }}</td>
                                        <td class="py-3 text-right">
                                            <button type="button"
                                                class="btn-adjust-stock text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg transition-colors"
                                                data-inv-id="{{ $inv->id }}" data-warehouse="{{ $inv->warehouse->name }}"
                                                data-on-hand="{{ $inv->quantity_on_hand }}">
                                                تعديل المخزون
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Stock History --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">سجل الحركات (آخر 20)</h3>
                    <a href="{{ route('partner.inventory.movements', $listing->id) }}"
                        class="text-xs text-blue-600 hover:underline">عرض الكل</a>
                </div>

                @if($movements->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-4">لا توجد حركات مخزون مسجلة.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-50">
                                    <th class="text-right py-2 font-medium">النوع</th>
                                    <th class="py-2 text-center font-medium">الكمية</th>
                                    <th class="py-2 text-center font-medium">بعد</th>
                                    <th class="text-right py-2 font-medium">السبب</th>
                                    <th class="text-right py-2 font-medium">التاريخ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($movements as $mov)
                                    @php
                                        [$movCls, $movLabel] = $movementTypeLabels[$mov->movement_type]
                                            ?? ['text-gray-600', $mov->movement_type];
                                        $deltaSign = $mov->quantity_delta > 0 ? '+' : '';
                                    @endphp
                                    <tr>
                                        <td class="py-2">
                                            <span class="{{ $movCls }} font-medium">{{ $movLabel }}</span>
                                        </td>
                                        <td
                                            class="py-2 text-center {{ $mov->quantity_delta > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                            {{ $deltaSign }}{{ $mov->quantity_delta }}
                                        </td>
                                        <td class="py-2 text-center text-gray-600">{{ $mov->quantity_after }}</td>
                                        <td class="py-2 text-gray-600">{{ $mov->reason ?? '—' }}</td>
                                        <td class="py-2 text-gray-400">{{ $mov->created_at?->format('M d, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- ── RIGHT SIDEBAR ── --}}
        <div class="lg:col-span-4 space-y-4">

            {{-- Quick Stats --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h4 class="font-semibold text-gray-800 text-sm mb-3">إجمالي المخزون</h4>
                @php
                    $totalOnHand = $listing->warehouseInventories->sum('quantity_on_hand');
                    $totalReserved = $listing->warehouseInventories->sum('quantity_reserved');
                    $totalAvail = $totalOnHand - $totalReserved;
                    $totalInbound = $listing->warehouseInventories->sum('quantity_inbound');
                @endphp
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">متاح للبيع</span>
                        <span @class(['font-bold', 'text-red-600' => $totalAvail <= 0, 'text-orange-500' => $totalAvail > 0 && $totalAvail <= $listing->low_stock_threshold, 'text-gray-900' => $totalAvail > $listing->low_stock_threshold])>
                            {{ $totalAvail }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>في المخزن</span>
                        <span>{{ $totalOnHand }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>محجوز</span>
                        <span>{{ $totalReserved }}</span>
                    </div>
                    @if($totalInbound > 0)
                        <div class="flex justify-between text-sm text-blue-600">
                            <span>وارد قريباً</span>
                            <span>{{ $totalInbound }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h4 class="font-semibold text-gray-800 text-sm mb-3">الإجراءات</h4>
                <div class="space-y-2">

                    {{-- Update Price --}}
                    <button id="btn-update-price"
                        class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-900 text-sm font-semibold py-2.5 rounded-xl transition-colors">
                        تعديل السعر
                    </button>

                    {{-- Toggle Status --}}
                    @if(in_array($listing->status, ['active', 'paused']))
                        <button id="btn-toggle-status"
                            class="w-full {{ $listing->status === 'active' ? 'border border-gray-200 hover:bg-gray-50 text-gray-700' : 'bg-green-600 hover:bg-green-700 text-white' }} text-sm font-semibold py-2.5 rounded-xl transition-colors">
                            {{ $listing->status === 'active' ? 'إيقاف مؤقت' : 'تفعيل القائمة' }}
                        </button>
                    @endif

                    {{-- Inventory movements link --}}
                    <a href="{{ route('partner.inventory.movements', $listing->id) }}"
                        class="w-full block text-center border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2.5 rounded-xl transition-colors">
                        سجل حركات المخزون
                    </a>

                </div>
            </div>

        </div>

    </div>

    {{-- Adjust Stock Modal --}}
    <div id="adjust-stock-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">تعديل المخزون</h3>
                    <p id="adjust-warehouse-name" class="text-xs text-gray-400"></p>
                </div>
                <button id="adjust-modal-close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="adjust-form" class="p-5 space-y-4">
                <input type="hidden" id="adjust-inv-id" name="warehouse_inventory_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المخزون الحالي</label>
                    <p id="adjust-current-qty" class="text-2xl font-bold text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">التعديل <span
                            class="text-xs text-gray-400">(موجب للإضافة، سالب للخصم)</span></label>
                    <input type="number" name="adjustment" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                        placeholder="+10 أو -5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">السبب <span
                            class="text-red-500">*</span></label>
                    <select name="reason" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                        <option value="">اختر السبب...</option>
                        <option value="received_stock">استلام مخزون جديد</option>
                        <option value="damaged_goods">بضاعة تالفة</option>
                        <option value="inventory_count">جرد مخزون</option>
                        <option value="returned_to_vendor">مرتجع من العميل</option>
                        <option value="transfer">نقل بين مستودعات</option>
                        <option value="other">سبب آخر</option>
                    </select>
                </div>
                <div id="adjust-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3"></div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-gray-900 hover:bg-gray-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                        تأكيد التعديل
                    </button>
                    <button type="button" id="adjust-cancel-btn"
                        class="flex-1 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition-colors">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Update Price Modal --}}
    <div id="update-price-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">تعديل السعر</h3>
                <button id="price-modal-close-detail" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="price-update-form" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">السعر الجديد</label>
                    <input type="number" id="new-price-input" name="price" step="0.01" min="0.01"
                        value="{{ $listing->price / 100 }}"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                </div>
                <div id="price-update-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3"></div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold py-2.5 rounded-xl text-sm transition-colors">حفظ</button>
                    <button type="button" id="price-close-btn"
                        class="flex-1 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition-colors">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection