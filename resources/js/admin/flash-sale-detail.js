/**
 * Flash Sale Detail (show) page JS.
 *
 * Handles:
 *  - Submissions DataTable (load, filter, row actions)
 *  - Review modal (single approve/reject with price history mini-chart)
 *  - Bulk reject modal
 *  - Status transitions (with cancel modal for cancellation)
 *  - Invitations DataTable
 *  - Auto-invite / manual invite
 *  - Live monitor: 10s polling, countdown timer
 *  - Analytics data loading
 */

/* global initDataTable, window */

// ─── State ────────────────────────────────────────────────────────────────────

let selectedSubmissionIds = new Set();
let livePollingInterval = null;
let countdownInterval = null;

// ─── Bootstrap ────────────────────────────────────────────────────────────────

$(function () {
    const status = window.FLASH_SALE_STATUS;

    initSubmissionsTable();
    initInvitationsTable();
    initTransitionButtons();
    initReviewModal();
    initBulkReject();
    initAutoInvite();
    initManualInvite();
    initCancelModal();

    if (status === 'live') {
        startLiveMonitor();
    }

    if (['live', 'ended'].includes(status)) {
        loadAnalytics();
    }
});

// ─── Submissions DataTable ────────────────────────────────────────────────────

const STATUS_BADGES = {
    submitted: { label: 'Submitted', color: 'gray' },
    under_review: { label: 'Under Review', color: 'warning' },
    approved: { label: 'Approved', color: 'primary' },
    live: { label: 'Live', color: 'success' },
    sold_out: { label: 'Sold Out', color: 'danger' },
    rejected: { label: 'Rejected', color: 'danger' },
    withdrawn: { label: 'Withdrawn', color: 'gray' },
    ended: { label: 'Ended', color: 'gray' },
};

function badge(status) {
    const b = STATUS_BADGES[status] || { label: status, color: 'gray' };
    return `<span class="badge badge-${b.color}">${b.label}</span>`;
}

function initSubmissionsTable() {
    if (!document.getElementById('submissions-table')) return;

    initDataTable('submissions-table', {
        url: window.URLS.submissionsDt,
        method: 'POST',
        columns: [
            {
                data: null,
                orderable: false,
                className: 'w-8',
                render: (d, t, row) =>
                    `<input type="checkbox" class="sub-check form-checkbox" data-id="${row.id}">`,
            },
            {
                data: 'product_name',
                title: 'Product',
                render: (d, t, row) => {
                    const img = row.product_image_url
                        ? `<img src="${row.product_image_url}" class="w-8 h-8 rounded object-cover flex-shrink-0">`
                        : `<div class="w-8 h-8 rounded bg-gray-100"></div>`;
                    const suspect = row.is_suspect
                        ? `<span class="ml-1 text-amber-500" title="Possible fake discount">⚠</span>`
                        : '';
                    return `<div class="flex items-center gap-2">${img}<div>
                        <span class="font-medium text-gray-900 text-sm">${row.product_name}${suspect}</span>
                        <div class="text-xs text-gray-400">${row.vendor_store_name}</div>
                    </div></div>`;
                },
            },
            {
                data: 'flash_price_formatted',
                title: 'Flash Price',
                className: 'text-right font-semibold',
                render: (d, t, row) => {
                    const ok = row.discount_ok;
                    return `<span class="${ok ? 'text-primary-700' : 'text-danger-600'}">${d}</span>`;
                },
            },
            {
                data: 'original_price_formatted',
                title: 'Original',
                className: 'text-right text-gray-400 line-through',
            },
            {
                data: 'calculated_discount_pct',
                title: 'Disc.',
                className: 'text-right',
                render: (d, t, row) => {
                    const ok = row.discount_ok;
                    return `<span class="font-mono font-medium ${ok ? 'text-emerald-600' : 'text-danger-600'}">${d}%</span>
                            ${!ok ? `<div class="text-xs text-danger-500">Min: ${row.min_discount_pct}%</div>` : ''}`;
                },
            },
            {
                data: 'quantity_sold',
                title: 'Qty',
                orderable: false,
                className: 'text-right font-mono',
                render: (d, t, row) =>
                    `${row.quantity_sold} / ${row.max_quantity_total}`,
            },
            {
                data: 'status',
                title: 'Status',
                render: d => badge(d),
            },
            {
                data: 'submitted_at_human',
                title: 'Submitted',
                render: d => d ? `<span class="text-gray-400 text-xs">${d}</span>` : '—',
            },
            {
                data: null,
                title: '',
                orderable: false,
                className: 'text-right',
                render: (d, t, row) => {
                    const canReview = ['submitted', 'under_review'].includes(row.status);
                    if (!canReview) return '';
                    return `<button type="button" class="btn btn-secondary btn-xs btn-review-submission"
                        data-id="${row.id}">Review</button>`;
                },
            },
        ],
        serverSideFilters: {
            status: () => $('#sub-filter-status').val() || null,
        },
    });

    // Filter change
    $('#sub-filter-status').on('change', function () {
        if ($.fn.DataTable.isDataTable('#submissions-table')) {
            $('#submissions-table').DataTable().ajax.reload();
        }
    });

    // Checkbox events (delegated)
    $(document).on('change', '.sub-check', function () {
        const id = $(this).data('id');
        if (this.checked) {
            selectedSubmissionIds.add(id);
        } else {
            selectedSubmissionIds.delete(id);
        }
        updateBulkRejectVisibility();
    });

    // Review button (delegated)
    $(document).on('click', '.btn-review-submission', function () {
        openReviewModal($(this).data('id'));
    });
}

