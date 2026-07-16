@extends('layouts.marketer')

@section('title', __('marketer.nav.dashboard'))
@section('page-title', __('marketer.nav.dashboard'))

@push('styles')
    <style>
        .kpi-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
        }

        .kpi-card .label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .kpi-card .value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 0.25rem;
            line-height: 1;
        }

        .kpi-card .sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
    </style>
@endpush

@section('content')

    {{-- ── Referral link quick copy ─────────────────────────────────────────────── --}}
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-5 mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">{{ __('marketer.dashboard.referral_link') }}</p>
            <p class="text-slate-200 font-mono text-sm mt-1 truncate max-w-xs" id="global-ref-url">
                {{ 'https://' . env('DEFAULT_COUNTRY_SLUG', 'sa') . '.' . env('APP_DOMAIN', 'localhost') . '?ref=' . $marketer->referral_code }}
            </p>
        </div>
        <button type="button" onclick="copyGlobalRef()"
            class="flex-shrink-0 bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            {{ __('marketer.dashboard.copy_link') }}
        </button>
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="kpi-card border-s-4 border-s-blue-500">
            <p class="label">{{ __('marketer.dashboard.clicks_today') }}</p>
            <p class="value">{{ number_format($stats['clicks_today']) }}</p>
        </div>
        <div class="kpi-card border-s-4 border-s-purple-500">
            <p class="label">{{ __('marketer.dashboard.conversions') }}</p>
            <p class="value">{{ number_format($stats['conversions_today']) }}</p>
            <p class="sub">{{ __('marketer.dashboard.this_month') }}: {{ number_format($stats['conversions_month']) }}</p>
        </div>
        <div class="kpi-card border-s-4 border-s-yellow-400">
            <p class="label">{{ __('marketer.dashboard.pending_earnings') }}</p>
            @if($stats['pending_by_currency']->isEmpty())
                <p class="value">—</p>
            @else
                @foreach($stats['pending_by_currency'] as $currency => $cents)
                    <p class="value" style="font-size:1.3rem">{{ number_format($cents / 100, 2) }}</p>
                    <p class="sub">{{ $currency }}</p>
                @endforeach
            @endif
        </div>
        <div class="kpi-card border-s-4 border-s-green-500">
            <p class="label">{{ __('marketer.dashboard.conversion_rate') }}</p>
            <p class="value">{{ $stats['conversion_rate'] }}%</p>
            @foreach($stats['paid_by_currency'] as $currency => $cents)
                <p class="sub">{{ __('marketer.dashboard.paid') }}: {{ number_format($cents / 100, 2) }} {{ $currency }}</p>
            @endforeach
        </div>
    </div>

    {{-- ── Tier Progress Card ───────────────────────────────────────────────────── --}}
    @if($nextTier || $currentTier)
        @php
            $salesCount = $salesCount ?? 0;
            $tierTarget = $nextTier?->min_sales_count ?? ($currentTier?->max_sales_count);
            $tierFrom = $currentTier?->min_sales_count ?? 0;
            $progress = $tierTarget && $tierTarget > $tierFrom
                ? min(100, round((($salesCount - $tierFrom) / ($tierTarget - $tierFrom)) * 100))
                : 100;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.dashboard.commission_tier') }}</p>
                    <p class="font-bold text-gray-800 mt-0.5">
                        {{ $currentTier ? __('marketer.dashboard.tier') . ' ' . $currentTier->tier_order . ' — ' . $currentTier->commission_rate . '%' : __('marketer.dashboard.default_rate') . ' — ' . $marketer->commission_rate . '%' }}
                    </p>
                </div>
                @if($nextTier)
                    <div class="text-right">
                        <p class="text-xs text-gray-400">{{ __('marketer.dashboard.next_tier') }}</p>
                        <p class="font-bold text-yellow-500">{{ $nextTier->commission_rate }}%</p>
                        <p class="text-xs text-gray-400">{{ __('marketer.dashboard.at_sales', ['count' => number_format($nextTier->min_sales_count)]) }}</p>
                    </div>
                @else
                    <span class="badge badge-success text-xs">{{ __('marketer.dashboard.top_tier') }}</span>
                @endif
            </div>
            @if($nextTier)
                <div class="bg-gray-100 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-500 transition-all duration-700"
                        style="width: {{ $progress }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">
                    {{ __('marketer.dashboard.sales_of', ['current' => number_format($salesCount), 'target' => number_format($nextTier->min_sales_count)]) }}
                    — {{ $nextTier->min_sales_count - $salesCount }} {{ __('marketer.dashboard.more_to_unlock', ['rate' => $nextTier->commission_rate]) }}
                </p>
            @endif
        </div>
    @endif

    {{-- ── Revenue Trend Chart ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">{{ __('marketer.dashboard.revenue_trend') }}</h3>
            <span class="text-xs text-gray-400">{{ __('marketer.dashboard.last_30_days') }}</span>
        </div>
        <canvas id="revenue-chart" height="80"></canvas>
    </div>

    {{-- ── Top Campaigns ────────────────────────────────────────────────────────── --}}
    @if($topCampaigns->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800">{{ __('marketer.dashboard.top_campaigns') }}</h3>
                <a href="{{ route('marketer.campaigns.index') }}" class="text-xs text-blue-600 hover:underline">{{ __('marketer.dashboard.view_all') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left font-semibold text-gray-500 pb-2">{{ __('marketer.dashboard.campaign') }}</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">{{ __('marketer.dashboard.clicks') }}</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">{{ __('marketer.dashboard.conv') }}</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">{{ __('marketer.dashboard.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCampaigns as $campaign)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="py-2.5">
                                    <a href="{{ route('marketer.campaigns.show', $campaign->id) }}"
                                        class="font-medium text-gray-800 hover:text-blue-600">
                                        {{ $campaign->name }}
                                    </a>
                                </td>
                                <td class="py-2.5 text-right text-gray-600">{{ number_format($campaign->total_clicks) }}</td>
                                <td class="py-2.5 text-right text-gray-600">{{ number_format($campaign->total_conversions) }}</td>
                                <td class="py-2.5 text-right font-semibold text-green-600">
                                    {{ number_format($campaign->total_revenue, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Quick Actions ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('marketer.campaigns.create') }}"
            class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-5 text-center hover:border-yellow-400 hover:bg-yellow-50 transition-colors group">
            <div class="text-2xl mb-1">🚀</div>
            <p class="font-semibold text-sm text-gray-700 group-hover:text-yellow-700">{{ __('marketer.dashboard.new_campaign') }}</p>
        </a>
        <a href="{{ route('marketer.earnings.index') }}"
            class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-5 text-center hover:border-green-400 hover:bg-green-50 transition-colors group">
            <div class="text-2xl mb-1">💰</div>
            <p class="font-semibold text-sm text-gray-700 group-hover:text-green-700">{{ __('marketer.dashboard.my_earnings') }}</p>
        </a>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Earnings',
                    data: @json($chartData),
                    borderColor: '#facc15',
                    backgroundColor: 'rgba(250,204,21,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { ticks: { maxTicksLimit: 8, font: { size: 10 } }, grid: { display: false } },
                    y: { ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } }
                },
                plugins: { legend: { display: false } }
            }
        });

        function copyGlobalRef() {
            const url = document.getElementById('global-ref-url').textContent.trim();
            const btn = event.currentTarget;
            copyToClipboard(url).then(() => {
                btn.textContent = @json(__('marketer.dashboard.copied'));
                setTimeout(() => { btn.textContent = @json(__('marketer.dashboard.copy_link')); }, 2000);
            });
        }
    </script>
@endpush
