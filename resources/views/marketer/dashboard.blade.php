@extends('layouts.marketer')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

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
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Your Referral Link</p>
            <p class="text-slate-200 font-mono text-sm mt-1 truncate max-w-xs" id="global-ref-url">
                {{ 'https://' . env('DEFAULT_COUNTRY_SLUG', 'sa') . '.' . env('APP_DOMAIN', 'localhost') . '?ref=' . $marketer->referral_code }}
            </p>
        </div>
        <button type="button" onclick="copyGlobalRef()"
            class="flex-shrink-0 bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            📋 Copy Link
        </button>
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="kpi-card border-l-4 border-l-blue-500">
            <p class="label">Clicks Today</p>
            <p class="value">{{ number_format($stats['clicks_today']) }}</p>
        </div>
        <div class="kpi-card border-l-4 border-l-purple-500">
            <p class="label">Conversions</p>
            <p class="value">{{ number_format($stats['conversions_today']) }}</p>
            <p class="sub">This month: {{ number_format($stats['conversions_month']) }}</p>
        </div>
        <div class="kpi-card border-l-4 border-l-yellow-400">
            <p class="label">Pending Earnings</p>
            <p class="value">{{ number_format($stats['pending_earnings'] / 100, 2) }}</p>
            <p class="sub">SAR</p>
        </div>
        <div class="kpi-card border-l-4 border-l-green-500">
            <p class="label">Conversion Rate</p>
            <p class="value">{{ $stats['conversion_rate'] }}%</p>
            <p class="sub">Paid: {{ number_format($stats['paid_earnings'] / 100, 2) }} SAR</p>
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
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Commission Tier</p>
                    <p class="font-bold text-gray-800 mt-0.5">
                        {{ $currentTier ? 'Tier ' . $currentTier->tier_order . ' — ' . $currentTier->commission_rate . '%' : 'Default — ' . $marketer->commission_rate . '%' }}
                    </p>
                </div>
                @if($nextTier)
                    <div class="text-right">
                        <p class="text-xs text-gray-400">Next tier</p>
                        <p class="font-bold text-yellow-500">{{ $nextTier->commission_rate }}%</p>
                        <p class="text-xs text-gray-400">at {{ number_format($nextTier->min_sales_count) }} sales</p>
                    </div>
                @else
                    <span class="badge badge-success text-xs">Top Tier 🏆</span>
                @endif
            </div>
            @if($nextTier)
                <div class="bg-gray-100 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-500 transition-all duration-700"
                        style="width: {{ $progress }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">
                    {{ number_format($salesCount) }} / {{ number_format($nextTier->min_sales_count) }} sales
                    — {{ $nextTier->min_sales_count - $salesCount }} more to unlock {{ $nextTier->commission_rate }}%
                </p>
            @endif
        </div>
    @endif

    {{-- ── Revenue Trend Chart ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">Revenue Trend</h3>
            <span class="text-xs text-gray-400">Last 30 days</span>
        </div>
        <canvas id="revenue-chart" height="80"></canvas>
    </div>

    {{-- ── Top Campaigns ────────────────────────────────────────────────────────── --}}
    @if($topCampaigns->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800">Top Performing Campaigns</h3>
                <a href="{{ route('marketer.campaigns.index') }}" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left font-semibold text-gray-500 pb-2">Campaign</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">Clicks</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">Conv.</th>
                            <th class="text-right font-semibold text-gray-500 pb-2">Revenue</th>
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
                                    {{ number_format($campaign->total_revenue_cents / 100, 2) }}
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
            <p class="font-semibold text-sm text-gray-700 group-hover:text-yellow-700">New Campaign</p>
        </a>
        <a href="{{ route('marketer.earnings.index') }}"
            class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-5 text-center hover:border-green-400 hover:bg-green-50 transition-colors group">
            <div class="text-2xl mb-1">💰</div>
            <p class="font-semibold text-sm text-gray-700 group-hover:text-green-700">My Earnings</p>
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
                    label: 'Earnings (SAR)',
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
            navigator.clipboard.writeText(url).then(() => {
                const btn = event.currentTarget;
                btn.textContent = '✓ Copied!';
                setTimeout(() => { btn.textContent = '📋 Copy Link'; }, 2000);
            });
        }
    </script>
@endpush