function updateBulkRejectVisibility() {
    const btn = document.getElementById('btn-bulk-reject');
    if (!btn) return;
    if (selectedSubmissionIds.size > 0) {
        btn.classList.remove('hidden');
        btn.textContent = `Bulk Reject (${selectedSubmissionIds.size})`;
    } else {
        btn.classList.add('hidden');
    }
}

// ─── Review modal ─────────────────────────────────────────────────────────────

function initReviewModal() {
    $('#btn-confirm-review').on('click', confirmReview);
}

function openReviewModal(submissionId) {
    document.getElementById('review-submission-id').value = submissionId;

    // Reset fields
    document.getElementById('review-admin-notes').value = '';
    document.getElementById('review-rejection-reason').value = '';
    document.getElementById('review-rejection-code').value = 'manual_rejection';
    document.getElementById('fraud-warning').classList.add('hidden');
    document.getElementById('review-price-chart').innerHTML = '<span class="text-xs text-gray-400">Loading…</span>';
    document.getElementById('review-product-info').classList.add('hidden');
    document.getElementById('review-stock-info').classList.add('hidden');

    // Set decision back to approved
    const approveRadio = document.querySelector('input[name="decision"][value="approved"], input[type="radio"][value="approved"]');
    if (approveRadio) approveRadio.click();

    // Open modal
    window.dispatchEvent(new CustomEvent('modal:open', { detail: { id: 'review-modal' } }));

    // Fetch detail
    const detailUrl = `${window.URLS.submissionDetail}/${submissionId}/detail`;
    $.get(detailUrl)
        .done(res => populateReviewModal(res.data))
        .fail(() => {
            document.getElementById('review-price-chart').innerHTML =
                '<span class="text-xs text-danger-600">Failed to load pricing data.</span>';
        });
}

function populateReviewModal(data) {
    // Product info
    if (data.product_name) {
        document.getElementById('review-product-name').textContent = data.product_name;
        document.getElementById('review-flash-price').textContent = data.flash_price_formatted;
        document.getElementById('review-original-price').textContent = data.original_price_formatted;
        document.getElementById('review-discount-pct').textContent = data.calculated_discount_pct + '%';
        if (data.product_image_url) {
            document.getElementById('review-product-img').src = data.product_image_url;
        }
        document.getElementById('review-product-info').classList.remove('hidden');
    }

    // Stock
    if (data.stock) {
        document.getElementById('review-max-qty').textContent = data.stock.max_quantity_total;
        document.getElementById('review-qty-sold').textContent = data.stock.quantity_sold;
        document.getElementById('review-qty-remaining').textContent = data.stock.quantity_remaining;
        document.getElementById('review-stock-info').classList.remove('hidden');
    }

    // Fraud warning
    if (data.analysis?.is_suspect && data.analysis.reasons?.length) {
        const warn = document.getElementById('fraud-warning');
        const list = document.getElementById('fraud-reasons');
        list.innerHTML = data.analysis.reasons.map(r => `<li>${r}</li>`).join('');
        warn.classList.remove('hidden');
    }

    // Price history mini-chart (simple bar chart)
    renderMiniPriceChart(data.price_history || []);
}

