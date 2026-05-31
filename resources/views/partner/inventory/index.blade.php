@extends('layouts.partner')

@section('title', 'المخزون')
@section('page-title', 'إدارة المخزون')

@push('scripts')
    @vite('resources/js/partner/inventory.js')
    <script>
        window.INVENTORY = {
            datatableUrl: '{{ route('partner.inventory.datatable') }}',
            filterParam: '{{ request('filter', '') }}',
            adjustBaseUrl: '{{ Route::has('partner.listings.adjust-stock') ? url('') : '' }}',
            csrf: '{{ csrf_token() }}',
        };
    </script>
@endpush

@section('content')

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-partner-stat-card title="إجمالي SKUs" :value="number_format($stats->total_skus)" icon="tag" color="blue" />

        <x-partner-stat-card title="إجمالي الوحدات" :value="number_format($stats->total_on_hand)" icon="cube"
            color="green" />

        <x-partner-stat-card title="متاح للبيع" :value="number_format($stats->total_available)" icon="check-circle"
            color="success" :link="route('partner.inventory.index')" />

        <x-partner-stat-card title="مخزون منخفض" :value="number_format($lowStockCount)" icon="exclamation-triangle"
            :color="$lowStockCount > 0 ? 'warning' : 'gray'" :link="route('partner.inventory.low-stock')" />
    </div>

    {{-- Quick links --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('partner.inventory.index') }}" @class([
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
            'bg-gray-900 text-white border-gray-900' => !request('filter'),
            'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' => request('filter')
        ])>
            الكل
        </a>
        <a href="{{ route('partner.inventory.index', ['filter' => 'low_stock']) }}" @class([
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
            'bg-orange-500 text-white border-orange-500' => request('filter') === 'low_stock',
            'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' => request('filter') !== 'low_stock'
        ])>
            ⚠ مخزون منخفض
            @if($lowStockCount > 0)
                <span class="bg-orange-100 text-orange-700 text-xs px-1.5 py-0.5 rounded-full">{{ $lowStockCount }}</span>
            @endif
        </a>
        <a href="{{ route('partner.inventory.index', ['filter' => 'out_of_stock']) }}" @class([
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
            'bg-red-500 text-white border-red-500' => request('filter') === 'out_of_stock',
            'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' => request('filter') !== 'out_of_stock'
        ])>
            🚫 نفد المخزون
            @if($outOfStockCount > 0)
                <span class="bg-red-100 text-red-700 text-xs px-1.5 py-0.5 rounded-full">{{ $outOfStockCount }}</span>
            @endif
        </a>
    </div>

    {{-- DataTable --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="p-5">
            <table id="inventory-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="text-right py-2 font-medium">المنتج</th>
                        <th class="text-right py-2 font-medium">النسخة / SKU</th>
                        <th class="text-right py-2 font-medium">المستودع</th>
                        <th class="py-2 text-center font-medium">في المخزن</th>
                        <th class="py-2 text-center font-medium">محجوز</th>
                        <th class="py-2 text-center font-medium">متاح</th>
                        <th class="py-2 text-center font-medium">وارد</th>
                        <th class="py-2 text-center font-medium">تالف</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Adjust Stock Modal --}}
    <div id="inventory-adjust-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">تعديل المخزون</h3>
                    <p id="inv-adjust-warehouse" class="text-xs text-gray-400"></p>
                </div>
                <button id="inv-adjust-close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="inv-adjust-form" class="p-5 space-y-4">
                <input type="hidden" id="inv-adjust-listing-id">
                <input type="hidden" id="inv-adjust-inv-id" name="warehouse_inventory_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المخزون الحالي</label>
                    <p id="inv-adjust-current" class="text-2xl font-bold text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">التعديل <span
                            class="text-xs text-gray-400">(+ للإضافة، − للخصم)</span></label>
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
                <div id="inv-adjust-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3"></div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-gray-900 hover:bg-gray-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">تأكيد</button>
                    <button type="button" id="inv-adjust-cancel"
                        class="flex-1 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition-colors">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

@endsection