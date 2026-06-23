import {
    Chart,
    LineElement,
    BarElement,
    PointElement,
    LinearScale,
    CategoryScale,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
    LineController,
    BarController,
    DoughnutController,
} from 'chart.js';

Chart.register(
    LineElement, BarElement, PointElement, LinearScale, CategoryScale,
    ArcElement, Tooltip, Legend, Filler,
    LineController, BarController, DoughnutController,
);

// ── State ─────────────────────────────────────────────────────────────────────
const state = {
    period: 'month',
    dateFrom: null,
    dateTo: null,
    countryId: '',
};

// ── Chart instances ───────────────────────────────────────────────────────────
let revenueChart = null;
let statusChart = null;
let paymentChart = null;
let categoryChart = null;
let customerAcqChart = null;
let slaTrendChart = null;
let adsPerfChart = null;
let retReasonChart = null;
let retTrendChart = null;
let supCatChart = null;
let supTrendChart = null;

// Tabs loaded flags (lazy loading for operational tabs)
const tabsLoaded = { sla: false, ads: false, flash: false, returns: false, support: false };

// ── Helpers ───────────────────────────────────────────────────────────────────

function params() {
    const p = { period: state.period, country_id: state.countryId };
    if (state.period === 'custom' && state.dateFrom && state.dateTo) {
        p.date_from = state.dateFrom;
        p.date_to = state.dateTo;
    }
    return p;
}

function formatMoney(v) {
    return new Intl.NumberFormat('en', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(v);
}

function formatNumber(v) {
    return new Intl.NumberFormat('en').format(v);
}

function formatPct(v) {
    return (v !== null && v !== undefined) ? v.toFixed(1) + '%' : '—';
}

function changeHtml(metric) {
    if (metric.change_pct === null || metric.change_pct === undefined) return '';
    const isUp = metric.direction === 'up';
    const arrow = isUp ? '↑' : '↓';
    const cls = isUp ? 'text-success-600' : 'text-danger-600';
    const sign = isUp ? '+' : '';
    return `<p class="mt-2 text-xs ${cls} font-medium">${arrow} ${sign}${metric.change_pct.toFixed(1)}% <span class="text-gray-400 font-normal">vs prev. period</span></p>`;
}

function updateKpiCard(containerId, value, change) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const valEl = container.querySelector('p.text-2xl');
    if (valEl) valEl.textContent = value;
    // Append change indicator below value
    let changeEl = container.querySelector('.kpi-change');
    if (!changeEl) {
        changeEl = document.createElement('div');
        changeEl.className = 'kpi-change';
        if (valEl) valEl.insertAdjacentElement('afterend', changeEl);
    }
    changeEl.innerHTML = changeHtml(change);
    // Remove skeleton
    container.querySelector('.animate-pulse')?.remove();
}

function destroyChart(chart) {
    if (chart) { chart.destroy(); }
    return null;
}

// ── Section Loaders ───────────────────────────────────────────────────────────

function loadOverview() {
    $.get('/analytics/overview', params(), function (res) {
        const d = res.data;

        updateKpiCard('kpi-gmv', formatMoney(d.gmv.value) + ' EGP', d.gmv);
        updateKpiCard('kpi-revenue', formatMoney(d.revenue.value) + ' EGP', d.revenue);
        updateKpiCard('kpi-orders', formatNumber(d.orders_count.value), d.orders_count);
        updateKpiCard('kpi-aov', formatMoney(d.avg_order_value.value) + ' EGP', d.avg_order_value);
        updateKpiCard('kpi-new-customers', formatNumber(d.new_customers.value), d.new_customers);
        updateKpiCard('kpi-active-vendors', formatNumber(d.active_vendors.value), d.active_vendors);
        updateKpiCard('kpi-sla', d.sla_compliance.value.toFixed(1) + '%', d.sla_compliance);
        updateKpiCard('kpi-return-rate', d.return_rate.value.toFixed(2) + '%', d.return_rate);
    });
}

