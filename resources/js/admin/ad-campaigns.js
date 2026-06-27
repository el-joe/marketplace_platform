/**
 * Ad Campaigns admin JS
 * Handles: campaign datatable, fraud datatable, bookings datatable,
 * approve/reject/pause/resume modals, block-IP modal, creative review modal,
 * and Chart.js performance chart on the campaign show page.
 */

import Chart from 'chart.js/auto';
import DataTable from 'datatables.net';

/* ─── CSRF helper ─────────────────────────────────────────────────────────── */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/* ─── Generic POST helper ─────────────────────────────────────────────────── */
function postJson(url, data = {}) {
    return $.ajax({
        url,
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

/* ─── Campaigns DataTable ─────────────────────────────────────────────────── */
let campaignsTable = null;

function initCampaignsTable() {
    const el = document.getElementById('campaigns-table');
    if (!el) return;

    campaignsTable = new DataTable('#campaigns-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes?.campaignsDatatable ?? '/ad-campaigns/datatable',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.status = document.getElementById('filter-status')?.value ?? '';
                d.type = document.getElementById('active-campaign-type')?.value ?? '';
                d.country_id = document.getElementById('filter-country')?.value ?? '';
                d.date_from = document.getElementById('filter-date-from')?.value ?? '';
                d.date_to = document.getElementById('filter-date-to')?.value ?? '';
                d.search = { value: document.getElementById('search-input')?.value ?? '' };
            },
        },
        columns: [
            { data: 'vendor', title: 'Vendor' },
            { data: 'name', title: 'Campaign' },
            { data: 'type', title: 'Type', orderable: false },
            { data: 'status', title: 'Status', orderable: false },
            { data: 'budget', title: 'Budget', orderable: false },
            { data: 'spend', title: 'Spend', orderable: false },
            { data: 'utilization', title: 'Utilization', orderable: false },
            { data: 'quality', title: 'Quality', orderable: false },
            { data: 'date_range', title: 'Dates', orderable: false },
            { data: 'actions', title: '', orderable: false },
        ],
        order: [[0, 'asc']],
    });
}

/* ─── Tab filter ──────────────────────────────────────────────────────────── */
let activeCampaignType = 'all';

function initCampaignTabs() {
    // hidden field to pass type filter value to datatable
    if (!document.getElementById('active-campaign-type')) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'active-campaign-type';
        hiddenInput.value = 'all';
        document.body.appendChild(hiddenInput);
    }

    document.querySelectorAll('.campaign-type-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.campaign-type-tab').forEach(t => {
                t.classList.remove('border-primary-500', 'text-primary-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('border-primary-500', 'text-primary-600');
            this.classList.remove('border-transparent', 'text-gray-500');

            activeCampaignType = this.dataset.type;
            document.getElementById('active-campaign-type').value = activeCampaignType;
            campaignsTable?.ajax.reload();
        });
    });
}

/* ─── Filter bar wiring ───────────────────────────────────────────────────── */
function initFilters() {
    ['filter-status', 'filter-country', 'filter-date-from', 'filter-date-to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => campaignsTable?.ajax.reload());
    });
    document.getElementById('search-input')?.addEventListener('keyup', debounce(() => campaignsTable?.ajax.reload(), 400));

    document.getElementById('clear-filters')?.addEventListener('click', () => {
        ['filter-status', 'filter-country', 'filter-date-from', 'filter-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const si = document.getElementById('search-input');
        if (si) si.value = '';
        // Reset campaign type tab to "all"
        document.querySelectorAll('.campaign-type-tab').forEach(t => {
            t.classList.remove('border-primary-500', 'text-primary-600');
            t.classList.add('border-transparent', 'text-gray-500');
        });
        const allTab = document.querySelector('.campaign-type-tab[data-type="all"]');
        if (allTab) {
            allTab.classList.add('border-primary-500', 'text-primary-600');
            allTab.classList.remove('border-transparent', 'text-gray-500');
        }
        const hiddenType = document.getElementById('active-campaign-type');
        if (hiddenType) hiddenType.value = 'all';
        campaignsTable?.ajax.reload();
    });
}