function renderMiniPriceChart(history) {
    const container = document.getElementById('review-price-chart');
    if (!history.length) {
        container.innerHTML = '<span class="text-xs text-gray-400">No price history data.</span>';
        return;
    }

    const prices = history.map(h => h.price_raw);
    const maxPrice = Math.max(...prices);
    const minPrice = Math.min(...prices);
    const range = maxPrice - minPrice || 1;

    container.innerHTML = history.map(h => {
        const heightPct = ((h.price_raw - minPrice) / range) * 100;
        const barH = Math.max(4, Math.round((heightPct / 100) * 60));
        return `<div class="flex-1 flex flex-col justify-end" title="${h.date}: ${h.price_formatted}">
            <div class="bg-primary-400 rounded-t" style="height:${barH}px; min-height:4px;"></div>
        </div>`;
    }).join('');
}

function confirmReview() {
    const submissionId = document.getElementById('review-submission-id').value;
    const decision = document.querySelector('#review-decision')?.value || 'approved';
    const adminNotes = document.getElementById('review-admin-notes').value;
    const rejectCode = document.getElementById('review-rejection-code').value;
    const rejectReason = document.getElementById('review-rejection-reason').value;

    if (decision === 'approved') {
        const fraudWarn = document.getElementById('fraud-warning');
        const override = document.getElementById('override-fraud-check');
        if (!fraudWarn.classList.contains('hidden') && !override?.checked) {
            alert('Please acknowledge the pricing warning before approving.');
            return;
        }
    }

    const btn = document.getElementById('btn-confirm-review');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const url = `${window.URLS.submissionDetail}/${submissionId}/review`;

    $.ajax({
        url,
        method: 'POST',
        data: {
            decision,
            admin_notes: adminNotes,
            rejection_code: rejectCode,
            rejection_reason: rejectReason,
            _token: $('meta[name="csrf-token"]').attr('content'),
        },
        success(res) {
            btn.disabled = false;
            btn.textContent = 'Confirm Decision';
            window.dispatchEvent(new CustomEvent('modal:close', { detail: { id: 'review-modal' } }));
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: res.message || 'Submission reviewed.' } }));
            if ($.fn.DataTable.isDataTable('#submissions-table')) {
                $('#submissions-table').DataTable().ajax.reload(null, false);
            }
        },
        error(xhr) {
            btn.disabled = false;
            btn.textContent = 'Confirm Decision';
            const msg = xhr.responseJSON?.message || 'Review failed.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
        },
    });
}

// ─── Bulk reject ──────────────────────────────────────────────────────────────

function initBulkReject() {
    document.getElementById('btn-bulk-reject')?.addEventListener('click', () => {
        if (!selectedSubmissionIds.size) return;
        document.getElementById('bulk-reject-count').textContent = selectedSubmissionIds.size;
        window.dispatchEvent(new CustomEvent('modal:open', { detail: { id: 'bulk-reject-modal' } }));
    });

    document.getElementById('btn-confirm-bulk-reject')?.addEventListener('click', () => {
        const code = document.getElementById('bulk-rejection-code').value;
        const reason = document.getElementById('bulk-rejection-reason').value;

        $.ajax({
            url: window.URLS.bulkReview,
            method: 'POST',
            data: {
                ids: [...selectedSubmissionIds],
                decision: 'rejected',
                rejection_code: code,
                rejection_reason: reason,
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success(res) {
                window.dispatchEvent(new CustomEvent('modal:close', { detail: { id: 'bulk-reject-modal' } }));
                const d = res.data;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: `Rejected: ${d.rejected}, Failed: ${d.failed}` },
                }));
                selectedSubmissionIds.clear();
                updateBulkRejectVisibility();
                if ($.fn.DataTable.isDataTable('#submissions-table')) {
                    $('#submissions-table').DataTable().ajax.reload(null, false);
                }
            },
            error(xhr) {
                const msg = xhr.responseJSON?.message || 'Bulk reject failed.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
            },
        });
    });
}