function loadRevenueChart(rangePeriod) {
    const p = { ...params(), period: rangePeriod };

    $.get('/analytics/revenue-chart', p, function (res) {
        const d = res.data;

        revenueChart = destroyChart(revenueChart);
        const ctx = document.getElementById('revenue-chart');
        if (!ctx) return;

        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.labels,
                datasets: [
                    {
                        label: 'GMV',
                        data: d.gmv,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.08)',
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Platform Revenue',
                        data: d.commission,
                        borderColor: '#22c55e',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                    },
                    {
                        label: 'Vendor Payouts',
                        data: d.payouts,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                    },
                    {
                        label: 'Refunds',
                        data: d.refunds,
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        borderDash: [4, 4],
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return ctx.dataset.label + ': ' +
                                    new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(ctx.raw) + ' EGP';
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        ticks: {
                            callback(v) {
                                return (v / 1000).toFixed(0) + 'K';
                            },
                        },
                    },
                },
            },
        });
    });
}

function loadOrdersByStatus() {
    $.get('/analytics/orders-by-status', params(), function (res) {
        const d = res.data;

        statusChart = destroyChart(statusChart);
        const ctx = document.getElementById('status-chart');
        if (!ctx) return;

        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: d.labels,
                datasets: [{ data: d.counts, backgroundColor: d.colors, borderWidth: 1 }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                },
                cutout: '65%',
            },
        });
    });
}

function loadPaymentMethods() {
    $.get('/analytics/orders-by-payment', params(), function (res) {
        const d = res.data;

        paymentChart = destroyChart(paymentChart);
        const ctx = document.getElementById('payment-chart');
        if (!ctx) return;

        paymentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [
                    {
                        label: 'Orders',
                        data: d.counts,
                        backgroundColor: '#6366f1',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } },
            },
        });
    });
}

function loadTopCategories() {
    $.get('/analytics/top-categories', params(), function (res) {
        const d = res.data;

        categoryChart = destroyChart(categoryChart);
        const ctx = document.getElementById('category-chart');
        if (!ctx) return;

        categoryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: d.revenues,
                        backgroundColor: '#22c55e',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: v => (v / 1000).toFixed(0) + 'K' },
                    },
                },
            },
        });
    });
}

function loadTopProducts() {
    $.get('/analytics/top-products', params(), function (res) {
        const tbody = document.getElementById('top-products-body');
        if (!tbody) return;

        if (!res.data || !res.data.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-sm">No data</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map((row, i) => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-400 text-xs">${i + 1}</td>
                <td class="px-4 py-2">
                    <p class="font-medium text-gray-900 truncate max-w-xs">${row.name}</p>
                    <p class="text-xs text-gray-400">${row.sku ?? ''}</p>
                </td>
                <td class="px-4 py-2 text-right text-gray-700">${formatNumber(row.units_sold)}</td>
                <td class="px-4 py-2 text-right font-medium text-gray-900">${formatMoney(row.revenue)}</td>
            </tr>
        `).join('');
    });
}

function loadTopVendors() {
    $.get('/analytics/top-vendors', params(), function (res) {
        const tbody = document.getElementById('top-vendors-body');
        if (!tbody) return;

        if (!res.data || !res.data.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-sm">No data</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map((row, i) => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-400 text-xs">${i + 1}</td>
                <td class="px-4 py-2">
                    <p class="font-medium text-gray-900">${row.store_name}</p>
                    <p class="text-xs text-gray-400">★ ${row.rating.toFixed(1)}</p>
                </td>
                <td class="px-4 py-2 text-right text-gray-700">${formatNumber(row.orders_count)}</td>
                <td class="px-4 py-2 text-right font-medium text-gray-900">${formatMoney(row.gmv)}</td>
            </tr>
        `).join('');
    });
}

