@extends('layouts.partner')

@section('title', 'نفد المخزون')
@section('page-title', 'نفد المخزون')

@section('content')

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">المنتجات التي نفد مخزونها المتاح للبيع</p>
        <div class="flex gap-2">
            <a href="{{ route('partner.inventory.low-stock') }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                ⚠ مخزون منخفض
            </a>
            <a href="{{ route('partner.inventory.index') }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                ← المخزون
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

        @if($rows->isEmpty())
            <div class="py-16 text-center">
                <div class="text-4xl mb-3">🎉</div>
                <h3 class="font-semibold text-gray-800 mb-1">لا يوجد مخزون نافد</h3>
                <p class="text-sm text-gray-400">جميع قوائمك تمتلك مخزوناً متاحاً للبيع.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="text-right py-3 px-5 font-medium">المنتج</th>
                            <th class="text-right py-3 px-4 font-medium">النسخة</th>
                            <th class="text-right py-3 px-4 font-medium">المستودع</th>
                            <th class="py-3 px-4 text-center font-medium">في المخزن</th>
                            <th class="py-3 px-4 text-center font-medium">محجوز</th>
                            <th class="py-3 px-4 text-center font-medium">متاح</th>
                            <th class="py-3 px-4 text-center font-medium">حالة القائمة</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($rows as $row)
                            @php
                                $available = $row->quantity_on_hand - $row->quantity_reserved;
                                $statusMap = [
                                    'active' => 'bg-green-100 text-green-700',
                                    'paused' => 'bg-gray-100 text-gray-600',
                                    'pending_review' => 'bg-yellow-100 text-yellow-700',
                                    'out_of_stock' => 'bg-red-100 text-red-700',
                                ];
                                $statusLabelMap = [
                                    'active' => 'نشط',
                                    'paused' => 'موقوف',
                                    'pending_review' => 'قيد المراجعة',
                                    'out_of_stock' => 'نفد المخزون',
                                ];
                                $statusCls = $statusMap[$row->listing_status] ?? 'bg-gray-100 text-gray-500';
                                $statusLabel = $statusLabelMap[$row->listing_status] ?? $row->listing_status;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-5">
                                    <p class="font-medium text-gray-800 leading-tight">
                                        {{ $row->product_name_ar ?: $row->product_name_en }}</p>
                                    @if($row->product_name_ar && $row->product_name_en)
                                        <p class="text-xs text-gray-400">{{ $row->product_name_en }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-gray-700 text-xs">{{ $row->variant_name ?: 'الافتراضية' }}</p>
                                    <p class="font-mono text-gray-400 text-xs">{{ $row->sku }}</p>
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-xs">{{ $row->warehouse_name }}</td>
                                <td class="py-3 px-4 text-center font-medium text-gray-600">{{ $row->quantity_on_hand }}</td>
                                <td class="py-3 px-4 text-center text-gray-500">{{ $row->quantity_reserved }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-bold text-red-600">{{ $available }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('partner.listings.show', $row->listing_id) }}"
                                        class="text-xs text-blue-600 hover:underline whitespace-nowrap">عرض القائمة</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $rows->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection