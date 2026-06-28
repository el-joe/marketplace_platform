@extends('layouts.partner')

@section('title', 'الطلبات')
@section('page-title', 'إدارة الطلبات')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@push('scripts')
    @vite('resources/js/partner/orders.js')
@endpush

@section('content')

    {{-- Filter tabs --}}
    <div class="bg-white rounded-2xl border border-gray-200 mb-4">
        <div class="flex items-center overflow-x-auto">

            {{-- All tab --}}
            <a href="{{ route('partner.orders.index') }}" @class([
                'flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                'border-yellow-400 text-yellow-600' => !request('status'),
                'border-transparent text-gray-500 hover:text-gray-700' => request('status'),
            ])>
                الكل
                <span class="mr-1.5 text-xs text-gray-400">({{ $counts->sum() }})</span>
            </a>

            {{-- SLA urgent --}}
            <a href="{{ route('partner.orders.index', ['status' => 'sla_urgent']) }}" @class([
                'flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5',
                'border-red-500 text-red-600' => request('status') === 'sla_urgent',
                'border-transparent text-gray-500 hover:text-gray-700' => request('status') !== 'sla_urgent',
            ])>
                ⚡ عاجل SLA
                @if($slaUrgentCount > 0)
                    <span
                        class="bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none">{{ $slaUrgentCount }}</span>
                @endif
            </a>

            @php
                $tabMap = [
                    'placed' => 'معلق',
                    'confirmed' => 'مؤكد',
                    'processing' => 'جارٍ التجهيز',
                    'packed' => 'جاهز للشحن',
                    'shipped' => 'تم الشحن',
                    'out_for_delivery' => 'في التوصيل',
                    'delivered' => 'تم التسليم',
                    'completed' => 'مكتمل',
                    'cancelled' => 'ملغى',
                ];
            @endphp

            @foreach($tabMap as $statusKey => $label)
                <a href="{{ route('partner.orders.index', ['status' => $statusKey]) }}" @class([
                    'flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                    'border-yellow-400 text-yellow-600' => request('status') === $statusKey,
                    'border-transparent text-gray-500 hover:text-gray-700' => request('status') !== $statusKey,
                ])>
                    {{ $label }}
                    @if(($counts[$statusKey] ?? 0) > 0)
                        <span class="mr-1 text-xs text-gray-400">({{ $counts[$statusKey] }})</span>
                    @endif
                </a>
            @endforeach

        </div>
    </div>

    {{-- Date range filter --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">من تاريخ</label>
            <input type="date" id="filter-date-from" value="{{ request('date_from') }}"
                class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">إلى تاريخ</label>
            <input type="date" id="filter-date-to" value="{{ request('date_to') }}"
                class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
        </div>
        <button id="apply-date-filter"
            class="bg-gray-900 hover:bg-gray-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
            تطبيق
        </button>
        <button id="clear-date-filter"
            class="border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
            إعادة تعيين
        </button>
    </div>

    {{-- DataTable --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table id="orders-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">رقم الطلب</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">الحالة</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">المبلغ</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">المدينة</th>
                    <th class="px-4 py-3 font-semibold tracking-wide text-center">العناصر</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">موعد الشحن</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wide">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100"></tbody>
        </table>
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
            <span id="orders-table-info" class="text-xs text-gray-400"></span>
            <div id="orders-table-pagination" class="flex items-center gap-1"></div>
        </div>
    </div>

    <script>
        window.ORDERS = {
            datatableUrl: '{{ route('partner.orders.datatable') }}',
            statusFilter: '{{ request('status', 'all') }}',
            dateFrom: '{{ request('date_from', '') }}',
            dateTo: '{{ request('date_to', '') }}',
        };
    </script>

@endsection