@extends('layouts.partner')

@section('title', 'المدفوعات')
@section('page-title', 'المدفوعات والأرباح')

@push('scripts')
    @vite('resources/js/partner/payouts.js')
    <script>
        window.PAYOUTS = {
            earningsSummaryUrl: '{{ route('partner.payouts.earnings-summary') }}',
        };
    </script>
@endpush

@section('content')

    {{-- Earnings summary cards (populated via AJAX) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- This month --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">أرباح هذا الشهر</p>
            <p id="stat-this-month" class="text-2xl font-bold text-gray-900 animate-pulse bg-gray-100 rounded-lg h-7 w-24">
            </p>
            <p class="text-xs text-gray-400 mt-1">من الطلبات المكتملة</p>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">بانتظار التحويل</p>
            <p id="stat-pending" class="text-2xl font-bold text-yellow-500 animate-pulse bg-gray-100 rounded-lg h-7 w-24">
            </p>
            <p class="text-xs text-gray-400 mt-1">مدفوعات معلقة</p>
        </div>

        {{-- YTD --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">إجمالي هذا العام</p>
            <p id="stat-ytd" class="text-2xl font-bold text-green-600 animate-pulse bg-gray-100 rounded-lg h-7 w-28">
            </p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('Y') }}</p>
        </div>

        {{-- Last payout --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">آخر دفعة مستلمة</p>
            <p id="stat-last-amount" class="text-2xl font-bold text-gray-900 animate-pulse bg-gray-100 rounded-lg h-7 w-24">
            </p>
            <p id="stat-last-date" class="text-xs text-gray-400 mt-1">—</p>
        </div>

    </div>

    {{-- Payout history --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">سجل المدفوعات</h2>
            <span class="text-xs text-gray-400">{{ $payouts->total() }} دفعة</span>
        </div>

        @if($payouts->isEmpty())
            <div class="py-16 text-center">
                <div class="text-4xl mb-3">💸</div>
                <h3 class="font-semibold text-gray-800 mb-1">لا توجد مدفوعات بعد</h3>
                <p class="text-sm text-gray-400">ستظهر هنا مدفوعاتك بمجرد إتمام أول دورة.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="text-right py-3 px-5 font-medium">رقم الدفعة</th>
                            <th class="text-right py-3 px-4 font-medium">الفترة</th>
                            <th class="py-3 px-4 text-center font-medium">المبيعات الإجمالية</th>
                            <th class="py-3 px-4 text-center font-medium">العمولة</th>
                            <th class="py-3 px-4 text-center font-medium">صافي المبلغ</th>
                            <th class="py-3 px-4 text-center font-medium">الحالة</th>
                            <th class="py-3 px-4 text-right font-medium">التاريخ</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($payouts as $payout)
                            @php
                                $statusMap = [
                                    'pending' => ['bg-yellow-100 text-yellow-700', 'معلق'],
                                    'approved' => ['bg-blue-100 text-blue-700', 'موافق عليه'],
                                    'processing' => ['bg-indigo-100 text-indigo-700', 'قيد التحويل'],
                                    'completed' => ['bg-green-100 text-green-700', 'مكتمل'],
                                    'failed' => ['bg-red-100 text-red-700', 'فشل'],
                                    'on_hold' => ['bg-gray-100 text-gray-600', 'معلق بأمر إداري'],
                                ];
                                [$statusCls, $statusLabel] = $statusMap[$payout->status]
                                    ?? ['bg-gray-100 text-gray-500', $payout->status];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-5">
                                    <a href="{{ route('partner.payouts.show', $payout->payout_number) }}"
                                        class="font-mono text-blue-600 hover:underline text-xs font-medium">
                                        {{ $payout->payout_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-xs text-gray-600">
                                    {{ $payout->period_start?->format('M d') }} — {{ $payout->period_end?->format('M d, Y') }}
                                </td>
                                <td class="py-3 px-4 text-center text-gray-700">
                                    {{ number_format($payout->gross_sales / 100, 2) }}
                                    <span class="text-xs text-gray-400">{{ $payout->currency }}</span>
                                </td>
                                <td class="py-3 px-4 text-center text-red-500 text-xs">
                                    − {{ number_format($payout->commission / 100, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-900">
                                    {{ number_format($payout->net_amount / 100, 2) }}
                                    <span class="text-xs text-gray-400 font-normal">{{ $payout->currency }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-xs text-gray-400">
                                    {{ $payout->processed_at?->format('Y/m/d') ?? '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    <a href="{{ route('partner.payouts.show', $payout->payout_number) }}"
                                        class="text-xs text-gray-500 hover:text-blue-600 transition-colors">تفاصيل →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payouts->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $payouts->links() }}
                </div>
            @endif
        @endif

    </div>

@endsection