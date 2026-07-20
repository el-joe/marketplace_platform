@extends('layouts.marketer')

@section('title', __('marketer.analytics.title'))
@section('page-title', __('marketer.analytics.title'))

@section('content')

{{-- ── Date Range Filter ────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('marketer.analytics.index') }}" class="bg-white rounded-2xl border border-gray-100 p-4 mb-6 flex flex-wrap items-end gap-3" x-data="{ range: '{{ $range }}' }">
    <div>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">{{ __('marketer.analytics.title') }}</p>
        <p class="text-sm text-gray-500">{{ __('marketer.analytics.subtitle') }}</p>
    </div>

    <div class="ms-auto flex flex-wrap items-end gap-2">
        <select name="range" x-model="range" onchange="this.form.submit()"
            class="rounded-lg border border-gray-200 text-sm px-3 py-2">
            <option value="7d" {{ $range === '7d' ? 'selected' : '' }}>{{ __('marketer.analytics.range_7d') }}</option>
            <option value="30d" {{ $range === '30d' ? 'selected' : '' }}>{{ __('marketer.analytics.range_30d') }}</option>
            <option value="90d" {{ $range === '90d' ? 'selected' : '' }}>{{ __('marketer.analytics.range_90d') }}</option>
            <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>{{ __('marketer.analytics.range_custom') }}</option>
        </select>

        <div x-show="range === 'custom'" class="flex items-end gap-2">
            <input type="text" id="custom-range" name="custom_range_display" readonly
                class="rounded-lg border border-gray-200 text-sm px-3 py-2 w-56"
                placeholder="{{ __('marketer.analytics.range_custom') }}"
                value="{{ $from->toDateString() }} to {{ $to->toDateString() }}">
            <input type="hidden" name="from" id="from-input" value="{{ $from->toDateString() }}">
            <input type="hidden" name="to" id="to-input" value="{{ $to->toDateString() }}">
            <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-slate-900 text-sm font-semibold px-4 py-2 rounded-lg">
                {{ __('marketer.analytics.apply') }}
            </button>
        </div>
    </div>
</form>

{{-- ── Summary Cards ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @if($hasClicksTable)
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.total_clicks') }}</p>
            <p class="text-xl font-extrabold text-gray-800 mt-1">{{ number_format($totalClicks) }}</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.total_conversions') }}</p>
        <p class="text-xl font-extrabold text-gray-800 mt-1">{{ number_format($totalConversions) }}</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.conversion_rate') }}</p>
        <p class="text-xl font-extrabold text-gray-800 mt-1">
            {{ $conversionRate !== null ? $conversionRate . '%' : __('marketer.analytics.not_available') }}
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4 col-span-2">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.commission_earned') }}</p>
        @forelse($earnedByCurrency as $currency => $amount)
            <p class="text-lg font-extrabold text-green-600 mt-1">
                {{ number_format($amount, 2) }} <span class="text-xs font-normal text-gray-400">{{ $currency }}</span>
            </p>
        @empty
            <p class="text-xl font-extrabold text-gray-800 mt-1">—</p>
        @endforelse
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.active_campaigns') }}</p>
        <p class="text-xl font-extrabold text-gray-800 mt-1">{{ number_format($activeCampaignsCount) }}</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4 col-span-2 sm:col-span-3 lg:col-span-6">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">{{ __('marketer.analytics.best_campaign') }}</p>
        <p class="text-lg font-bold text-gray-800 mt-1">
            {{ $bestCampaign?->name ?? __('marketer.analytics.not_available') }}
        </p>
    </div>
</div>

{{-- ── Charts ───────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5 lg:col-span-2">
        <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.analytics.daily_conversions') }}</h3>
        <canvas id="conversions-line-chart" height="80"></canvas>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.analytics.earnings_per_campaign') }}</h3>
        <canvas id="earnings-bar-chart" height="200"></canvas>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.analytics.conversions_by_type') }}</h3>
        <canvas id="type-donut-chart" height="200"></canvas>
    </div>
</div>

{{-- ── Per-Campaign Breakdown Table ────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="p-5 border-b border-gray-50">
        <h3 class="font-bold text-gray-800">{{ __('marketer.analytics.breakdown_title') }}</h3>
    </div>

    @if($campaignBreakdown->isEmpty())
        <div class="p-10 text-center">
            <p class="font-semibold text-gray-500">{{ __('marketer.analytics.no_data') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.campaign') }}</th>
                        <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.type') }}</th>
                        @if($hasClicksTable)
                            <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.clicks') }}</th>
                        @endif
                        <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.conversions') }}</th>
                        <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.conv_rate') }}</th>
                        <th class="text-right font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.earned') }}</th>
                        <th class="text-center font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.currency') }}</th>
                        <th class="text-center font-semibold text-gray-500 px-4 py-3">{{ __('marketer.analytics.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaignBreakdown as $campaign)
                        <tr class="border-t border-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-700">
                                <a href="{{ route('marketer.campaigns.show', $campaign->id) }}" class="hover:text-blue-600">
                                    {{ $campaign->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $campaign->campaign_type->label() }}</td>
                            @if($hasClicksTable)
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($campaign->period_clicks) }}</td>
                            @endif
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($campaign->period_conversions) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                {{ $campaign->conv_rate !== null ? $campaign->conv_rate . '%' : __('marketer.analytics.not_available') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format($campaign->period_earned, 2) }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $campaign->period_currency ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 bg-{{ $campaign->status_color }}-100 text-{{ $campaign->status_color }}-700">
                                    {{ $campaign->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script>
        // Line chart: daily conversions
        new Chart(document.getElementById('conversions-line-chart').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($lineLabels),
                datasets: [{
                    label: @json(__('marketer.analytics.daily_conversions')),
                    data: @json($lineData),
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
                    x: { ticks: { maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
                    y: { ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Bar chart: earnings per campaign (top 5)
        new Chart(document.getElementById('earnings-bar-chart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($earningsPerCampaign->pluck('name')),
                datasets: [{
                    label: @json(__('marketer.analytics.earnings_per_campaign')),
                    data: @json($earningsPerCampaign->pluck('earned')),
                    backgroundColor: '#22c55e',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                    y: { ticks: { font: { size: 10 } }, grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Donut chart: conversions by campaign type
        new Chart(document.getElementById('type-donut-chart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($conversionsByType->keys()),
                datasets: [{
                    data: @json($conversionsByType->values()),
                    backgroundColor: ['#facc15', '#22c55e', '#3b82f6', '#f97316', '#a855f7', '#ef4444', '#14b8a6', '#6366f1', '#ec4899', '#84cc16', '#0ea5e9'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
            }
        });

        @if($range === 'custom')
            flatpickr('#custom-range', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: [@json($from->toDateString()), @json($to->toDateString())],
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        document.getElementById('from-input').value = selectedDates[0].toISOString().slice(0, 10);
                        document.getElementById('to-input').value = selectedDates[1].toISOString().slice(0, 10);
                    }
                }
            });
        @endif
    </script>
@endpush