// ─── Status transitions ───────────────────────────────────────────────────────

function initTransitionButtons() {
    $(document).on('click', '.flash-sale-transition', function () {
        const action = $(this).data('action');
        const needsConfirm = $(this).data('needs-confirm') === '1' || $(this).data('needs-confirm') === 1;

        if (action === 'cancel') {
            window.dispatchEvent(new CustomEvent('modal:open', { detail: { id: 'cancel-modal' } }));
            return;
        }

        if (needsConfirm) {
            if (!confirm('Are you sure you want to proceed with this action?')) return;
        }

        doTransition(action);
    });
}

function doTransition(action, reason = '') {
    $.ajax({
        url: window.URLS.transition,
        method: 'POST',
        data: {
            action,
            reason,
            _token: $('meta[name="csrf-token"]').attr('content'),
        },
        success(res) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'success', message: res.message || 'Status updated.' },
            }));
            setTimeout(() => window.location.reload(), 800);
        },
        error(xhr) {
            const msg = xhr.responseJSON?.message || 'Transition failed.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
        },
    });
}

// ─── Cancel modal ─────────────────────────────────────────────────────────────

function initCancelModal() {
    document.getElementById('btn-confirm-cancel')?.addEventListener('click', () => {
        const reason = document.getElementById('cancel-reason').value;
        window.dispatchEvent(new CustomEvent('modal:close', { detail: { id: 'cancel-modal' } }));
        doTransition('cancel', reason);
    });
}

// ─── Invitations DataTable ────────────────────────────────────────────────────

const INV_STATUS_BADGES = {
    pending: { label: 'Pending', color: 'gray' },
    accepted: { label: 'Accepted', color: 'success' },
    declined: { label: 'Declined', color: 'danger' },
    submitted: { label: 'Submitted', color: 'primary' },
};

function initInvitationsTable() {
    if (!document.getElementById('invitations-table')) return;

    initDataTable('invitations-table', {
        url: window.URLS.invitationsDt,
        method: 'POST',
        columns: [
            { data: 'store_name', title: 'Vendor' },
            { data: 'invitation_type', title: 'Type', render: d => d === 'manual' ? '✋ Manual' : '🤖 Auto' },
            {
                data: 'status',
                title: 'Status',
                render: d => {
                    const b = INV_STATUS_BADGES[d] || { label: d, color: 'gray' };
                    return `<span class="badge badge-${b.color}">${b.label}</span>`;
                },
            },
            {
                data: 'slots_allocated',
                title: 'Slots',
                className: 'text-right font-mono',
                render: d => d ?? '—',
            },
            {
                data: 'invited_at',
                title: 'Invited',
                render: d => d ? new Date(d).toLocaleDateString() : '—',
            },
            {
                data: 'responded_at',
                title: 'Responded',
                render: d => d ? new Date(d).toLocaleDateString() : '—',
            },
        ],
    });
}

// ─── Auto-invite ──────────────────────────────────────────────────────────────

function initAutoInvite() {
    document.getElementById('btn-auto-invite')?.addEventListener('click', () => {
        const btn = document.getElementById('btn-auto-invite');
        btn.disabled = true;
        btn.textContent = 'Inviting…';

        $.ajax({
            url: window.URLS.inviteVendors,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success(res) {
                btn.disabled = false;
                btn.textContent = 'Auto-Invite Eligible';
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: res.message || `${res.count} vendors invited.` },
                }));
                if ($.fn.DataTable.isDataTable('#invitations-table')) {
                    $('#invitations-table').DataTable().ajax.reload(null, false);
                }
            },
            error(xhr) {
                btn.disabled = false;
                btn.textContent = 'Auto-Invite Eligible';
                const msg = xhr.responseJSON?.message || 'Invite failed.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
            },
        });
    });
}

// ─── Manual invite ────────────────────────────────────────────────────────────