/* ─── Campaign Approve/Reject/Pause/Resume modals ─────────────────────────── */
function initCampaignActionModals() {
    let pendingApproveUrl = null;
    let pendingRejectUrl = null;
    let pendingPauseUrl = null;
    let pendingResumeUrl = null;

    /* ── Approve ── */
    $(document).on('click', '.js-approve-btn', function () {
        pendingApproveUrl = $(this).data('url');
        $('#approve-campaign-name').text($(this).data('name') ?? '');
        $('#approve-modal').modal('open');
    });

    $('#confirm-approve-btn').on('click', function () {
        if (!pendingApproveUrl) return;
        const $btn = $(this).prop('disabled', true).text('Approving…');
        postJson(pendingApproveUrl)
            .done(res => {
                window.Toast?.success(res.message ?? 'Campaign approved.');
                $('#approve-modal').modal('close');
                campaignsTable?.ajax.reload();
                // reload page if on show page
                if (!campaignsTable) location.reload();
            })
            .fail(xhr => {
                window.Toast?.error(xhr.responseJSON?.message ?? 'Failed to approve campaign.');
            })
            .always(() => $btn.prop('disabled', false).text('Approve'));
    });

    /* ── Reject ── */
    $(document).on('click', '.js-reject-btn', function () {
        pendingRejectUrl = $(this).data('url');
        $('#reject-campaign-name').text($(this).data('name') ?? '');
        $('#reject-reason-input').val('');
        $('#reject-reason-error').addClass('hidden');
        $('#reject-modal').modal('open');
    });

    $('#confirm-reject-btn').on('click', function () {
        const reason = $('#reject-reason-input').val().trim();
        if (!reason) {
            $('#reject-reason-error').removeClass('hidden');
            return;
        }
        const $btn = $(this).prop('disabled', true).text('Rejecting…');
        postJson(pendingRejectUrl, { rejection_reason: reason })
            .done(res => {
                window.Toast?.success(res.message ?? 'Campaign rejected.');
                $('#reject-modal').modal('close');
                campaignsTable?.ajax.reload();
                if (!campaignsTable) location.reload();
            })
            .fail(xhr => {
                window.Toast?.error(xhr.responseJSON?.message ?? 'Failed to reject campaign.');
            })
            .always(() => $btn.prop('disabled', false).text('Reject'));
    });

    /* ── Pause ── */
    $(document).on('click', '.js-pause-btn', function () {
        pendingPauseUrl = $(this).data('url');
        $('#pause-campaign-name').text($(this).data('name') ?? '');
        $('#pause-modal').modal('open');
    });

    $('#confirm-pause-btn').on('click', function () {
        if (!pendingPauseUrl) return;
        const $btn = $(this).prop('disabled', true).text('Pausing…');
        postJson(pendingPauseUrl)
            .done(res => {
                window.Toast?.success(res.message ?? 'Campaign paused.');
                $('#pause-modal').modal('close');
                campaignsTable?.ajax.reload();
                if (!campaignsTable) location.reload();
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'))
            .always(() => $btn.prop('disabled', false).text('Pause'));
    });

    /* ── Resume ── */
    $(document).on('click', '.js-resume-btn', function () {
        pendingResumeUrl = $(this).data('url');
        $('#resume-campaign-name').text($(this).data('name') ?? '');
        $('#resume-modal').modal('open');
    });

    $('#confirm-resume-btn').on('click', function () {
        if (!pendingResumeUrl) return;
        const $btn = $(this).prop('disabled', true).text('Resuming…');
        postJson(pendingResumeUrl)
            .done(res => {
                window.Toast?.success(res.message ?? 'Campaign resumed.');
                $('#resume-modal').modal('close');
                campaignsTable?.ajax.reload();
                if (!campaignsTable) location.reload();
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'))
            .always(() => $btn.prop('disabled', false).text('Resume'));
    });
}

/* ─── Fraud DataTable ─────────────────────────────────────────────────────── */
let fraudTable = null;

function initFraudTable() {
    const el = document.getElementById('fraud-table');
    if (!el) return;

    fraudTable = new DataTable('#fraud-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes?.fraudDatatable ?? '/ad-campaigns/fraud/datatable',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.is_blocked = document.getElementById('fraud-filter-status')?.value ?? '';
                d.search = { value: document.getElementById('fraud-search')?.value ?? '' };
            },
        },
        columns: [
            { data: 'ip_address', title: 'IP Address' },
            { data: 'campaign', title: 'Campaign' },
            { data: 'clicks_last_hour', title: 'Clicks/Hr' },
            { data: 'clicks_last_24h', title: 'Clicks/24h' },
            { data: 'is_blocked', title: 'Status', orderable: false },
            { data: 'blocked_at', title: 'Blocked At' },
            { data: 'block_reason', title: 'Reason', orderable: false },
            { data: 'actions', title: '', orderable: false },
        ],
        order: [[5, 'desc']],
    });

    document.getElementById('fraud-filter-status')?.addEventListener('change', () => fraudTable?.ajax.reload());
    document.getElementById('fraud-search')?.addEventListener('keyup', debounce(() => fraudTable?.ajax.reload(), 400));
}

/* ─── Block IP Modal ──────────────────────────────────────────────────────── */
function initBlockIpModal() {
    let pendingBlockUrl = null;

    $(document).on('click', '.js-block-ip-btn', function () {
        pendingBlockUrl = $(this).data('url');
        $('#block-ip-address').text($(this).data('ip') ?? '');
        $('#block-reason-input').val('');
        $('#block-ip-modal').modal('open');
    });

    $('#confirm-block-btn').on('click', function () {
        if (!pendingBlockUrl) return;
        const $btn = $(this).prop('disabled', true).text('Blocking…');
        postJson(pendingBlockUrl, { block_reason: $('#block-reason-input').val().trim() })
            .done(res => {
                window.Toast?.success(res.message ?? 'IP blocked.');
                $('#block-ip-modal').modal('close');
                fraudTable?.ajax.reload();
                // reload if we're on the show page (inline fraud tab)
                if (!fraudTable) {
                    const row = document.querySelector(`.js-block-ip-btn[data-url="${pendingBlockUrl}"]`)?.closest('tr');
                    if (row) row.remove();
                }
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed to block IP.'))
            .always(() => $btn.prop('disabled', false).text('Block IP'));
    });
}

/* ─── Bookings DataTable ──────────────────────────────────────────────────── */
let bookingsTable = null;

function initBookingsTable() {
    const el = document.getElementById('bookings-table');
    if (!el) return;

    bookingsTable = new DataTable('#bookings-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes?.bookingsDatatable ?? '/paid-ad-bookings/datatable',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.status = document.getElementById('bookings-filter-status')?.value ?? '';
                d.payment_status = document.getElementById('bookings-filter-payment')?.value ?? '';
                d.date_from = document.getElementById('bookings-date-from')?.value ?? '';
                d.date_to = document.getElementById('bookings-date-to')?.value ?? '';
                d.search = { value: document.getElementById('bookings-search')?.value ?? '' };
            },
        },
        columns: [
            { data: 'reference', title: 'Reference' },
            { data: 'vendor', title: 'Vendor' },
            { data: 'slot', title: 'Slot' },
            { data: 'dates', title: 'Dates', orderable: false },
            { data: 'rate', title: 'Rate', orderable: false },
            { data: 'status', title: 'Status', orderable: false },
            { data: 'payment_status', title: 'Payment', orderable: false },
            { data: 'actions', title: '', orderable: false },
        ],
        order: [[0, 'desc']],
    });

    ['bookings-filter-status', 'bookings-filter-payment', 'bookings-date-from', 'bookings-date-to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => bookingsTable?.ajax.reload());
    });
    document.getElementById('bookings-search')?.addEventListener('keyup', debounce(() => bookingsTable?.ajax.reload(), 400));

    document.getElementById('clear-bookings-filters')?.addEventListener('click', () => {
        ['bookings-filter-status', 'bookings-filter-payment', 'bookings-date-from', 'bookings-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const si = document.getElementById('bookings-search');
        if (si) si.value = '';
        bookingsTable?.ajax.reload();
    });
}

