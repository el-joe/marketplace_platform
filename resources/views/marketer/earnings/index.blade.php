@extends('layouts.marketer')

@section('title', __('marketer.earnings.title'))
@section('page-title', __('marketer.earnings.title'))

@section('content')

{{-- ── Summary Cards ────────────────────────────────────────────────────────── --}}
{{-- Each card may show multiple currency rows when the marketer earns in more than one country. --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.earnings.pending_summary') }}</p>
        @forelse($summary['pending'] as $currency => $cents)
            <p class="text-2xl font-extrabold text-yellow-600 mt-1">
                {{ number_format($cents / 100, 2) }} <span class="text-sm font-normal text-gray-400">{{ $currency }}</span>
            </p>
        @empty
            <p class="text-2xl font-extrabold text-yellow-600 mt-1">—</p>
        @endforelse
        <p class="text-xs text-gray-400 mt-0.5">{{ __('marketer.earnings.awaiting_return_window') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.earnings.approved_summary') }}</p>
        @forelse($summary['approved'] as $currency => $cents)
            <p class="text-2xl font-extrabold text-blue-600 mt-1">
                {{ number_format($cents / 100, 2) }} <span class="text-sm font-normal text-gray-400">{{ $currency }}</span>
            </p>
        @empty
            <p class="text-2xl font-extrabold text-blue-600 mt-1">—</p>
        @endforelse
        <p class="text-xs text-gray-400 mt-0.5">{{ __('marketer.earnings.ready_for_payout') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.earnings.paid_out') }}</p>
        @forelse($summary['paid'] as $currency => $cents)
            <p class="text-2xl font-extrabold text-green-600 mt-1">
                {{ number_format($cents / 100, 2) }} <span class="text-sm font-normal text-gray-400">{{ $currency }}</span>
            </p>
        @empty
            <p class="text-2xl font-extrabold text-green-600 mt-1">—</p>
        @endforelse
        <p class="text-xs text-gray-400 mt-0.5">{{ __('marketer.earnings.total_received') }}</p>
    </div>
</div>

<form method="GET" action="{{ route('marketer.earnings.index') }}" class="flex flex-wrap items-end gap-3 mb-4 bg-white rounded-2xl border border-gray-100 p-4">
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_from') }}</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_to') }}</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <button type="submit" class="bg-slate-800 text-white text-sm font-semibold rounded-lg px-4 py-2">{{ __('common.filter') }}</button>
    <div class="ml-auto">
        <x-export-dropdown />
    </div>
</form>

{{-- ── Tabs ─────────────────────────────────────────────────────────────────── --}}
<div x-data="{ tab: 'pending' }">

    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 w-fit">
        @foreach(['pending' => __('marketer.earnings.pending_tab'), 'approved' => __('marketer.earnings.approved_tab'), 'payouts' => __('marketer.earnings.payout_history')] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors"
                    :class="tab === '{{ $key }}' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                {{ $label }}
                @if($key === 'pending' && $pending->total() > 0)
                    <span class="ml-1 bg-yellow-400 text-slate-900 text-xs rounded-full w-4 h-4 inline-flex items-center justify-center font-bold">
                        {{ min($pending->total(), 99) }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── Pending ──────────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'pending'">
        @if($pending->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center">
                <div class="text-3xl mb-2">⏳</div>
                <p class="font-semibold text-gray-600">{{ __('marketer.earnings.no_pending') }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.campaign') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.order_value') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.commission') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $conv)
                            <tr class="border-t border-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-700">{{ $conv->campaign?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($conv->order_value, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-yellow-600">
                                    {{ number_format($conv->commission_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400 text-xs">{{ $conv->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($pending->hasPages())
                <div class="mt-4">{{ $pending->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ── Approved ─────────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'approved'">
        @if($approved->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center">
                <div class="text-3xl mb-2">✅</div>
                <p class="font-semibold text-gray-600">{{ __('marketer.earnings.no_approved') }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.campaign') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.order_value') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.commission') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.approved_on') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approved as $conv)
                            <tr class="border-t border-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-700">{{ $conv->campaign?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($conv->order_value, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                    {{ number_format($conv->commission_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400 text-xs">
                                    {{ $conv->approved_at?->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($approved->hasPages())
                <div class="mt-4">{{ $approved->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ── Payout History ───────────────────────────────────────────────────── --}}
    <div x-show="tab === 'payouts'">
        @if($payouts->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center">
                <div class="text-3xl mb-2">💸</div>
                <p class="font-semibold text-gray-600">{{ __('marketer.earnings.no_payouts') }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ __('marketer.earnings.payouts_processed_weekly') }}</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.payout_number') }}</th>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.period') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.conversions_count') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.net_amount') }}</th>
                            <th class="text-center font-semibold text-gray-500 px-4 py-3">{{ __('marketer.earnings.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payouts as $payout)
                            @php
                                $payoutColor = [
                                    'paid'      => 'bg-green-100 text-green-700',
                                    'approved'  => 'bg-blue-100 text-blue-700',
                                    'pending'   => 'bg-yellow-100 text-yellow-700',
                                    'failed'    => 'bg-red-100 text-red-600',
                                ][$payout->status->value] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="border-t border-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $payout->payout_number }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $payout->period_start }} – {{ $payout->period_end }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($payout->total_conversions) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-800">
                                    {{ number_format($payout->net_amount, 2) }} {{ $payout->currency }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $payoutColor }}">
                                        {{ $payout->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>{{-- end x-data tabs --}}

@endsection
