/**
 * resources/js/admin/flash-sales.js
 *
 * Covers three pages:
 *   1. Index   — status-filter tabs, DataTable, action dropdown
 *   2. Create  — two-tab form, timeline visual, store via AJAX
 *   3. Edit    — six tabs, update form, status transitions, invite vendors,
 *                submissions DataTable + review modal (price history chart),
 *                bulk review, live monitor polling, analytics chart
 *
 * Globals injected by each Blade view via inline <script> blocks.
 */

import $ from 'jquery';

const Toast = window.Toast || { success: console.log, error: console.warn, info: console.log };

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function ajax(method, url, data) {
    return $.ajax({
        url,
        type: method,
        data: data ?? {},
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        },
    });
}

function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/** Show a confirmation dialog; returns a Promise that resolves if confirmed, rejects if not. */
function confirm2(msg) {
    return new Promise((resolve, reject) => {
        if (window.confirm(msg)) resolve(); else reject();
    });
}

function fmtMoney(cents) {
    return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtNum(n) { return Number(n).toLocaleString(); }

function showErrors(errors) {
    Object.values(errors ?? {}).flat().forEach(e => Toast.error(e));
}

// ─────────────────────────────────────────────────────────────────────────────
// Timeline visual (both create + edit pages with #timeline-visual)
// ─────────────────────────────────────────────────────────────────────────────

function renderTimelineVisual() {
    const $el = $('#timeline-visual');
    if (!$el.length) return;

    const fields = [
        { label: 'Submissions Open', selector: '[name=submission_opens_at]' },
        { label: 'Submissions Close', selector: '[name=submission_closes_at]' },
        { label: 'Review Deadline', selector: '[name=review_deadline_at]' },
        { label: 'Sale Starts', selector: '[name=sale_starts_at]' },
        { label: 'Sale Ends', selector: '[name=sale_ends_at]' },
    ];

    const values = fields.map(f => ({
        label: f.label,
        value: $(f.selector).val() || null,
    }));

    const html = `
        <ol class="flex items-stretch gap-0 overflow-x-auto text-xs select-none">
            ${values.map((v, i) => `
                <li class="flex flex-col items-center flex-1 min-w-[100px]">
                    <div class="flex items-center w-full">
                        ${i > 0 ? '<div class="flex-1 h-0.5 bg-gray-300"></div>' : '<div class="flex-1"></div>'}
                        <div class="w-3 h-3 rounded-full flex-shrink-0 ${v.value ? 'bg-primary-500' : 'bg-gray-300'}"></div>
                        ${i < values.length - 1 ? '<div class="flex-1 h-0.5 bg-gray-300"></div>' : '<div class="flex-1"></div>'}
                    </div>
                    <span class="mt-1 text-center text-gray-700 font-medium leading-tight">${v.label}</span>
                    <span class="text-gray-400 leading-tight">${v.value ? v.value.replace('T', ' ') : '—'}</span>
                </li>
            `).join('')}
        </ol>`;
    $el.html(html);
}

// ─────────────────────────────────────────────────────────────────────────────
// Create form  (create.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    const $form = $('#flash-sale-form');
    if (!$form.length || typeof window.FLASH_SALE_STORE_URL === 'undefined') return;

    // timeline visual on date change
    $form.on('change', '[name$="_at"]', renderTimelineVisual);
    renderTimelineVisual();

    $form.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#flash-sale-submit-btn').prop('disabled', true).text('Creating…');

        ajax('POST', window.FLASH_SALE_STORE_URL, $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? 'Flash sale created.');
                if (res.redirect) setTimeout(() => { window.location.href = res.redirect; }, 500);
            })
            .fail(function (xhr) {
                const json = xhr.responseJSON ?? {};
                if (json.errors) showErrors(json.errors);
                else Toast.error(json.message ?? 'Failed to create flash sale.');
            })
            .always(() => $btn.prop('disabled', false).text('Create Flash Sale'));
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Edit page  (edit.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    if (typeof window.FLASH_SALE_ID === 'undefined') return;

    // ── Save details / rules form ─────────────────────────────────────────────
    $(document).on('submit', '#flash-sale-form, #flash-sale-form-rules', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');
        const data = {};
        // Collect form values, aggregating checkbox[] groups into arrays
        $(this).serializeArray().forEach(p => {
            const isArr = p.name.endsWith('[]');
            const key = isArr ? p.name.slice(0, -2) : p.name;
            if (isArr) {
                if (!Array.isArray(data[key])) data[key] = [];
                data[key].push(p.value);
            } else {
                data[key] = p.value;
            }
        });
        // Ensure unchecked checkbox groups are sent as empty arrays
        $(this).find('input[type=checkbox][name$="[]"]').each(function () {
            const key = $(this).attr('name').slice(0, -2);
            if (!Array.isArray(data[key])) data[key] = [];
        });

        ajax('PUT', window.FLASH_SALE_UPDATE_URL, data)
            .done(function (res) { Toast.success(res.message ?? 'Saved.'); })
            .fail(function (xhr) {
                const json = xhr.responseJSON ?? {};
                if (json.errors) showErrors(json.errors);
                else Toast.error(json.message ?? 'Failed to save.');
            })
            .always(function () { $btn.prop('disabled', false).text('Save Changes'); });
    });

    // timeline visual on date change
    $(document).on('change', '[name$="_at"]', renderTimelineVisual);
    renderTimelineVisual();

    // ── Status transitions ────────────────────────────────────────────────────
    $(document).on('click', '[data-transition]', function (e) {
        e.preventDefault();
        const action = $(this).data('transition');
        const $btn = $(this);

        if (action === 'cancel') {
            openModal('cancel-modal');
            return;
        }

        const messages = {
            open_submissions: 'Open submissions for this flash sale?',
            close_submissions: 'Close submissions early?',
            mark_approved: 'Mark this flash sale as approved?',
            start_sale: 'Start the sale now?',
            end_sale: 'End the sale early?',
        };
        const msg = messages[action] ?? 'Confirm this action?';

        confirm2(msg).then(() => {
            $btn.prop('disabled', true);
            ajax('POST', window.TRANSITION_URL, { action })
                .done(res => { Toast.success(res.message ?? 'Status updated.'); setTimeout(() => location.reload(), 600); })
                .fail(xhr => { Toast.error(xhr.responseJSON?.message ?? 'Transition failed.'); $btn.prop('disabled', false); });
        }).catch(() => { });
    });

    // Cancel form
    $('#cancel-form').on('submit', function (e) {
        e.preventDefault();
        const reason = $('[name=cancellation_reason]', this).val().trim();
        if (!reason) { Toast.error('Cancellation reason is required.'); return; }
        const $btn = $('#cancel-submit-btn').prop('disabled', true);
        ajax('POST', window.TRANSITION_URL, { action: 'cancel', reason })
            .done(res => { Toast.success(res.message ?? 'Cancelled.'); setTimeout(() => location.reload(), 600); })
            .fail(xhr => { Toast.error(xhr.responseJSON?.message ?? 'Cancel failed.'); $btn.prop('disabled', false); });
    });

    // ── Invite vendors ────────────────────────────────────────────────────────
    $('#invite-vendors-btn').on('click', function () {
        const $btn = $(this).prop('disabled', true).text('Loading count…');

        ajax('GET', window.ELIGIBLE_COUNT_URL)
            .done(res => {
                const count = res.count ?? 0;
                if (!count) { Toast.info('No eligible vendors found.'); $btn.prop('disabled', false).text('Invite Eligible Vendors'); return; }
                confirm2(`Invite ${count} eligible vendor(s) to this flash sale?`)
                    .then(() => {
                        $btn.text('Sending invitations…');
                        ajax('POST', window.INVITE_URL)
                            .done(r => { Toast.success(r.message ?? `${r.count} vendor(s) invited.`); if (window._invitationsTable) window._invitationsTable.ajax.reload(); })
                            .fail(xhr => Toast.error(xhr.responseJSON?.message ?? 'Invite failed.'))
                            .always(() => $btn.prop('disabled', false).text('Invite Eligible Vendors'));
                    })
                    .catch(() => $btn.prop('disabled', false).text('Invite Eligible Vendors'));
            })
            .fail(() => { Toast.error('Failed to fetch eligible vendor count.'); $btn.prop('disabled', false).text('Invite Eligible Vendors'); });
    });

    // ── Invitations DataTable ─────────────────────────────────────────────────
    if ($('#invitations-table').length) {
        window._invitationsTable = initDataTable('invitations-table', {
            url: window.INVITATIONS_DATATABLE_URL,
            columns: [
                { data: 'vendor_name', title: 'Vendor' },
                {
                    data: 'status', title: 'Status',
                    render: d => `<span class="badge badge-gray">${d}</span>`
                },
                { data: 'notified_at', title: 'Notified' },
                { data: 'responded_at', title: 'Responded' },
            ],
        });
    }

    // ── Submissions DataTable + stats ─────────────────────────────────────────
    if ($('#submissions-table').length) {
        loadSubmissionStats();

        window._submissionsTable = initDataTable('submissions-table', {
            url: window.SUBMISSIONS_DATATABLE_URL,
            selectable: true,
            columns: [
                {
                    data: null, title: '<input type="checkbox" id="submissions-table-select-all">', orderable: false,
                    render: (d, t, r) => `<input type="checkbox" class="row-check" value="${r.id}">`
                },
                { data: 'product_name', title: 'Product' },
                { data: 'vendor_name', title: 'Vendor' },
                { data: 'flash_price_formatted', title: 'Flash Price' },
                { data: 'original_price_formatted', title: 'Original' },
                {
                    data: 'discount_pct', title: 'Discount',
                    render: (d, t, r) => {
                        const minDisc = window.FLASH_SALE_MIN_DISC ?? 0;
                        const ok = parseFloat(d) >= minDisc;
                        return `<span class="font-medium ${ok ? 'text-success-700' : 'text-danger-700'}">${d}%</span>`;
                    }
                },
                {
                    data: 'is_suspect', title: '⚠',
                    render: (d) => d ? '<span title="Fake discount suspected" class="text-warning-500 font-bold">⚠</span>' : ''
                },
                {
                    data: 'status', title: 'Status',
                    render: (d) => `<span class="badge badge-gray">${d}</span>`
                },
                { data: 'quantity_approved', title: 'Qty' },
                {
                    data: null, title: '', orderable: false,
                    render: (d, t, r) => {
                        if (!['submitted', 'under_review'].includes(r.status)) return '';
                        return `<button class="btn btn-primary btn-xs review-btn" data-id="${r.id}">Review</button>`;
                    }
                },
            ],
            serverSideFilters: {
                status: () => $('#filter-submission-status').val(),
                suspect: () => $('#filter-suspect').is(':checked') ? '1' : '',
            },
        });

        $('#filter-submission-status, #filter-suspect').on('change', function () {
            if (window._submissionsTable) window._submissionsTable.ajax.reload();
        });
    }

    // ── Review modal ──────────────────────────────────────────────────────────
    $(document).on('click', '.review-btn', function () {
        const id = $(this).data('id');
        openReviewModal(id);
    });

    function openReviewModal(submissionId) {
        $('#review-form [name=submission_id]').val(submissionId);
        $('#review-form [name=decision]').val('');
        $('#review-form [name=rejection_code]').val('');
        $('#review-form [name=rejection_reason]').val('');
        $('#review-form [name=admin_notes]').val('');
        $('#review-product-img').attr('src', '');
        $('#review-product-name').text('Loading…');
        $('#review-variant-name, #review-vendor-name, #review-vendor-country, #review-vendor-rating').text('');
        $('#review-stock-tbody').html('<tr><td colspan="3" class="text-center py-2">Loading…</td></tr>');
        $('#review-original-price, #review-flash-price, #review-discount-pct').text('');
        $('#review-discount-check').html('');
        $('#review-30d-row').addClass('hidden');
        $('#fake-discount-warnings').addClass('hidden').html('');
        destroyPriceHistoryChart();
        openModal('review-modal');

        // Fetch submission data from the datatable row cache
        const row = window._submissionsTable?.row(`[data-id="${submissionId}"]`)?.data()
            ?? getSubmissionRowData(submissionId);

        if (row) populateReviewModal(row);
    }

    function getSubmissionRowData(id) {
        // Fallback: find from DOM data
        return null;
    }

    function populateReviewModal(row) {
        $('#review-product-img').attr('src', row.product_image ?? '');
        $('#review-product-name').text(row.product_name ?? '');
        $('#review-variant-name').text(row.variant_name ?? '');
        $('#review-vendor-name').text(row.vendor_name ?? '');
        $('#review-vendor-country').text(row.vendor_country ?? '');
        $('#review-vendor-rating').text(row.vendor_rating ?? '—');

        const stock = row.stock ?? [];
        if (stock.length) {
            $('#review-stock-tbody').html(stock.map(s =>
                `<tr><td>${s.warehouse}</td><td class="text-right">${fmtNum(s.on_hand)}</td><td class="text-right">${fmtNum(s.available)}</td></tr>`
            ).join(''));
        }

        $('#review-original-price').text(row.original_price_formatted ?? '');
        $('#review-flash-price').text(row.flash_price_formatted ?? '');

        const disc = parseFloat(row.discount_pct ?? 0);
        const minDisc = window.FLASH_SALE_MIN_DISC ?? 0;
        const ok = disc >= minDisc;
        $('#review-discount-pct').text(disc.toFixed(2) + '%')
            .removeClass('text-success-700 text-danger-700').addClass(ok ? 'text-success-700' : 'text-danger-700');
        $('#review-discount-check').html(
            ok ? '<span class="badge badge-success">✓ Meets minimum</span>'
                : `<span class="badge badge-danger">✗ Below ${minDisc}% minimum</span>`
        );

        // 30-day avg
        const avgPrice = row.avg_price_30d;
        if (avgPrice) {
            const diff = (((row.flash_price / avgPrice) - 1) * 100).toFixed(1);
            const sign = diff >= 0 ? '+' : '';
            $('#review-30d-price').text(fmtMoney(avgPrice));
            $('#review-30d-diff').text(`${sign}${diff}% vs avg`);
            $('#review-30d-row').removeClass('hidden');
        }

        // Fake discount
        if (row.is_suspect) {
            const reasons = row.fraud_reasons ?? [];
            $('#fake-discount-warnings')
                .removeClass('hidden')
                .html(`<div class="bg-warning-50 border border-warning-300 rounded-lg p-3">
                    <p class="text-sm font-semibold text-warning-800 mb-1">⚠ Potential fake discount detected</p>
                    <ul class="text-xs text-warning-700 list-disc list-inside space-y-0.5">
                        ${reasons.map(r => `<li>${r}</li>`).join('')}
                    </ul>
                </div>`);
        }

        // Price history chart
        if (row.vendor_listing_id) {
            loadPriceHistoryChart(row.vendor_listing_id, row.flash_price ?? 0, row.original_price ?? 0);
        }
    }

    // ── Price history chart (Chart.js) ────────────────────────────────────────
    let _priceHistoryChart = null;

    function destroyPriceHistoryChart() {
        if (_priceHistoryChart) { _priceHistoryChart.destroy(); _priceHistoryChart = null; }
    }

    function loadPriceHistoryChart(listingId, flashPrice, originalPrice) {
        ajax('GET', window.PRICE_HISTORY_URL + '?vendor_listing_id=' + encodeURIComponent(listingId))
            .done(res => {
                const labels = res.map(p => p.date);
                const prices = res.map(p => p.price / 100);

                destroyPriceHistoryChart();
                const ctx = document.getElementById('price-history-chart');
                if (!ctx) return;

                import('chart.js').then(({ Chart, registerables }) => {
                    Chart.register(...registerables);
                    _priceHistoryChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Historical Price', data: prices, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3, pointRadius: 2 },
                                { label: 'Flash Price', data: Array(labels.length).fill(flashPrice / 100), borderColor: '#f59e0b', borderDash: [4, 4], pointRadius: 0 },
                                { label: 'Original Price', data: Array(labels.length).fill(originalPrice / 100), borderColor: '#6b7280', borderDash: [2, 4], pointRadius: 0 },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                            scales: { y: { ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 }, maxRotation: 45 } } },
                        },
                    });
                });
            });
    }

    // ── Review form submit ────────────────────────────────────────────────────
    $('#review-form').on('submit', function (e) {
        e.preventDefault();
        const submissionId = $('[name=submission_id]', this).val();
        const decision = $('[name=decision]:checked', this).val();
        if (!decision) { Toast.error('Please select a decision.'); return; }
        if (decision === 'rejected' && !$('[name=rejection_code]', this).val()) {
            Toast.error('Please select a rejection reason.'); return;
        }

        const data = {};
        $(this).serializeArray().forEach(p => { data[p.name] = p.value; });
        const $btn = $('#review-submit-btn').prop('disabled', true).text('Saving…');

        ajax('POST', `/flash-sales/submissions/${submissionId}/review`, data)
            .done(res => {
                Toast.success(res.message ?? 'Decision saved.');
                closeModal('review-modal');
                if (window._submissionsTable) window._submissionsTable.ajax.reload();
                loadSubmissionStats();
            })
            .fail(xhr => {
                const json = xhr.responseJSON ?? {};
                if (json.errors) showErrors(json.errors);
                else Toast.error(json.message ?? 'Failed to save decision.');
            })
            .always(() => $btn.prop('disabled', false).text('Save Decision'));
    });

    // ── Bulk approve ──────────────────────────────────────────────────────────
    $('#bulk-approve-submissions').on('click', function () {
        const ids = getSelectedSubmissionIds();
        if (!ids.length) { Toast.info('Select at least one submission.'); return; }
        confirm2(`Approve ${ids.length} selected submission(s)?`).then(() => {
            const $btn = $(this).prop('disabled', true);
            ajax('POST', window.BULK_REVIEW_URL, { ids, decision: 'approved' })
                .done(res => {
                    Toast.success(`Approved: ${res.approved}, Rejected: ${res.rejected}, Failed: ${res.failed}`);
                    window._submissionsTable?.ajax.reload();
                    loadSubmissionStats();
                })
                .fail(xhr => Toast.error(xhr.responseJSON?.message ?? 'Bulk approve failed.'))
                .always(() => $btn.prop('disabled', false));
        }).catch(() => { });
    });

    // ── Bulk reject ───────────────────────────────────────────────────────────
    $('#bulk-reject-submissions').on('click', function () {
        const ids = getSelectedSubmissionIds();
        if (!ids.length) { Toast.info('Select at least one submission.'); return; }
        $('[name=bulk_ids]', '#bulk-reject-form').val(ids.join(','));
        openModal('bulk-reject-modal');
    });

    $('#bulk-reject-form').on('submit', function (e) {
        e.preventDefault();
        const ids = $('[name=bulk_ids]', this).val().split(',').filter(Boolean);
        const code = $('[name=bulk_rejection_code]', this).val();
        const reason = $('[name=bulk_rejection_reason]', this).val();
        if (!code) { Toast.error('Select a rejection reason.'); return; }
        const $btn = $('#bulk-reject-submit').prop('disabled', true);
        ajax('POST', window.BULK_REVIEW_URL, { ids, decision: 'rejected', rejection_code: code, rejection_reason: reason })
            .done(res => {
                Toast.success(`Approved: ${res.approved}, Rejected: ${res.rejected}, Failed: ${res.failed}`);
                closeModal('bulk-reject-modal');
                window._submissionsTable?.ajax.reload();
                loadSubmissionStats();
            })
            .fail(xhr => Toast.error(xhr.responseJSON?.message ?? 'Bulk reject failed.'))
            .always(() => $btn.prop('disabled', false));
    });

    function getSelectedSubmissionIds() {
        return $('#submissions-table tbody input.row-check:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function loadSubmissionStats() {
        if (!window.SUBMISSIONS_DATATABLE_URL) return;
        const statsUrl = window.SUBMISSIONS_DATATABLE_URL.replace('/datatable', '/stats')
            .replace('submissions/datatable', 'submission-stats');
        ajax('GET', statsUrl.includes('submission-stats')
            ? statsUrl
            : window.FLASH_SALE_UPDATE_URL.replace('/update', '').replace('/flash-sales/' + window.FLASH_SALE_ID, '/flash-sales/' + window.FLASH_SALE_ID + '/submission-stats'))
            .done(res => {
                $('#stat-submitted').find('[data-stat-value]').text(fmtNum(res.submitted ?? 0));
                $('#stat-approved').find('[data-stat-value]').text(fmtNum(res.approved ?? 0));
                $('#stat-rejected').find('[data-stat-value]').text(fmtNum(res.rejected ?? 0));
                $('#stat-pending').find('[data-stat-value]').text(fmtNum(res.pending ?? 0));
            });
    }

    // ── Live monitor polling ──────────────────────────────────────────────────
    if (window.IS_LIVE && $('#live-monitor-section').length) {
        function refreshLiveData() {
            ajax('GET', window.LIVE_DATA_URL)
                .done(res => {
                    $('#live-units').find('[data-stat-value]').text(fmtNum(res.units_sold ?? 0));
                    $('#live-revenue').find('[data-stat-value]').text(fmtMoney(res.gross_revenue ?? 0));
                    $('#live-soldout').find('[data-stat-value]').text(fmtNum(res.sold_out_count ?? 0));

                    const $tbody = $('#live-submissions-tbody');
                    $tbody.empty();
                    (res.top_submissions ?? []).forEach(s => {
                        $tbody.append(`<tr>
                            <td class="py-1 px-3">${s.product_name}</td>
                            <td class="py-1 px-3 text-right">${fmtNum(s.units_sold)}</td>
                            <td class="py-1 px-3 text-right">${fmtNum(s.quantity_remaining)}</td>
                            <td class="py-1 px-3 text-right">${fmtMoney(s.revenue)}</td>
                            <td class="py-1 px-3"><span class="badge badge-${s.status === 'sold_out' ? 'danger' : 'success'}">${s.status}</span></td>
                        </tr>`);
                    });
                });
        }

        refreshLiveData();
        setInterval(refreshLiveData, 10000);
    }

    // ── Analytics chart ───────────────────────────────────────────────────────
    if (window.IS_ENDED && $('#analytics-section').length) {
        const analyticsUrl = window.FLASH_SALE_UPDATE_URL.replace('/flash-sales/' + window.FLASH_SALE_ID, '/flash-sales/' + window.FLASH_SALE_ID + '/analytics-data');
        ajax('GET', analyticsUrl)
            .done(res => {
                const s = res.summary ?? {};
                $('#an-units').find('[data-stat-value]').text(fmtNum(s.units_sold ?? 0));
                $('#an-revenue').find('[data-stat-value]').text(fmtMoney(s.gross_revenue ?? 0));
                $('#an-discount').find('[data-stat-value]').text(fmtMoney(s.discount_given ?? 0));
                $('#an-commission').find('[data-stat-value]').text(fmtMoney(s.platform_commission ?? 0));
                $('#an-payout').find('[data-stat-value]').text(fmtMoney(s.vendor_payout ?? 0));
                $('#an-conversion').find('[data-stat-value]').text(((s.avg_conversion_rate ?? 0) * 100).toFixed(1) + '%');

                const byDay = res.by_day ?? [];
                const ctx = document.getElementById('analytics-chart');
                if (!ctx || !byDay.length) return;

                import('chart.js').then(({ Chart, registerables }) => {
                    Chart.register(...registerables);
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: byDay.map(d => d.date),
                            datasets: [
                                { label: 'Gross Revenue', data: byDay.map(d => (d.gross_revenue ?? 0) / 100), backgroundColor: 'rgba(99,102,241,0.7)', yAxisID: 'y' },
                                { label: 'Discount', data: byDay.map(d => (d.discount_given ?? 0) / 100), backgroundColor: 'rgba(245,158,11,0.7)', yAxisID: 'y' },
                                { label: 'Units Sold', data: byDay.map(d => d.units_sold ?? 0), type: 'line', borderColor: '#10b981', backgroundColor: 'transparent', yAxisID: 'y2', tension: 0.3 },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            scales: {
                                y: { position: 'left', ticks: { font: { size: 10 } } },
                                y2: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10 } } },
                                x: { ticks: { font: { size: 9 }, maxRotation: 45 } },
                            },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                        },
                    });
                });
            });
    }
});
