@extends('layouts.partner')

@section('title', 'طلبات الإرجاع')
@section('page-title', 'طلبات الإرجاع')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('apply-date-filter')?.addEventListener('click', function () {
            const from = document.getElementById('filter-date-from').value;
            const to   = document.getElementById('filter-date-to').value;
            const url  = new URL(window.location.href);
            if (from) url.searchParams.set('date_from', from);
            else url.searchParams.delete('date_from');
            if (to) url.searchParams.set('date_to', to);
            else url.searchParams.delete('date_to');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
        document.getElementById('clear-date-filter')?.addEventListener('click', function () {
            const url = new URL(window.location.href);
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    });
</script>
@endpush

@section('content')

@php
    $statusMap = [
        'requested'        => ['bg-yellow-100 text-yellow-700',  'مطلوب'],
        'approved'         => ['bg-blue-100 text-blue-700',      'مقبول'],
        'rejected'         => ['bg-red-100 text-red-700',        'مرفوض'],
        'awaiting_pickup'  => ['bg-amber-100 text-amber-700',    'بانتظار الاستلام'],
        'in_transit'       => ['bg-indigo-100 text-indigo-700',  'في الطريق'],
        'received'         => ['bg-cyan-100 text-cyan-700',      'تم الاستلام'],
        'inspecting'       => ['bg-purple-100 text-purple-700',  'قيد الفحص'],
        'completed'        => ['bg-green-100 text-green-700',    'مكتمل'],
        'cancelled'        => ['bg-gray-100 text-gray-500',      'ملغى'],
    ];
    $reasonMap = [
        'changed_mind'      => 'غيّر رأيه',
        'wrong_item'        => 'منتج خاطئ',
        'defective'         => 'معيب',
        'damaged'           => 'تالف',
        'not_as_described'  => 'غير مطابق للوصف',
        'size_issue'        => 'مشكلة في المقاس',
        'quality_issue'     => 'جودة رديئة',
        'arrived_late'      => 'وصل متأخراً',
        'other'             => 'أخرى',
    ];
    $typeMap = [
        'refund'       => 'استرداد',
        'exchange'     => 'استبدال',
        'store_credit' => 'رصيد متجر',
    ];
    $tabStatuses = array_keys($statusMap);
    $currentStatus = request('status');
@endphp

{{-- Status tabs --}}
<div class="bg-white rounded-2xl border border-gray-200 mb-4">
    <div class="flex items-center overflow-x-auto">
        <a href="{{ route('partner.returns.index') }}" @class([
            'flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
            'border-primary-500 text-primary-600' => !$currentStatus,
            'border-transparent text-gray-500 hover:text-gray-700' => $currentStatus,
        ])>الكل <span class="mr-1 text-xs text-gray-400">({{ $returns->total() }})</span></a>

        @foreach($tabStatuses as $st)
            <a href="{{ route('partner.returns.index', ['status' => $st, 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}" @class([
                'flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                'border-primary-500 text-primary-600' => $currentStatus === $st,
                'border-transparent text-gray-500 hover:text-gray-700' => $currentStatus !== $st,
            ])>{{ $statusMap[$st][1] }}</a>
        @endforeach
    </div>
</div>

{{-- Date filter --}}
<div class="bg-white rounded-2xl border border-gray-200 p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">من تاريخ</label>
        <input type="date" id="filter-date-from" value="{{ request('date_from') }}"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400/40">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">إلى تاريخ</label>
        <input type="date" id="filter-date-to" value="{{ request('date_to') }}"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400/40">
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

{{-- Table --}}
<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">قائمة طلبات الإرجاع</h2>
        <span class="text-xs text-gray-400">{{ $returns->total() }} طلب</span>
    </div>

    @if($returns->isEmpty())
        <div class="py-16 text-center">
            <div class="text-4xl mb-3">📦</div>
            <h3 class="font-semibold text-gray-800 mb-1">لا توجد طلبات إرجاع</h3>
            <p class="text-sm text-gray-400">ستظهر هنا طلبات الإرجاع عند ورودها.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="text-right py-3 px-5 font-medium">رقم الإرجاع</th>
                        <th class="text-right py-3 px-4 font-medium">رقم الطلب</th>
                        <th class="text-right py-3 px-4 font-medium">العميل</th>
                        <th class="text-center py-3 px-4 font-medium">السبب</th>
                        <th class="text-center py-3 px-4 font-medium">النوع</th>
                        <th class="text-center py-3 px-4 font-medium">الحالة</th>
                        <th class="text-right py-3 px-4 font-medium">التاريخ</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($returns as $ret)
                        @php
                            [$statusCls, $statusLabel] = $statusMap[$ret->status] ?? ['bg-gray-100 text-gray-500', $ret->status];
                            $orderMasked = $ret->order ? '****' . substr($ret->order->order_number, -4) : '—';
                            $customerName = trim(($ret->customer->first_name ?? '') . ' ' . (isset($ret->customer->last_name) ? strtoupper(substr($ret->customer->last_name, 0, 1)) . '.' : ''));
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-5">
                                <span class="font-mono text-xs font-medium text-gray-800">{{ $ret->return_number }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 font-mono">{{ $orderMasked }}</td>
                            <td class="py-3 px-4 text-xs text-gray-700">{{ $customerName ?: '—' }}</td>
                            <td class="py-3 px-4 text-center text-xs text-gray-600">
                                {{ $reasonMap[$ret->reason] ?? $ret->reason }}
                            </td>
                            <td class="py-3 px-4 text-center text-xs text-gray-600">
                                {{ $typeMap[$ret->return_type] ?? $ret->return_type }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right text-xs text-gray-400">
                                {{ $ret->created_at->format('Y/m/d') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('partner.returns.show', $ret->return_number) }}"
                                    class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                                    عرض
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $returns->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
