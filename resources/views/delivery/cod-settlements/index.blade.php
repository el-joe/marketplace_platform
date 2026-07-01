@extends('layouts.delivery')

@section('title', 'مدفوعاتي النقدية')

@section('content')

@php
    /** @var \App\Models\DeliveryAgent $agent */
    $currency = 'SAR';

    $statusChipMap = [
        'pending'  => ['class' => 'chip-cod-pending',  'label' => 'معلّق'],
        'settled'  => ['class' => 'chip-cod-settled',  'label' => 'مسوَّى'],
        'disputed' => ['class' => 'chip-cod-disputed', 'label' => 'متنازع عليه'],
    ];

    function codCents(int $cents, string $currency = 'SAR'): string {
        return number_format($cents / 100, 2) . ' ' . $currency;
    }
@endphp

<style>
    .chip-cod-pending  { background:#4a3000; color:#facc15; }
    .chip-cod-settled  { background:#14532d; color:#86efac; }
    .chip-cod-disputed { background:#450a0a; color:#fca5a5; }

    [dir="rtl"] { direction: rtl; text-align: right; }

    .settlement-row { cursor: pointer; }
    .delivery-rows  { display: none; }
    .delivery-rows.open { display: table-row-group; }
</style>

<div dir="rtl" class="space-y-5">

    {{-- ── Page Title ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-1">
        <div class="text-2xl">💵</div>
        <div>
            <h1 class="text-lg font-bold">مدفوعاتي النقدية</h1>
            <p class="text-xs text-slate-400 mt-0.5">تسويات الدفع النقدي عند التسليم (COD)</p>
        </div>
    </div>

    {{-- ── Current Period Summary ───────────────────────────────────────────── --}}
    <div class="d-card space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-sm font-bold text-slate-300">الفترة الحالية (اليوم)</p>
            <span class="chip chip-cod-pending text-xs">معلّق</span>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-slate-800 rounded-xl p-3">
                <p class="text-base font-bold text-slate-100">{{ codCents($currentCodTotal, $currency) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">إجمالي النقد</p>
            </div>
            <div class="bg-slate-800 rounded-xl p-3">
                <p class="text-base font-bold text-green-400">{{ codCents($currentEarningsTotal, $currency) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">عمولتي</p>
            </div>
            <div class="bg-slate-800 rounded-xl p-3">
                <p class="text-base font-bold {{ $currentNetOwed > 0 ? 'text-yellow-400' : 'text-slate-400' }}">
                    {{ codCents($currentNetOwed, $currency) }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">صافي المستحق</p>
            </div>
        </div>

        @if($currentPeriodAssignments->isNotEmpty())
            <div class="border-t border-slate-700 pt-3">
                <p class="text-xs font-semibold text-slate-400 mb-2">التوصيلات اليوم</p>
                <div class="space-y-2">
                    @foreach($currentPeriodAssignments as $a)
                        <div class="flex items-center justify-between text-sm bg-slate-800 rounded-lg px-3 py-2">
                            <div>
                                <p class="font-semibold">#{{ $a->subOrder?->sub_order_number ?? $a->id }}</p>
                                <p class="text-xs text-slate-400">{{ $a->delivered_at?->format('H:i') }}</p>
                            </div>
                            <p class="font-bold text-yellow-300">{{ codCents($a->cod_amount_collected_cents, $currency) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-center text-xs text-slate-500 py-2">لا توجد توصيلات نقدية اليوم</p>
        @endif
    </div>

    {{-- ── Settlements History ──────────────────────────────────────────────── --}}
    <div>
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">سجل التسويات</h2>

        @forelse($settlements as $settlement)
            @php
                $chip = $statusChipMap[$settlement->status] ?? ['class' => 'chip-cod-pending', 'label' => $settlement->status];
            @endphp

            <div class="d-card mb-3" x-data="{ open: false }">

                {{-- Settlement header row --}}
                <button type="button"
                        class="w-full flex items-start justify-between gap-2 text-right"
                        @click="open = !open">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="chip {{ $chip['class'] }} text-xs">{{ $chip['label'] }}</span>
                            <span class="text-xs text-slate-400">
                                {{ $settlement->period_start->format('Y/m/d') }}
                                —
                                {{ $settlement->period_end->format('Y/m/d') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center mt-3">
                            <div>
                                <p class="text-sm font-bold text-slate-100">{{ codCents($settlement->total_cod_collected_cents, $currency) }}</p>
                                <p class="text-xs text-slate-500">النقد</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-green-400">{{ codCents($settlement->total_earnings_owed_cents, $currency) }}</p>
                                <p class="text-xs text-slate-500">عمولتي</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold {{ $settlement->net_to_remit_cents > 0 ? 'text-yellow-400' : 'text-slate-400' }}">
                                    {{ codCents($settlement->net_to_remit_cents, $currency) }}
                                </p>
                                <p class="text-xs text-slate-500">المستحق</p>
                            </div>
                        </div>
                    </div>

                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0 mt-1 transition-transform duration-200"
                         :class="{ 'rotate-180': open }"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>

                {{-- Delivery breakdown --}}
                <div x-show="open" x-collapse class="border-t border-slate-700 mt-3 pt-3 space-y-2">
                    <p class="text-xs font-semibold text-slate-400 mb-2">التوصيلات المشمولة</p>

                    @forelse($settlement->assignments as $a)
                        <div class="flex items-center justify-between text-sm bg-slate-800 rounded-lg px-3 py-2">
                            <div>
                                <p class="font-semibold">#{{ $a->subOrder?->sub_order_number ?? $a->id }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $a->delivered_at?->format('Y/m/d H:i') ?? '—' }}
                                </p>
                            </div>
                            <p class="font-bold text-yellow-300">
                                {{ codCents($a->cod_amount_collected_cents ?? 0, $currency) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-2">لا توجد توصيلات مرتبطة</p>
                    @endforelse

                    @if($settlement->status === 'pending' && $settlement->net_to_remit_cents > 0)
                        <div class="mt-3 p-3 rounded-xl bg-yellow-900/30 border border-yellow-700/40 text-yellow-300 text-xs leading-relaxed">
                            يرجى تسليم مبلغ
                            <span class="font-bold">{{ codCents($settlement->net_to_remit_cents, $currency) }}</span>
                            إلى المشرف لإتمام التسوية.
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="d-card text-center py-10">
                <div class="text-4xl mb-3">📋</div>
                <p class="font-semibold text-slate-300">لا توجد تسويات بعد</p>
                <p class="text-sm text-slate-500 mt-1">ستظهر تسوياتك النقدية هنا</p>
            </div>
        @endforelse

        {{ $settlements->links() }}
    </div>

</div>

@endsection
