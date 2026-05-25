const Toast = window.Toast || { success: console.log, error: console.warn, info: console.log };

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

function ajaxPost(url, data) {
    return $.ajax({
        url,
        method: 'POST',
        data,
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.dispatchEvent(new Event('close'));
}

// ─────────────────────────────────────────────────────────────────────────────
// Create form (create.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    const $createForm = $('#create-flash-sale-form');
    if (!$createForm.length) return;

    $createForm.on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Creating…');

        $.ajax({
            url: window.location.pathname.replace(/\/$/, '') + '/store',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        })
            .done(function (res) {
                Toast.success(res.message ?? 'Flash sale created.');
                if (res.redirect) {
                    setTimeout(() => { window.location.href = res.redirect; }, 500);
                }
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Failed to create flash sale.';
                const errors = xhr.responseJSON?.errors ?? {};
                Object.values(errors).flat().forEach(e => Toast.error(e));
                if (!Object.keys(errors).length) Toast.error(msg);
            })
            .always(() => $btn.prop('disabled', false).text('Create Flash Sale'));
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Show page (show.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    if (typeof window.FLASH_SALE_ID === 'undefined') return;

    const URLS = window.URLS || {};

    // ── Status transitions ────────────────────────────────────────────────────
    $(document).on('click', '.flash-sale-transition', function () {
        const action = $(this).data('action');
        const confirm = $(this).data('confirm');
        if (confirm && !window.confirm(confirm)) return;

        const $btn = $(this).prop('disabled', true);

        ajaxPost(URLS.transition, { action })
            .done(function (res) {
                Toast.success(res.message ?? 'Status updated.');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                Toast.error(xhr.responseJSON?.message ?? 'Transition failed.');
                $btn.prop('disabled', false);
            });
    });

    // ── Auto-invite ───────────────────────────────────────────────────────────
    $('#btn-auto-invite').on('click', function () {
        if (!window.confirm('Auto-invite all eligible vendors based on flash sale rules?')) return;

        const $btn = $(this).prop('disabled', true).text('Inviting…');

        ajaxPost(URLS.inviteVendors, { mode: 'auto' })
            .done(function (res) {
                Toast.success(res.message ?? `${res.count} vendor(s) invited.`);
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                Toast.error(xhr.responseJSON?.message ?? 'Invitation failed.');
                $btn.prop('disabled', false).text('Auto-Invite Eligible');
            });
    });

    // ── Manual invite ─────────────────────────────────────────────────────────
    $('#manual-invite-form').on('submit', function (e) {
        e.preventDefault();
        const raw = $(this).find('[name=vendor_ids_raw]').val().trim();
        const vendorIds = raw.split('\n').map(s => s.trim()).filter(Boolean);

        if (!vendorIds.length) {
            Toast.warning('Please enter at least one vendor ID.');
            return;
        }

        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Sending…');
        const data = { mode: 'manual' };
        vendorIds.forEach((id, i) => { data[`vendor_ids[${i}]`] = id; });

        ajaxPost(URLS.inviteVendors, data)
            .done(function (res) {
                Toast.success(res.message ?? 'Invitations sent.');
                closeModal('manual-invite-modal');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                Toast.error(xhr.responseJSON?.message ?? 'Invitation failed.');
                $btn.prop('disabled', false).text('Send Invitations');
            });
    });

    // ── Approve submission ────────────────────────────────────────────────────
    $('#approve-form').on('submit', function (e) {
        e.preventDefault();
        const submissionId = $('#approve-submission-id').val();
        const url = `/admin/flash-sales/submissions/${submissionId}/approve`;
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Approving…');

        ajaxPost(url, $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? 'Submission approved.');
                closeModal('approve-modal');
                // Reload the submissions DataTable
                if (window._submissionsTable) window._submissionsTable.ajax.reload();
            })
            .fail(function (xhr) {
                const data = xhr.responseJSON ?? {};
                if (data.fraud_check && data.requires_override) {
                    // Show fraud warning inline
                    const $warn = $('#fraud-warning');
                    const $list = $('#fraud-reasons');
                    $list.empty();
                    (data.fraud_check.reasons || []).forEach(r => {
                        $list.append(`<li>${r}</li>`);
                    });
                    $warn.removeClass('hidden');
                } else {
                    Toast.error(data.message ?? 'Approval failed.');
                }
            })
            .always(() => $btn.prop('disabled', false).text('Approve'));
    });

    // ── Reject submission ─────────────────────────────────────────────────────
    $('#reject-form').on('submit', function (e) {
        e.preventDefault();
        const submissionId = $('#reject-submission-id').val();
        const url = `/admin/flash-sales/submissions/${submissionId}/reject`;
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Rejecting…');

        ajaxPost(url, $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? 'Submission rejected.');
                closeModal('reject-modal');
                if (window._submissionsTable) window._submissionsTable.ajax.reload();
            })
            .fail(function (xhr) {
                Toast.error(xhr.responseJSON?.message ?? 'Rejection failed.');
                $btn.prop('disabled', false).text('Reject');
            });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// DataTable row action callbacks (called from column render functions)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Called from the submissions DataTable row action renderer.
 */
window.approveSubmission = function (submissionId) {
    $('#approve-submission-id').val(submissionId);
    $('#fraud-warning').addClass('hidden');
    $('[name=override_fraud_check]').prop('checked', false);
    $('[name=notes]').val('');
    document.getElementById('approve-modal').dispatchEvent(new Event('open'));
};

window.openRejectModal = function (submissionId) {
    $('#reject-submission-id').val(submissionId);
    $('[name=reason]').val('');
    document.getElementById('reject-modal').dispatchEvent(new Event('open'));
};

/**
 * Inline renderer for the submissions DataTable actions column.
 */
window.renderSubmissionActions = function (data, type, row) {
    if (!['submitted', 'under_review', 'approved'].includes(row.status)) return '';

    return `
        <button class="btn btn-success btn-xs mr-1"
            onclick="approveSubmission('${row.id}')">Approve</button>
        <button class="btn btn-danger btn-xs"
            onclick="openRejectModal('${row.id}')">Reject</button>
    `;
};