/* ─── Booking Approve/Reject Modals ───────────────────────────────────────── */
function initBookingActionModals() {
    let pendingApproveUrl = null;
    let pendingRejectUrl = null;

    /* ── Approve booking ── */
    $(document).on('click', '.js-approve-booking-btn', function () {
        pendingApproveUrl = $(this).data('url');
        $('#approve-booking-ref').text($(this).data('ref') ?? '');
        $('#approve-booking-modal').modal('open');
    });

    $('#confirm-approve-booking-btn').on('click', function () {
        if (!pendingApproveUrl) return;
        const $btn = $(this).prop('disabled', true).text('Approving…');
        postJson(pendingApproveUrl)
            .done(res => {
                window.Toast?.success(res.message ?? 'Booking approved.');
                $('#approve-booking-modal').modal('close');
                bookingsTable?.ajax.reload();
                if (!bookingsTable) location.reload();
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'))
            .always(() => $btn.prop('disabled', false).text('Approve'));
    });

    /* ── Reject booking ── */
    $(document).on('click', '.js-reject-booking-btn', function () {
        pendingRejectUrl = $(this).data('url');
        $('#reject-booking-ref').text($(this).data('ref') ?? '');
        $('#reject-booking-reason').val('');
        $('#reject-booking-reason-error').addClass('hidden');
        $('#reject-booking-modal').modal('open');
    });

    $('#confirm-reject-booking-btn').on('click', function () {
        const reason = $('#reject-booking-reason').val().trim();
        if (!reason) {
            $('#reject-booking-reason-error').removeClass('hidden');
            return;
        }
        const $btn = $(this).prop('disabled', true).text('Rejecting…');
        postJson(pendingRejectUrl, { rejection_reason: reason })
            .done(res => {
                window.Toast?.success(res.message ?? 'Booking rejected.');
                $('#reject-booking-modal').modal('close');
                bookingsTable?.ajax.reload();
                if (!bookingsTable) location.reload();
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'))
            .always(() => $btn.prop('disabled', false).text('Reject'));
    });
}

/* ─── Creative Review Modal ───────────────────────────────────────────────── */
function initCreativeReviewModals() {
    let pendingRejectCreativeUrl = null;
    let pendingApproveCreativeUrl = null;

    /* ── Approve creative ── */
    $(document).on('click', '.js-approve-creative-btn', function () {
        pendingApproveCreativeUrl = $(this).data('url');
        const $btn = $(this).prop('disabled', true).text('Approving…');
        postJson(pendingApproveCreativeUrl, { action: 'approve' })
            .done(res => {
                window.Toast?.success(res.message ?? 'Creative approved.');
                location.reload();
            })
            .fail(xhr => {
                window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.');
                $btn.prop('disabled', false).text('Approve');
            });
    });

    /* ── Reject creative (open modal) ── */
    $(document).on('click', '.js-reject-creative-btn', function () {
        pendingRejectCreativeUrl = $(this).data('url');
        $('#creative-rejection-reason').val('');
        $('#creative-rejection-code').val('');
        $('#creative-rejection-reason-error').addClass('hidden');
        $('#reject-creative-modal').modal('open');
    });

    $('#confirm-reject-creative-btn').on('click', function () {
        const reason = $('#creative-rejection-reason').val().trim();
        if (!reason) {
            $('#creative-rejection-reason-error').removeClass('hidden');
            return;
        }
        const $btn = $(this).prop('disabled', true).text('Rejecting…');
        postJson(pendingRejectCreativeUrl, {
            action: 'reject',
            rejection_reason: reason,
            rejection_code: $('#creative-rejection-code').val() || null,
        })
            .done(res => {
                window.Toast?.success(res.message ?? 'Creative rejected.');
                $('#reject-creative-modal').modal('close');
                location.reload();
            })
            .fail(xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'))
            .always(() => $btn.prop('disabled', false).text('Reject Creative'));
    });
}

/* ─── Slots DataTable ─────────────────────────────────────────────────────── */
function initSlotsTable() {
    const el = document.getElementById('slots-table');
    if (!el) return;

    const slotsTable = new DataTable('#slots-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes?.slotsDatatable ?? '/ad-slots/datatable',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.is_available = document.getElementById('slots-filter-available')?.value ?? '';
                d.country_id = document.getElementById('slots-filter-country')?.value ?? '';
            },
        },
        columns: [
            { data: 'name', title: 'Name' },
            { data: 'placement', title: 'Placement', orderable: false },
            { data: 'country', title: 'Country', orderable: false },
            { data: 'pricing_model', title: 'Pricing', orderable: false },
            { data: 'base_rate', title: 'Base Rate', orderable: false },
            { data: 'booking_days', title: 'Booking Days', orderable: false },
            { data: 'is_available', title: 'Available', orderable: false },
            { data: 'actions', title: '', orderable: false },
        ],
        order: [[0, 'asc']],
    });

    ['slots-filter-available', 'slots-filter-country'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => slotsTable.ajax.reload());
    });
}

/* ─── Performance Chart (campaign show page) ──────────────────────────────── */
function initPerformanceChart() {
    const canvas = document.getElementById('performance-chart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels ?? '[]');
    const impressions = JSON.parse(canvas.dataset.impressions ?? '[]');
    const clicks = JSON.parse(canvas.dataset.clicks ?? '[]');

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Impressions',
                    data: impressions,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    yAxisID: 'y1',
                },
                {
                    label: 'Clicks',
                    data: clicks,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 2,
                    yAxisID: 'y2',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y1: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Impressions' },
                    grid: { color: 'rgba(0,0,0,0.04)' },
                },
                y2: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Clicks' },
                    grid: { drawOnChartArea: false },
                },
            },
            plugins: {
                legend: { position: 'top' },
            },
        },
    });
}

/* ─── Utility ─────────────────────────────────────────────────────────────── */
function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

/* ─── Init ────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    initCampaignsTable();
    initCampaignTabs();
    initFilters();
    initCampaignActionModals();
    initFraudTable();
    initBlockIpModal();
    initBookingsTable();
    initBookingActionModals();
    initCreativeReviewModals();
    initSlotsTable();
    initPerformanceChart();
});
