/**
 * resources/js/admin/vendors.js
 *
 * Vendor management panel:
 *   - Vendors DataTable (server-side, with filters + bulk actions)
 *   - Application queue (approve / quick-reject cards)
 *   - Vendor show page:
 *       · Profile inline edit
 *       · Document verify / reject
 *       · Bank account verify
 *       · Strikes (issue, auto-suspend warnings)
 *       · Payout hold / release
 *       · Suspend / reactivate / blacklist / approve
 *       · Assign manager
 *       · Send notification
 *       · Performance charts (Chart.js lazy-loaded)
 *   - Modal open / close helpers
 *   - Toast notifications (assumes global Toast helper)
 */

import $ from 'jquery';
import DataTable from 'datatables.net';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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

/**
 * Disable a button, show spinner, return its jqXHR so we can re-enable in `.always()`.
 */
function withLoading(btnOrSelector, jqXhr) {
    const $btn = typeof btnOrSelector === 'string' ? $(btnOrSelector) : $(btnOrSelector);
    const original = $btn.html();
    $btn.prop('disabled', true).html(
        '<svg class="animate-spin w-4 h-4 inline" fill="none" viewBox="0 0 24 24">' +
        '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>' +
        '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>'
    );
    return jqXhr.always(function () {
        $btn.prop('disabled', false).html(original);
    });
}

// ─── Row-action dropdown state ────────────────────────────────────────────────

let activeVendorId     = null;
let activeVendorName   = null;
let activeVendorStatus = null;

// ─── Init ─────────────────────────────────────────────────────────────────────