function loadCustomerStats() {
    $.get('/analytics/customers', params(), function (res) {
        const d = res.data;

        // New buyers / returning counts
        const nbEl = document.getElementById('new-buyers-count');
        const rbEl = document.getElementById('returning-buyers-count');
        if (nbEl) nbEl.textContent = formatNumber(d.returning_vs_new.new_buyers);
        if (rbEl) rbEl.textContent = formatNumber(d.returning_vs_new.returning_buyers);

        // Acquisition chart
        customerAcqChart = destroyChart(customerAcqChart);
        const ctx = document.getElementById('customer-acq-chart');
        if (!ctx) return;

        customerAcqChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.acquisition_chart.labels,
                datasets: [
                    {
                        label: 'New Customers',
                        data: d.acquisition_chart.counts,
                        backgroundColor: '#6366f1',
                        borderRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    });
}

function loadSearchAnalytics() {
    $.get('/analytics/search', params(), function (res) {
        const d = res.data;

        const tsEl = document.getElementById('total-searches');
        const ctrEl = document.getElementById('search-avg-ctr');
        const zrEl = document.getElementById('zero-result-rate');
        if (tsEl) tsEl.textContent = formatNumber(d.total_searches);
        if (ctrEl) ctrEl.textContent = formatPct(d.avg_ctr);
        if (zrEl) zrEl.textContent = formatPct(d.zero_result_rate);

        const tbody = document.getElementById('search-table-body');
        if (!tbody) return;

        if (!d.top_queries || !d.top_queries.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-gray-400 text-xs">No data</td></tr>';
            return;
        }

        tbody.innerHTML = d.top_queries.slice(0, 15).map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-800 text-xs truncate max-w-[180px]">${row.query}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-700">${formatNumber(row.searches)}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-700">${formatPct(row.ctr)}</td>
            </tr>
        `).join('');
    });
}

// ── Operational Tab Loaders ──────────────────────────────────────────────────

function loadSlaMetrics() {
    $.get('/analytics/sla', params(), function (res) {
        const d = res.data;

        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setText('sla-breach-rate', d.breach_rate.toFixed(2) + '%');
        setText('sla-compliance', d.sla_compliance_pct.toFixed(1) + '%');
        setText('sla-avg-ship', d.avg_ship_hours.toFixed(1) + 'h');
        setText('sla-avg-delivery', d.avg_delivery_hours.toFixed(1) + 'h');

        // Vendor table
        const tbody = document.getElementById('sla-vendor-table');
        if (tbody) {
            tbody.innerHTML = (d.breach_by_vendor || []).slice(0, 10).map(row => `
                <tr class="text-xs">
                    <td class="py-1.5 pr-2 text-gray-800">${row.store_name}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatNumber(row.total)}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatNumber(row.breached)}</td>
                    <td class="py-1.5 text-right font-medium ${row.breach_rate >= 20 ? 'text-danger-600' : row.breach_rate >= 10 ? 'text-warning-600' : 'text-success-600'}">
                        ${row.breach_rate.toFixed(1)}%
                    </td>
                </tr>
            `).join('');
        }

        // Trend chart
        slaTrendChart = destroyChart(slaTrendChart);
        const ctx = document.getElementById('sla-trend-chart');
        if (!ctx) return;

        slaTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.trend.labels,
                datasets: [{
                    label: 'Breach Rate %',
                    data: d.trend.breach_rates,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.08)',
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
            },
        });
    });
}

function loadAdPerformance() {
    $.get('/analytics/ads', params(), function (res) {
        const d = res.data;
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('ads-impressions', formatNumber(d.total_impressions));
        setText('ads-clicks', formatNumber(d.total_clicks));
        setText('ads-spend', formatMoney(d.total_spend) + ' EGP');
        setText('ads-revenue', formatMoney(d.total_revenue) + ' EGP');

        // Campaigns table
        const tbody = document.getElementById('ads-campaigns-table');
        if (tbody) {
            tbody.innerHTML = (d.top_campaigns || []).map(row => `
                <tr class="text-xs">
                    <td class="py-1.5 pr-2 text-gray-800 truncate max-w-[140px]">${row.name}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatMoney(row.spend)}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatMoney(row.revenue)}</td>
                    <td class="py-1.5 text-right text-gray-700 font-medium">${row.ctr ? (row.ctr * 100).toFixed(2) + '%' : '—'}</td>
                </tr>
            `).join('');
        }

        // Performance chart
        adsPerfChart = destroyChart(adsPerfChart);
        const ctx = document.getElementById('ads-perf-chart');
        if (!ctx) return;

        adsPerfChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.performance_chart.labels,
                datasets: [
                    {
                        label: 'Spend (EGP)',
                        data: d.performance_chart.spend,
                        borderColor: '#f59e0b',
                        tension: 0.3,
                    },
                    {
                        label: 'Revenue (EGP)',
                        data: d.performance_chart.revenue,
                        borderColor: '#22c55e',
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => (v / 1000).toFixed(0) + 'K' },
                    },
                },
            },
        });
    });
}

function loadFlashSales() {
    $.get('/analytics/flash-sales', params(), function (res) {
        const d = res.data;
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('flash-units', formatNumber(d.total_units_sold));
        setText('flash-revenue', formatMoney(d.total_revenue) + ' EGP');
        setText('flash-discount', formatMoney(d.total_discount) + ' EGP');
        setText('flash-cvr', formatPct(d.avg_conversion_rate));

        const saleTable = document.getElementById('flash-sales-table');
        if (saleTable) {
            saleTable.innerHTML = (d.top_performing_sales || []).map(row => `
                <tr class="text-xs">
                    <td class="py-1.5 pr-2 text-gray-800 truncate max-w-[160px]">${row.title}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatNumber(row.units_sold)}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatMoney(row.revenue)}</td>
                    <td class="py-1.5 text-right text-gray-700">${row.avg_cvr.toFixed(1)}%</td>
                </tr>
            `).join('');
        }

        const vendorTable = document.getElementById('flash-vendors-table');
        if (vendorTable) {
            vendorTable.innerHTML = (d.top_performing_vendors || []).map(row => `
                <tr class="text-xs">
                    <td class="py-1.5 pr-2 text-gray-800">${row.store_name}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatNumber(row.units_sold)}</td>
                    <td class="py-1.5 text-right text-gray-600">${formatMoney(row.revenue)}</td>
                </tr>
            `).join('');
        }
    });
}

function loadReturns() {
    $.get('/analytics/returns', params(), function (res) {
        const d = res.data;
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('ret-total', formatNumber(d.total_returns));
        setText('ret-rate', formatPct(d.return_rate));
        setText('ret-top-reason', d.by_reason?.[0]?.reason ?? '—');

        // Reasons chart
        retReasonChart = destroyChart(retReasonChart);
        const ctxR = document.getElementById('returns-reason-chart');
        if (ctxR) {
            retReasonChart = new Chart(ctxR, {
                type: 'doughnut',
                data: {
                    labels: (d.by_reason || []).map(r => r.reason),
                    datasets: [{
                        data: (d.by_reason || []).map(r => r.count),
                        backgroundColor: [
                            '#6366f1', '#f59e0b', '#ef4444', '#22c55e',
                            '#3b82f6', '#8b5cf6', '#06b6d4', '#ec4899', '#94a3b8',
                        ],
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'right', labels: { font: { size: 10 }, boxWidth: 10 } } },
                    cutout: '55%',
                },
            });
        }

        // Monthly trend chart
        retTrendChart = destroyChart(retTrendChart);
        const ctxT = document.getElementById('returns-trend-chart');
        if (ctxT) {
            retTrendChart = new Chart(ctxT, {
                type: 'line',
                data: {
                    labels: d.monthly_trend.labels,
                    datasets: [{
                        label: 'Returns',
                        data: d.monthly_trend.counts,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.08)',
                        fill: true,
                        tension: 0.3,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
    });
}

function loadSupportMetrics() {
    $.get('/analytics/support', params(), function (res) {
        const d = res.data;
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('sup-open', formatNumber(d.open_tickets));
        setText('sup-first-resp', d.avg_first_response_hours.toFixed(1) + 'h');
        setText('sup-resolution', d.avg_resolution_hours.toFixed(1) + 'h');
        setText('sup-satisfaction', d.avg_satisfaction ? d.avg_satisfaction.toFixed(1) + '/5' : '—');

        // Category chart
        supCatChart = destroyChart(supCatChart);
        const ctxC = document.getElementById('sup-category-chart');
        if (ctxC) {
            supCatChart = new Chart(ctxC, {
                type: 'bar',
                data: {
                    labels: (d.by_category || []).map(r => r.category),
                    datasets: [{
                        label: 'Tickets',
                        data: (d.by_category || []).map(r => r.count),
                        backgroundColor: '#6366f1',
                        borderRadius: 4,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        // Resolution trend chart
        supTrendChart = destroyChart(supTrendChart);
        const ctxT = document.getElementById('sup-trend-chart');
        if (ctxT) {
            supTrendChart = new Chart(ctxT, {
                type: 'line',
                data: {
                    labels: d.resolution_trend.labels,
                    datasets: [
                        {
                            label: 'Created',
                            data: d.resolution_trend.created,
                            borderColor: '#6366f1',
                            tension: 0.3,
                        },
                        {
                            label: 'Resolved',
                            data: d.resolution_trend.resolved,
                            borderColor: '#22c55e',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
    });
}

// ── Load All ──────────────────────────────────────────────────────────────────

function loadAll() {
    // Reset loaded flags for operational tabs
    tabsLoaded.sla = false;
    tabsLoaded.ads = false;
    tabsLoaded.flash = false;
    tabsLoaded.returns = false;
    tabsLoaded.support = false;

    loadOverview();
    loadRevenueChart(state.period === 'custom' ? 'month' : state.period);
    loadOrdersByStatus();
    loadPaymentMethods();
    loadTopCategories();
    loadTopProducts();
    loadTopVendors();
    loadCustomerStats();
    loadSearchAnalytics();

    // Load the currently active operational tab
    const activeTab = document.querySelector('.ops-tab-btn.border-primary-600')?.dataset?.tab ?? 'sla';
    loadOperationalTab(activeTab);
}

function loadOperationalTab(tab) {
    if (tabsLoaded[tab]) return;
    tabsLoaded[tab] = true;

    switch (tab) {
        case 'sla': loadSlaMetrics(); break;
        case 'ads': loadAdPerformance(); break;
        case 'flash': loadFlashSales(); break;
        case 'returns': loadReturns(); break;
        case 'support': loadSupportMetrics(); break;
    }
}

// ── Initialise ────────────────────────────────────────────────────────────────

$(function () {

    // Period tab click
    $('#period-tabs').on('click', '.period-btn', function () {
        const period = $(this).data('period');
        state.period = period;

        // Toggle button styles
        $('.period-btn').removeClass('bg-primary-600 text-white').addClass('bg-white text-gray-700');
        $(this).removeClass('bg-white text-gray-700').addClass('bg-primary-600 text-white');

        // Show/hide custom date range
        if (period === 'custom') {
            $('#custom-date-range').removeClass('hidden');
            return; // Don't load until Apply
        }
        $('#custom-date-range').addClass('hidden');
        state.dateFrom = null;
        state.dateTo = null;
        loadAll();
    });

    // Apply custom date range
    $('#apply-custom').on('click', function () {
        state.dateFrom = $('#date-from').val();
        state.dateTo = $('#date-to').val();
        if (!state.dateFrom || !state.dateTo) return;
        loadAll();
    });

    // Country filter
    $('#filter-country').on('change', function () {
        state.countryId = $(this).val();
        loadAll();
    });

    // Revenue chart range buttons
    $('#revenue-range-tabs').on('click', '.rev-range-btn', function () {
        $('.rev-range-btn').removeClass('bg-primary-600 text-white').addClass('bg-white text-gray-700');
        $(this).removeClass('bg-white text-gray-700').addClass('bg-primary-600 text-white');
        loadRevenueChart($(this).data('range'));
    });

    // Operational tabs
    $('#ops-tabs').on('click', '.ops-tab-btn', function () {
        const tab = $(this).data('tab');

        // Update tab styles
        $('.ops-tab-btn')
            .removeClass('border-primary-600 text-primary-600')
            .addClass('border-transparent text-gray-500');
        $(this)
            .removeClass('border-transparent text-gray-500')
            .addClass('border-primary-600 text-primary-600');

        // Show / hide panels
        $('#ops-sla, #ops-ads, #ops-flash, #ops-returns, #ops-support').addClass('hidden');
        $(`#ops-${tab}`).removeClass('hidden');

        loadOperationalTab(tab);
    });

    // Initial load
    loadAll();
});
