/**
 * resources/js/admin/secret-promotion-detail.js
 *
 * Show page for a single secret promotion:
 *   - Chart.js conversions line chart
 *   - Toggle status (pause / resume)
 *   - Force expire
 *   - Duplicate
 *   - Edit modal form submission
 */

import Chart from 'chart.js/auto';

/* ─── Helpers ────────────────────────────────────────────────────────────── */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function ajax(method, url, data = {}) {
    return $.ajax({
        url,
        type: method,
        data,
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function withLoading($btn, text, fn) {
    const orig = $btn.text();
    $btn.prop('disabled', true).text(text ?? 'Loading…');
    return Promise.resolve(fn()).finally(() => $btn.prop('disabled', false).text(orig));
}

const Toast = window.Toast || { success: console.log, error: console.warn, info: console.log };

/* ─── Conversions Chart ──────────────────────────────────────────────────── */
let chart = null;

function initChart() {
    const canvas = document.getElementById('conversions-chart');
    if (!canvas) return;

    const chartData = window.conversionChartData ?? { labels: [], datasets: [] };

    chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: chartData.labels ?? [],
            datasets: [
                {
                    label: 'Conversions',
                    data: chartData.counts ?? [],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y',
                },
                {
                    label: '🔒 Admin Revenue',
                    data: chartData.admin_revenue ?? [],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.08)',
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Conversions' },
                    ticks: { stepSize: 1 },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Admin Revenue' },
                    grid: { drawOnChartArea: false },
                },
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const label = context.dataset.label;
                            const val   = context.parsed.y;
                            return context.datasetIndex === 1
                                ? `${label}: ${(val / 100).toFixed(2)}`
                                : `${label}: ${val}`;
                        },
                    },
                },
            },
        },
    });
}

/* ─── Chart range filter ─────────────────────────────────────────────────── */
function initChartRangeFilter() {
    document.getElementById('chart-range')?.addEventListener('change', function () {
        const days = parseInt(this.value) || 30;
        const id   = window.PROMO_ID;
        if (!id || !chart) return;

        $.get(`/secret-promotions/${id}/chart`, { days })
            .done(function (data) {
                chart.data.labels            = data.labels ?? [];
                chart.data.datasets[0].data  = data.counts ?? [];
                chart.data.datasets[1].data  = data.admin_revenue ?? [];
                chart.update();
            })
            .fail(function () {
                Toast.error('Could not refresh chart data.');
            });
    });
}

/* ─── Toggle status (pause / resume) ────────────────────────────────────── */
function initToggleStatus() {
    const $btn = $('#toggle-status-btn');
    if (!$btn.length) return;

    $btn.on('click', function () {
        const action = $(this).data('action');
        const url    = window.TOGGLE_STATUS_URL;
        if (!url) return;

        withLoading($btn, '…', () =>
            ajax('POST', url, { action })
                .done(function (res) {
                    Toast.success(res.message ?? 'Status updated.');
                    setTimeout(() => location.reload(), 800);
                })
                .fail(function (xhr) {
                    Toast.error(xhr.responseJSON?.message ?? 'Failed to update status.');
                })
        );
    });
}

/* ─── Force expire ───────────────────────────────────────────────────────── */
function initExpire() {
    function doExpire($btn) {
        window.confirmDialog({
            title: 'Force expire this promotion?',
            text: 'This promotion will be permanently expired and can no longer be used by marketers.',
            confirmButtonText: 'Yes, expire it',
            confirmButtonColor: '#dc2626',
        }).then(() => {
            withLoading($btn, '…', () =>
                ajax('POST', window.EXPIRE_URL, {})
                    .done(function (res) {
                        Toast.success(res.message ?? 'Promotion expired.');
                        setTimeout(() => location.reload(), 800);
                    })
                    .fail(function (xhr) {
                        Toast.error(xhr.responseJSON?.message ?? 'Failed to expire.');
                    })
            );
        }).catch(() => {});
    }

    $('#expire-btn, #sidebar-expire-btn').on('click', function () {
        doExpire($(this));
    });
}

/* ─── Duplicate ──────────────────────────────────────────────────────────── */
function initDuplicate() {
    function doDuplicate($btn) {
        withLoading($btn, 'Duplicating…', () =>
            ajax('POST', window.DUPLICATE_URL, {})
                .done(function (res) {
                    Toast.success(res.message ?? 'Promotion duplicated.');
                    if (res.redirect_url) {
                        setTimeout(() => window.location.href = res.redirect_url, 800);
                    } else if (window.INDEX_URL) {
                        setTimeout(() => window.location.href = window.INDEX_URL, 800);
                    }
                })
                .fail(function (xhr) {
                    Toast.error(xhr.responseJSON?.message ?? 'Failed to duplicate.');
                })
        );
    }

    $('#duplicate-btn, #sidebar-duplicate-btn').on('click', function () {
        doDuplicate($(this));
    });
}

/* ─── Edit modal ─────────────────────────────────────────────────────────── */
function initEditModal() {
    document.getElementById('edit-promo-btn')?.addEventListener('click', function () {
        $('#edit-promo-modal').modal('open');
    });

    $(document).on('submit', '#edit-promo-form', function (e) {
        e.preventDefault();

        const url    = this.dataset.updateUrl;
        const $btn   = $(this).find('[type=submit]');
        const data   = $(this).serialize();

        withLoading($btn, 'Saving…', () =>
            ajax('PUT', url, data)
                .done(function (res) {
                    Toast.success(res.message ?? 'Promotion updated.');
                    $('#edit-promo-modal').modal('close');
                    setTimeout(() => location.reload(), 800);
                })
                .fail(function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors ?? {};
                        Object.values(errors).flat().forEach(m => Toast.error(m));
                    } else {
                        Toast.error(xhr.responseJSON?.message ?? 'Failed to save.');
                    }
                })
        );
    });
}

/* ─── Status badge live-update helper ───────────────────────────────────── */
function updateStatusBadge(newStatus) {
    const badge = document.getElementById('status-badge');
    if (!badge) return;

    const colorMap = {
        active:  ['badge-success'],
        paused:  ['badge-warning'],
        expired: ['badge-secondary'],
    };

    badge.className = 'badge text-sm px-3 py-1 ' + (colorMap[newStatus]?.[0] ?? 'badge-secondary');
    badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
}

/* ─── Bootstrap ──────────────────────────────────────────────────────────── */
$(function () {
    initChart();
    initChartRangeFilter();
    initToggleStatus();
    initExpire();
    initDuplicate();
    initEditModal();
});