$(function () {

    // ══════════════════════════════════════════════════════════════════════════
    // VENDORS INDEX — DataTable
    // ══════════════════════════════════════════════════════════════════════════

    const $vendorTable = $('#vendors-table');
    let dt;

    if ($vendorTable.length) {
        dt = new DataTable('#vendors-table', {
            processing: true,
            serverSide: true,
            ajax: {
                url: '/vendors/datatable',
                type: 'POST',
                data: function (d) {
                    d._token = csrfToken();
                    d.global_status = $('#filter-status').val();
                    d.country_id = $('#filter-country').val();
                    d.account_manager_admin_id = $('#filter-manager').val();
                    d.date_from = $('#filter-date-from').val();
                    d.date_to = $('#filter-date-to').val();
                },
            },
            columns: [
                {
                    data: null, orderable: false, searchable: false,
                    render: function () { return '<input type="checkbox" class="row-check rounded border-gray-300">'; }
                },
                { data: 'store', render: renderStore },
                { data: 'owner_name' },
                { data: 'email' },
                { data: 'gmv' },
                { data: 'orders' },
                { data: 'rating' },
                { data: 'global_status', render: renderStatus },
                { data: 'manager' },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false, render: renderActions },
            ],
            order: [[9, 'desc']],
            pageLength: 25,
            dom: 'rtip',
        });

        // Trigger reload on filter change
        $('#filter-status, #filter-country, #filter-manager, #filter-date-from, #filter-date-to')
            .on('change', function () { dt.ajax.reload(); });

        // Global search debounced
        let searchTimer;
        $('#search-input').on('input', function () {
            clearTimeout(searchTimer);
            const val = $(this).val();
            searchTimer = setTimeout(function () {
                dt.search(val).draw();
            }, 350);
        });

        // Reset filters
        $('#clear-filters').on('click', function () {
            $('#filter-status, #filter-country, #filter-manager').val('');
            $('#filter-date-from, #filter-date-to, #search-input').val('');
            dt.search('').ajax.reload();
        });
    }

    // ── Row-action dropdown ────────────────────────────────────────────────────

    function closeRowDropdown() {
        $('#vendor-row-dropdown').addClass('hidden');
        // activeVendorId = null;
    }

    $(document).on('click', '.vendor-dots-btn', function (e) {
        e.stopPropagation();
        const $btn  = $(this);
        const id     = $btn.data('id');
        const name   = $btn.data('name');
        const status = $btn.data('status');
        const $dd    = $('#vendor-row-dropdown');

        // Toggle if same button clicked again
        if (activeVendorId === id && !$dd.hasClass('hidden')) {
            closeRowDropdown();
            return;
        }

        activeVendorId     = id;
        activeVendorName   = name;
        activeVendorStatus = status;

        // Update static links
        $('#vrd-view').attr('href', '/vendors/' + id);
        $('#vrd-edit').attr('href', '/vendors/' + id + '/edit');

        // Reset status-conditional items
        $('#vrd-divider, #vrd-approve, #vrd-reject, #vrd-suspend, #vrd-reactivate').addClass('hidden');

        if (status === 'active') {
            $('#vrd-divider, #vrd-suspend').removeClass('hidden');
        } else if (status === 'under_review') {
            $('#vrd-divider, #vrd-approve, #vrd-reject').removeClass('hidden');
        } else if (status === 'suspended') {
            $('#vrd-divider, #vrd-reactivate').removeClass('hidden');
        }

        // Position (fixed to viewport)
        const rect = this.getBoundingClientRect();
        $dd.css({
            top:  rect.bottom + 4,
            left: rect.right - 192,   // 192px = w-48
        }).removeClass('hidden');
    });

    // Close dropdown on outside click
    $(document).on('click', function () { closeRowDropdown(); });
    $('#vendor-row-dropdown').on('click', function (e) { e.stopPropagation(); });

    // ── Dropdown → per-action modals ──────────────────────────────────────────

    $('#vrd-suspend').on('click', function () {
        closeRowDropdown();
        $('#suspend-vendor-name').text(activeVendorName);
        $('#suspend-reason').val('');
        $('#suspend-reason-error').addClass('hidden');
        openModal('suspend-modal');
    });

    $('#vrd-approve').on('click', function () {
        closeRowDropdown();
        $('#approve-vendor-name').text(activeVendorName);
        openModal('approve-modal');
    });

    $('#vrd-reject').on('click', function () {
        closeRowDropdown();
        $('#reject-vendor-name').text(activeVendorName);
        $('#reject-reason').val('');
        $('#reject-reason-error').addClass('hidden');
        openModal('reject-modal');
    });

    $('#vrd-reactivate').on('click', function () {
        closeRowDropdown();
        $('#reactivate-vendor-name').text(activeVendorName);
        openModal('reactivate-modal');
    });

    // ── Per-action confirm buttons ─────────────────────────────────────────────

    $('#suspend-confirm-btn').on('click', function () {
        const reason = $('#suspend-reason').val().trim();
        if (reason.length < 5) {
            $('#suspend-reason-error').removeClass('hidden');
            return;
        }
        const vendorId = activeVendorId;
        const vendorName = activeVendorName;

        withLoading(this,
            $.post('/vendors/' + vendorId + '/suspend', { _token: csrfToken(), reason })
                .done(function (res) {
                    closeModal('suspend-modal');
                    Toast.success((vendorName ? vendorName + ' suspended.' : res.message));
                    if (dt) dt.ajax.reload();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Suspension failed.'); })
        );
    });

    $('#approve-confirm-btn').on('click', function () {
        const vendorId = activeVendorId;
        const vendorName = activeVendorName;
        withLoading(this,
            $.post('/vendors/' + vendorId + '/approve', { _token: csrfToken() })
                .done(function (res) {
                    closeModal('approve-modal');
                    Toast.success((vendorName ? vendorName + ' approved.' : res.message));
                    if (dt) dt.ajax.reload();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Approval failed.'); })
        );
    });

    $('#reject-confirm-btn').on('click', function () {
        const reason = $('#reject-reason').val().trim();
        if (reason.length < 5) {
            $('#reject-reason-error').removeClass('hidden');
            return;
        }
        const vendorId = activeVendorId;
        const vendorName = activeVendorName;
        withLoading(this,
            $.post('/vendors/' + vendorId + '/reject', { _token: csrfToken(), reason })
                .done(function (res) {
                    closeModal('reject-modal');
                    Toast.success((vendorName ? vendorName + ' rejected.' : res.message));
                    if (dt) dt.ajax.reload();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Rejection failed.'); })
        );
    });

    $('#reactivate-confirm-btn').on('click', function () {
        const vendorId = activeVendorId;
        const vendorName = activeVendorName;
        withLoading(this,
            $.post('/vendors/' + vendorId + '/reactivate', { _token: csrfToken() })
                .done(function (res) {
                    closeModal('reactivate-modal');
                    Toast.success((vendorName ? vendorName + ' reactivated.' : res.message));
                    if (dt) dt.ajax.reload();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Reactivation failed.'); })
        );
    });

    // ── Bulk actions ──────────────────────────────────────────────────────────

    let selectedIds = [];

    $(document).on('change', '#select-all', function () {
        $('.row-check').prop('checked', this.checked);
        syncBulkBar();
    });

    $(document).on('change', '.row-check', function () {
        syncBulkBar();
        $('#select-all').prop('checked', $('.row-check:not(:checked)').length === 0);
    });

    function syncBulkBar() {
        selectedIds = [];
        if (dt) {
            dt.rows().every(function () {
                const row = this.node();
                if ($(row).find('.row-check').is(':checked')) {
                    selectedIds.push(this.data().actions.id);
                }
            });
        }
        const count = selectedIds.length;
        $('#bulk-bar').toggleClass('hidden', count === 0);
        $('#selected-count').text(count);
    }

    function clearSelection() {
        $('.row-check, #select-all').prop('checked', false);
        selectedIds = [];
        $('#bulk-bar').addClass('hidden');
    }

    $('#clear-selection').on('click', clearSelection);

    // Bulk Suspend
    $(document).on('click', '[data-bulk="suspend"]', function () {
        $('#bulk-suspend-count').text(selectedIds.length);
        $('#bulk-suspend-reason').val('');
        $('#bulk-suspend-reason-error').addClass('hidden');
        openModal('bulk-suspend-modal');
    });

    $('#bulk-suspend-confirm-btn').on('click', function () {
        const reason = $('#bulk-suspend-reason').val().trim();
        if (reason.length < 5) {
            $('#bulk-suspend-reason-error').removeClass('hidden');
            return;
        }
        withLoading(this,
            $.post('/vendors/bulk', {
                _token: csrfToken(), action: 'suspend',
                vendor_ids: selectedIds, reason,
            })
                .done(function (res) {
                    closeModal('bulk-suspend-modal');
                    Toast.success(res.message);
                    if (dt) dt.ajax.reload();
                    clearSelection();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Bulk suspension failed.'); })
        );
    });

    // Bulk Reactivate
    $(document).on('click', '[data-bulk="reactivate"]', function () {
        $('#bulk-reactivate-count').text(selectedIds.length);
        openModal('bulk-reactivate-modal');
    });

    $('#bulk-reactivate-confirm-btn').on('click', function () {
        withLoading(this,
            $.post('/vendors/bulk', {
                _token: csrfToken(), action: 'reactivate',
                vendor_ids: selectedIds,
            })
                .done(function (res) {
                    closeModal('bulk-reactivate-modal');
                    Toast.success(res.message);
                    if (dt) dt.ajax.reload();
                    clearSelection();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Bulk reactivation failed.'); })
        );
    });

    // Bulk misc (place_hold / assign_manager / export)
    let pendingBulkAction = null;

    $(document).on('click', '[data-bulk="place_hold"], [data-bulk="assign_manager"], [data-bulk="export"]', function () {
        pendingBulkAction = $(this).data('bulk');
        const needsAdmin  = pendingBulkAction === 'assign_manager';
        const needsReason = pendingBulkAction === 'place_hold';

        $('#bulk-admin-select').toggleClass('hidden', !needsAdmin);
        $('#bulk-reason-field').toggleClass('hidden', !needsReason);
        openModal('bulk-misc-modal');
    });

    $('#bulk-misc-confirm-btn').on('click', function () {
        if (!pendingBulkAction || selectedIds.length === 0) return;

        withLoading(this,
            $.post('/vendors/bulk', {
                _token: csrfToken(),
                action: pendingBulkAction,
                vendor_ids: selectedIds,
                reason: $('#bulk-reason').val(),
                admin_id: $('#bulk-admin-id').val(),
            })
                .done(function (res) {
                    closeModal('bulk-misc-modal');
                    Toast.success(res.message);
                    if (dt) dt.ajax.reload();
                    clearSelection();
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Bulk action failed.'); })
        );
    });

    // ══════════════════════════════════════════════════════════════════════════
    // APPLICATION QUEUE — card actions
    // ══════════════════════════════════════════════════════════════════════════

    // Quick approve on queue card
    $(document).on('click', '[data-approve-vendor]', function () {
        const vendorId = $(this).data('approve-vendor');
        if (!confirm('Approve this vendor account?')) return;

        withLoading(this,
            $.post('/vendors/' + vendorId + '/approve', { _token: csrfToken() })
                .done(function (res) {
                    Toast.success(res.message);
                    setTimeout(function () { location.reload(); }, 700);
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Approval failed.'); })
        );
    });

    // Populate quick-reject modal
    $(document).on('click', '[data-open-modal="quick-reject-modal"]', function () {
        const vendorId = $(this).data('vendor-id');
        const vendorName = $(this).data('vendor-name');
        $('#qr-vendor-id').val(vendorId);
        $('#qr-vendor-name').text(vendorName);
        openModal('quick-reject-modal');
    });

    $('#quick-reject-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $('#qr-vendor-id').val();

        withLoading('#quick-reject-form [type=submit]',
            $.post('/vendors/' + vendorId + '/reject', $(this).serialize())
                .done(function (res) {
                    closeModal('quick-reject-modal');
                    Toast.success(res.message);
                    setTimeout(function () { location.reload(); }, 700);
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Rejection failed.'); })
        );
    });

    // ══════════════════════════════════════════════════════════════════════════
    // VENDOR SHOW PAGE — modal wiring
    // ══════════════════════════════════════════════════════════════════════════

    // Generic modal open via data-open-modal
    $(document).on('click', '[data-open-modal]', function () {
        openModal($(this).data('open-modal'));
    });

    // Close modal on backdrop click or [data-modal-close]
    $(document).on('click', '.modal-backdrop', function (e) {
        if ($(e.target).is('.modal-backdrop')) {
            $(this).addClass('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
    $(document).on('click', '[data-modal-close]', function () {
        $(this).closest('.modal-backdrop').addClass('hidden');
        document.body.classList.remove('overflow-hidden');
    });

    // ── Reactivate vendor ─────────────────────────────────────────────────────
    $(document).on('click', '[data-reactivate-vendor]', function () {
        const vendorId = $(this).data('reactivate-vendor');
        if (!confirm('Reactivate this vendor?')) return;

        withLoading(this,
            $.post('/vendors/' + vendorId + '/reactivate', { _token: csrfToken() })
                .done(function (res) { Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function () { Toast.error('Reactivation failed.'); })
        );
    });

    // ── Suspend form ──────────────────────────────────────────────────────────
    $('#suspend-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#suspend-form [type=submit]',
            $.post('/vendors/' + vendorId + '/suspend', $(this).serialize())
                .done(function (res) { closeModal('suspend-modal'); Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    // ── Reject Application form ───────────────────────────────────────────────
    $('#reject-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#reject-form [type=submit]',
            $.post('/vendors/' + vendorId + '/reject', $(this).serialize())
                .done(function (res) { closeModal('reject-modal'); Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    // ── Blacklist form ────────────────────────────────────────────────────────
    $('#blacklist-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#blacklist-form [type=submit]',
            $.post('/vendors/' + vendorId + '/blacklist', $(this).serialize())
                .done(function (res) { closeModal('blacklist-modal'); Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    // ── Issue Strike form ─────────────────────────────────────────────────────
    $('#issue-strike-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $('#strike-vendor-id').val();

        withLoading('#issue-strike-btn',
            $.post('/vendors/' + vendorId + '/strikes', $(this).serialize())
                .done(function (res) {
                    closeModal('issue-strike-modal');
                    Toast.success(res.message);
                    $('#active-strikes-count').text(res.data.active_count);
                    if (res.data.auto_suspended) {
                        Toast.warning('Vendor auto-suspended: 3 active strikes or critical strike issued.');
                    }
                    setTimeout(function () { location.reload(); }, 1200);
                })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Strike failed.'); })
        );
    });

    // Hide expiry for critical, show warning banner
    $(document).on('change', '[name="severity"]', function () {
        const isCritical = $(this).val() === 'critical';
        $('#strike-expiry-field').toggleClass('hidden', isCritical);
        if (isCritical) {
            $('#strike-warning').text('Critical strike will auto-suspend the vendor immediately.').removeClass('hidden');
        } else {
            $('#strike-warning').addClass('hidden');
        }
    });

    // ── Payout Hold form ──────────────────────────────────────────────────────
    $('#hold-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#hold-form [type=submit]',
            $.post('/vendors/' + vendorId + '/hold', $(this).serialize())
                .done(function (res) { closeModal('place-hold-modal'); Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    $(document).on('click', '[data-release-hold]', function () {
        const vendorId = $(this).data('release-hold');
        if (!confirm('Release payout hold?')) return;

        withLoading(this,
            $.post('/vendors/' + vendorId + '/release-hold', { _token: csrfToken() })
                .done(function (res) { Toast.success(res.message); setTimeout(function () { location.reload(); }, 700); })
                .fail(function () { Toast.error('Failed to release hold.'); })
        );
    });

    // ── Assign Manager form ───────────────────────────────────────────────────
    $('#assign-manager-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#assign-manager-form [type=submit]',
            $.post('/vendors/' + vendorId + '/assign-manager', $(this).serialize())
                .done(function (res) { closeModal('assign-manager-modal'); Toast.success(res.message); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    // ── Profile inline edit ───────────────────────────────────────────────────
    $('#edit-profile-btn').on('click', function () {
        $('#profile-view').addClass('hidden');
        $('#profile-edit-form').removeClass('hidden');
        $(this).addClass('hidden');
    });

    $('#cancel-edit-btn').on('click', function () {
        $('#profile-view').removeClass('hidden');
        $('#profile-edit-form').addClass('hidden');
        $('#edit-profile-btn').removeClass('hidden');
    });

    $('#profile-edit-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#profile-edit-form [type=submit]',
            $.ajax({ url: '/vendors/' + vendorId, method: 'PUT', data: $(this).serialize() })
                .done(function (res) { Toast.success(res.message ?? 'Profile updated.'); setTimeout(function () { location.reload(); }, 700); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Update failed.'); })
        );
    });

    // ── Document verify ───────────────────────────────────────────────────────
    $(document).on('click', '[data-verify-doc]', function () {
        const docId = $(this).data('verify-doc');
        if (!confirm('Verify this document?')) return;

        withLoading(this,
            $.post('/vendors/documents/' + docId + '/verify', { _token: csrfToken() })
                .done(function () { Toast.success('Document verified.'); updateDocRow(docId, 'verified'); })
                .fail(function () { Toast.error('Verification failed.'); })
        );
    });

    $(document).on('click', '[data-reject-doc]', function () {
        $('#reject-doc-id').val($(this).data('reject-doc'));
        openModal('reject-document-modal');
    });

    $('#reject-doc-form').on('submit', function (e) {
        e.preventDefault();
        const docId = $('#reject-doc-id').val();

        withLoading('#reject-doc-form [type=submit]',
            $.post('/vendors/documents/' + docId + '/reject', $(this).serialize())
                .done(function () { closeModal('reject-document-modal'); Toast.success('Document rejected.'); updateDocRow(docId, 'rejected'); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed.'); })
        );
    });

    function updateDocRow(docId, status) {
        const colorMap = { verified: 'success', rejected: 'danger', pending: 'gray', expired: 'danger', approved: 'success' };
        const color = colorMap[status] ?? 'gray';
        $('[data-doc-id="' + docId + '"]').find('td:nth-child(2)').html(
            '<span class="inline-flex items-center gap-1 rounded-full font-medium px-2 py-0.5 text-xs bg-' + color + '-100 text-' + color + '-700">' +
            status.charAt(0).toUpperCase() + status.slice(1) + '</span>'
        );
    }

    // ── Bank account verify ───────────────────────────────────────────────────
    $(document).on('click', '[data-verify-bank]', function () {
        const accountId = $(this).data('verify-bank');
        const vendorId = $(this).data('vendor-id');
        if (!confirm('Verify this bank account?')) return;

        withLoading(this,
            $.post('/vendors/' + vendorId + '/bank-accounts/' + accountId + '/verify', { _token: csrfToken() })
                .done(function (res) { Toast.success(res.message); setTimeout(function () { location.reload(); }, 600); })
                .fail(function () { Toast.error('Verification failed.'); })
        );
    });

    // ── Send Notification form ────────────────────────────────────────────────
    $('#send-notification-form').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $(this).data('vendor-id');

        withLoading('#send-notification-form [type=submit]',
            $.post('/vendors/' + vendorId + '/notify', $(this).serialize())
                .done(function (res) { closeModal('send-notification-modal'); Toast.success(res.message); })
                .fail(function (xhr) { Toast.error(xhr.responseJSON?.message ?? 'Failed to send.'); })
        );
    });

    // ══════════════════════════════════════════════════════════════════════════
    // PERFORMANCE CHARTS (lazy Chart.js)
    // ══════════════════════════════════════════════════════════════════════════

    if (typeof window.VENDOR_ID !== 'undefined' && document.getElementById('performance-tab')) {
        import('chart.js/auto').then(function ({ Chart }) {
            $.get('/vendors/' + window.VENDOR_ID + '/performance-data', function (res) {
                const d = res.data;

                // GMV line chart
                const gmvCtx = document.getElementById('gmv-chart');
                if (gmvCtx) {
                    new Chart(gmvCtx, {
                        type: 'line',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                label: 'GMV ($)',
                                data: d.gmv,
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2,132,199,0.08)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 2,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                            },
                        },
                    });
                }

                // Orders by status bar chart
                const oCtx = document.getElementById('orders-status-chart');
                if (oCtx && d.orders_by_status) {
                    const statusColors = {
                        completed: '#16a34a', delivered: '#22c55e',
                        shipped: '#0ea5e9', processing: '#6366f1',
                        cancelled: '#ef4444', returned: '#f59e0b',
                        placed: '#9ca3af',
                    };
                    const labels = Object.keys(d.orders_by_status);
                    new Chart(oCtx, {
                        type: 'bar',
                        data: {
                            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                            datasets: [{
                                label: 'Orders',
                                data: labels.map(l => d.orders_by_status[l]),
                                backgroundColor: labels.map(l => statusColors[l] ?? '#6b7280'),
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                                x: { grid: { display: false } },
                            },
                        },
                    });
                }

                // Platform comparison
                if (d.platform_avg) {
                    $('#avg-gmv').text('$' + Number(d.platform_avg.gmv).toLocaleString(undefined, { maximumFractionDigits: 0 }));
                    $('#avg-orders').text(Number(d.platform_avg.orders).toFixed(0));
                    $('#avg-rating').text(Number(d.platform_avg.rating).toFixed(1));
                }
            });
        });
    }
});

// ─── DataTable cell renderers ─────────────────────────────────────────────────

function renderStore(data) {
    if (!data) return '—';
    const avatar = data.avatar
        ? `<img src="${data.avatar}" class="w-8 h-8 rounded-lg object-cover border border-gray-200 shrink-0">`
        : `<div class="w-8 h-8 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold shrink-0">${data.store_name.charAt(0).toUpperCase()}</div>`;

    const badges = [];
    if (data.payout_hold) badges.push('<span class="text-warning-700 text-xs">⚠ Hold</span>');
    if (data.strikes > 0) badges.push(`<span class="text-danger-600 text-xs">⚡ ${data.strikes}</span>`);

    return `<div class="flex items-center gap-2 min-w-0">
        ${avatar}
        <div class="min-w-0">
            <a href="/vendors/${data.id}" class="font-medium text-gray-900 hover:text-primary-600 truncate block">${escHtml(data.store_name)}</a>
            ${badges.length ? `<div class="flex gap-1 mt-0.5">${badges.join('')}</div>` : ''}
        </div>
    </div>`;
}

function renderStatus(status) {
    const map = {
        active: 'success', pending: 'gray', suspended: 'warning',
        rejected: 'danger', blacklisted: 'danger', under_review: 'primary',
    };
    const color = map[status] ?? 'gray';
    const label = status ? status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ') : '—';
    return `<span class="inline-flex items-center rounded-full font-medium px-2 py-0.5 text-xs bg-${color}-100 text-${color}-700">${label}</span>`;
}

function renderActions(data) {
    if (!data) return '';
    const { id, store_name, global_status } = data;
    const name = escHtml(store_name ?? '');
    return `<div class="flex items-center justify-end gap-2 text-xs">
        <a href="/vendors/${id}" class="text-primary-600 hover:underline">View</a>
        <button type="button"
            class="vendor-dots-btn p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
            data-id="${id}" data-name="${name}" data-status="${global_status}"
            title="More actions">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
            </svg>
        </button>
    </div>`;
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}
