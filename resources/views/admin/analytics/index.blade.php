@extends('layouts.admin')

@push('styles')
    @vite('resources/js/admin/analytics.js')
@endpush

@section('title', 'Analytics')

@section('content')

    {{-- ─── Page Header ──────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">Platform-wide performance metrics and insights.</p>
        </div>

        {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-2">

            {{-- Country filter --}}
            <select id="filter-country" class="form-input py-1.5 text-sm w-36">
                <option value="">All Countries</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                @endforeach
            </select>

            {{-- Period tabs --}}
            <div class="inline-flex rounded-lg shadow-sm border border-gray-300 overflow-hidden" role="group" id="period-tabs">
                @foreach ([
                    'today'   => 'Today',
                    'week'    => '7D',
                    'month'   => '30D',
                    'quarter' => '90D',
                    'year'    => '1Y',
                    'custom'  => 'Custom',
                ] as $key => $label)
                    <button
                        type="button"
                        data-period="{{ $key }}"
                        class="period-btn px-3 py-1.5 text-sm font-medium border-r border-gray-300 last:border-r-0 transition-colors
                               {{ $key === 'month' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Custom date range (hidden by default) --}}
            <div id="custom-date-range" class="hidden flex items-center gap-1">
                <input type="date" id="date-from" class="form-input py-1.5 text-sm w-36" />
                <span class="text-gray-500 text-sm">–</span>
                <input type="date" id="date-to" class="form-input py-1.5 text-sm w-36" />
                <button id="apply-custom" type="button" class="btn btn-primary btn-sm">Apply</button>
            </div>
        </div>
    </div>

    {{-- ─── Section 1: KPI Cards ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6" id="kpi-grid">
        <div id="kpi-gmv">
            <x-stat-card title="Gross Merchandise Value" value="—" icon="banknotes" iconBg="bg-primary-100 text-primary-600" :loading="true" />
        </div>
        <div id="kpi-revenue">
            <x-stat-card title="Platform Revenue" value="—" icon="chart-bar" iconBg="bg-success-100 text-success-600" :loading="true" />
        </div>
        <div id="kpi-orders">
            <x-stat-card title="Total Orders" value="—" icon="shopping-bag" iconBg="bg-blue-100 text-blue-600" :loading="true" />
        </div>
        <div id="kpi-aov">
            <x-stat-card title="Avg. Order Value" value="—" icon="currency-dollar" iconBg="bg-indigo-100 text-indigo-600" :loading="true" />
        </div>
        <div id="kpi-new-customers">
            <x-stat-card title="New Customers" value="—" icon="user-plus" iconBg="bg-teal-100 text-teal-600" :loading="true" />
        </div>
        <div id="kpi-active-vendors">
            <x-stat-card title="Active Vendors" value="—" icon="building-storefront" iconBg="bg-orange-100 text-orange-600" :loading="true" />
        </div>
        <div id="kpi-sla">
            <x-stat-card title="SLA Compliance" value="—" icon="check-badge" iconBg="bg-green-100 text-green-600" :loading="true" />
        </div>
        <div id="kpi-return-rate">
            <x-stat-card title="Return Rate" value="—" icon="arrow-uturn-left" iconBg="bg-red-100 text-red-600" :loading="true" />
        </div>
    </div>

    {{-- ─── Section 2: Revenue Chart ─────────────────────────────────────────────── --}}
    <x-card class="mb-6">
        <x-slot:title>Revenue Overview</x-slot:title>
        <x-slot:actions>
            <div class="inline-flex rounded-lg shadow-sm border border-gray-300 overflow-hidden" id="revenue-range-tabs">
                @foreach (['week' => '7D', 'month' => '30D', 'quarter' => '90D', 'year' => '1Y'] as $k => $l)
                    <button type="button" data-range="{{ $k }}"
                        class="rev-range-btn px-3 py-1 text-xs font-medium border-r border-gray-300 last:border-r-0 transition-colors
                               {{ $k === 'month' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                        {{ $l }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
        <div class="p-6">
            <canvas id="revenue-chart" height="100"></canvas>
        </div>
    </x-card>

    {{-- ─── Section 3: Three Side-by-Side Charts ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Orders by Status --}}
        <x-card>
            <x-slot:title>Orders by Status</x-slot:title>
            <div class="p-6">
                <canvas id="status-chart" height="220"></canvas>
            </div>
        </x-card>

        {{-- Payment Methods --}}
        <x-card>
            <x-slot:title>Payment Methods</x-slot:title>
            <div class="p-6">
                <canvas id="payment-chart" height="220"></canvas>
            </div>
        </x-card>

        {{-- Top Categories --}}
        <x-card>
            <x-slot:title>Revenue by Category</x-slot:title>
            <div class="p-6">
                <canvas id="category-chart" height="220"></canvas>
            </div>
        </x-card>
    </div>

    {{-- ─── Section 4: Top Products & Vendors ────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Top Products --}}
        <x-card>
            <x-slot:title>Top Products</x-slot:title>
            <x-slot:subtitle>By units sold in period</x-slot:subtitle>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">#</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Product</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Units</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="top-products-body" class="divide-y divide-gray-100">
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Top Vendors --}}
        <x-card>
            <x-slot:title>Top Vendors</x-slot:title>
            <x-slot:subtitle>By GMV in period</x-slot:subtitle>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">#</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Store</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Orders</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">GMV</th>
                        </tr>
                    </thead>
                    <tbody id="top-vendors-body" class="divide-y divide-gray-100">
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- ─── Section 5: Customers & Search ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Customer Acquisition --}}
        <x-card>
            <x-slot:title>Customer Acquisition</x-slot:title>
            <div class="p-6 space-y-4">
                <canvas id="customer-acq-chart" height="160"></canvas>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">New Buyers</p>
                        <p id="new-buyers-count" class="text-2xl font-bold text-gray-900">—</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Returning Buyers</p>
                        <p id="returning-buyers-count" class="text-2xl font-bold text-gray-900">—</p>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Search Analytics --}}
        <x-card>
            <x-slot:title>Search Analytics</x-slot:title>
            <div class="p-4 grid grid-cols-3 gap-3 border-b border-gray-100">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Total Searches</p>
                    <p id="total-searches" class="text-xl font-bold text-gray-900">—</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Avg. CTR</p>
                    <p id="search-avg-ctr" class="text-xl font-bold text-gray-900">—</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Zero Results</p>
                    <p id="zero-result-rate" class="text-xl font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="overflow-x-auto max-h-64">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Query</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Searches</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">CTR</th>
                        </tr>
                    </thead>
                    <tbody id="search-table-body" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 text-xs">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- ─── Section 6: Operational Tabs ──────────────────────────────────────────── --}}
    <x-card>
        {{-- Tab headers --}}
        <div class="border-b border-gray-200 px-6">
            <nav class="-mb-px flex gap-1 overflow-x-auto" id="ops-tabs">
                @foreach ([
                    'sla'     => 'SLA Metrics',
                    'ads'     => 'Ad Performance',
                    'flash'   => 'Flash Sales',
                    'returns' => 'Returns',
                    'support' => 'Support',
                ] as $tab => $label)
                    <button type="button" data-tab="{{ $tab }}"
                        class="ops-tab-btn whitespace-nowrap py-3 px-4 text-sm font-medium border-b-2 transition-colors
                               {{ $tab === 'sla'
                                  ? 'border-primary-600 text-primary-600'
                                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- SLA Metrics --}}
        <div id="ops-sla" class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6" id="sla-kpi-row">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Breach Rate</p>
                    <p id="sla-breach-rate" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Compliance</p>
                    <p id="sla-compliance" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Avg. Ship Time</p>
                    <p id="sla-avg-ship" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Avg. Delivery Time</p>
                    <p id="sla-avg-delivery" class="text-2xl font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Breach Rate Trend</h4>
                    <canvas id="sla-trend-chart" height="160"></canvas>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Breach by Vendor (Top 10)</h4>
                    <div class="overflow-x-auto max-h-48">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500">
                                    <th class="text-left pb-2">Store</th>
                                    <th class="text-right pb-2">Orders</th>
                                    <th class="text-right pb-2">Breached</th>
                                    <th class="text-right pb-2">Rate</th>
                                </tr>
                            </thead>
                            <tbody id="sla-vendor-table" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ad Performance --}}
        <div id="ops-ads" class="p-6 hidden">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Impressions</p>
                    <p id="ads-impressions" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Clicks</p>
                    <p id="ads-clicks" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Total Spend</p>
                    <p id="ads-spend" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Attributed Revenue</p>
                    <p id="ads-revenue" class="text-2xl font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Performance Trend</h4>
                    <canvas id="ads-perf-chart" height="160"></canvas>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Top Campaigns</h4>
                    <div class="overflow-x-auto max-h-48">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500">
                                    <th class="text-left pb-2">Campaign</th>
                                    <th class="text-right pb-2">Spend</th>
                                    <th class="text-right pb-2">Revenue</th>
                                    <th class="text-right pb-2">CTR</th>
                                </tr>
                            </thead>
                            <tbody id="ads-campaigns-table" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Sales --}}
        <div id="ops-flash" class="p-6 hidden">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Units Sold</p>
                    <p id="flash-units" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Total Revenue</p>
                    <p id="flash-revenue" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Discount Given</p>
                    <p id="flash-discount" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Avg. Conversion</p>
                    <p id="flash-cvr" class="text-2xl font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Top Flash Sales</h4>
                    <div class="overflow-x-auto max-h-56">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500">
                                    <th class="text-left pb-2">Sale</th>
                                    <th class="text-right pb-2">Units</th>
                                    <th class="text-right pb-2">Revenue</th>
                                    <th class="text-right pb-2">CVR</th>
                                </tr>
                            </thead>
                            <tbody id="flash-sales-table" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Top Vendors in Flash Sales</h4>
                    <div class="overflow-x-auto max-h-56">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500">
                                    <th class="text-left pb-2">Store</th>
                                    <th class="text-right pb-2">Units</th>
                                    <th class="text-right pb-2">Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="flash-vendors-table" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Returns --}}
        <div id="ops-returns" class="p-6 hidden">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Total Returns</p>
                    <p id="ret-total" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Return Rate</p>
                    <p id="ret-rate" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl col-span-2 sm:col-span-1">
                    <p class="text-xs text-gray-500">Most Common Reason</p>
                    <p id="ret-top-reason" class="text-lg font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Returns by Reason</h4>
                    <canvas id="returns-reason-chart" height="180"></canvas>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Monthly Trend</h4>
                    <canvas id="returns-trend-chart" height="180"></canvas>
                </div>
            </div>
        </div>

        {{-- Support --}}
        <div id="ops-support" class="p-6 hidden">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Open Tickets</p>
                    <p id="sup-open" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Avg. First Response</p>
                    <p id="sup-first-resp" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Avg. Resolution</p>
                    <p id="sup-resolution" class="text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-500">Satisfaction Score</p>
                    <p id="sup-satisfaction" class="text-2xl font-bold text-gray-900">—</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Tickets by Category</h4>
                    <canvas id="sup-category-chart" height="180"></canvas>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Resolution Trend</h4>
                    <canvas id="sup-trend-chart" height="180"></canvas>
                </div>
            </div>
        </div>
    </x-card>

@endsection