function initManualInvite() {
    document.getElementById('btn-confirm-manual-invite')?.addEventListener('click', () => {
        const raw = document.getElementById('manual-invite-ids').value.trim();
        if (!raw) return;

        const ids = raw.split('\n').map(s => s.trim()).filter(Boolean);
        if (!ids.length) return;

        $.ajax({
            url: window.URLS.inviteVendors,
            method: 'POST',
            data: {
                vendor_ids: ids,
                type: 'manual',
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success(res) {
                window.dispatchEvent(new CustomEvent('modal:close', { detail: { id: 'manual-invite-modal' } }));
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: res.message || 'Vendors invited.' },
                }));
                if ($.fn.DataTable.isDataTable('#invitations-table')) {
                    $('#invitations-table').DataTable().ajax.reload(null, false);
                }
            },
            error(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to invite vendors.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
            },
        });
    });
}

// ─── Live monitor ─────────────────────────────────────────────────────────────

function startLiveMonitor() {
    fetchLiveData();
    livePollingInterval = setInterval(fetchLiveData, 10000);
}

function fetchLiveData() {
    $.get(window.URLS.liveData)
        .done(res => updateLiveStats(res.data))
        .fail(() => {
            if (livePollingInterval) clearInterval(livePollingInterval);
        });
}

function updateLiveStats(data) {
    setText('live-units', data.total_units_sold?.toLocaleString() ?? '—');
    setText('live-revenue', data.total_revenue != null ? data.total_revenue.toFixed(2) : '—');
    setText('live-sold-out', data.sold_out_count ?? '—');

    // Countdown
    let secs = data.time_remaining_seconds ?? 0;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (secs <= 0) {
            clearInterval(countdownInterval);
            setText('live-countdown', 'Ended');
            return;
        }
        secs--;
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        setText('live-countdown', `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`);
    }, 1000);

    // Top products table
    const tbody = document.getElementById('live-top-tbody');
    if (tbody && data.top_submissions) {
        tbody.innerHTML = data.top_submissions.map(s => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">${s.product_name}</td>
                <td class="px-4 py-3 text-right font-mono text-sm">${s.quantity_sold}</td>
                <td class="px-4 py-3 text-right font-mono text-sm">${s.quantity_remaining}</td>
                <td class="px-4 py-3 text-right font-mono text-sm">${s.revenue_formatted}</td>
            </tr>`).join('') || '<tr><td colspan="4" class="py-6 text-center text-gray-400 text-sm">No data yet.</td></tr>';
    }
}

// ─── Analytics ────────────────────────────────────────────────────────────────

function loadAnalytics() {
    $.get(window.URLS.analyticsData)
        .done(res => renderAnalytics(res.data))
        .fail(() => {
            const el = document.getElementById('analytics-tbody');
            if (el) el.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-gray-400 text-sm">Analytics unavailable.</td></tr>';
        });
}

function renderAnalytics(data) {
    if (!data) return;

    const s = data.summary;
    if (s) {
        setText('an-units', (s.units_sold ?? 0).toLocaleString());
        setText('an-revenue', formatMoney(s.gross_revenue));
        setText('an-discount', formatMoney(s.discount_given));
        setText('an-commission', formatMoney(s.platform_commission));
        setText('an-payout', formatMoney(s.vendor_payout));
        setText('an-conversion', (s.avg_conversion_pct ?? 0).toFixed(2) + '%');
    }

    const tbody = document.getElementById('analytics-tbody');
    if (tbody && data.by_day) {
        tbody.innerHTML = data.by_day.map(row => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm text-gray-700">${row.date}</td>
                <td class="px-4 py-2 text-right font-mono text-sm">${row.units_sold}</td>
                <td class="px-4 py-2 text-right font-mono text-sm">${formatMoney(row.gross_revenue)}</td>
                <td class="px-4 py-2 text-right font-mono text-sm">${formatMoney(row.discount_given)}</td>
            </tr>`).join('') || '<tr><td colspan="4" class="py-4 text-center text-gray-400 text-sm">No daily data.</td></tr>';
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
        // If it's a stat-card, update the value span inside
        const valueEl = el.querySelector('[data-stat-value]') || el.querySelector('.text-2xl') || el;
        valueEl.textContent = value;
    }
}

function formatMoney(cents) {
    if (cents == null) return '—';
    return (cents / 100).toFixed(2);
}
