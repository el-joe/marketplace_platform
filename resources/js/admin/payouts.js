const Toast = window.Toast || { success: console.log, error: console.warn, info: console.log };
const T = window.TRANSLATIONS || {};

const URLS = {
    approve: () => `/payouts/${window.PAYOUT_ID}/approve`,
    process: () => `/payouts/${window.PAYOUT_ID}/process`,
    hold: () => `/payouts/${window.PAYOUT_ID}/hold`,
    recalculate: () => `/payouts/${window.PAYOUT_ID}/recalculate`,
};

/**
 * Post a JSON payload to the given URL, then reload on success.
 */
function submitPayoutAction(url, data) {
    return $.ajax({
        url,
        method: 'POST',
        data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.dispatchEvent(new Event('close'));
    }
}

$(function () {

    // ── Approve ──────────────────────────────────────────────────────────────
    $('#approve-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text(T.payoutsApproving ?? 'Approving…');

        submitPayoutAction(URLS.approve(), $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? T.payoutsApproved ?? 'Payout approved.');
                closeModal('approve-modal');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message ?? T.payoutsFailedApprove ?? 'Failed to approve payout.';
                Toast.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).text(T.payoutsApprove ?? 'Approve');
            });
    });

    // ── Process / Complete ───────────────────────────────────────────────────
    $('#process-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text(T.payoutsProcessing ?? 'Processing…');

        submitPayoutAction(URLS.process(), $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? T.payoutsCompleted ?? 'Payout marked as completed.');
                closeModal('process-modal');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message ?? T.payoutsFailedProcess ?? 'Failed to process payout.';
                Toast.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).text(T.payoutsMarkCompleted ?? 'Mark Completed');
            });
    });

    // ── Hold ─────────────────────────────────────────────────────────────────
    $('#hold-form').on('submit', function (e) {
        e.preventDefault();
        const reason = $(this).find('[name=reason]').val().trim();
        if (!reason) {
            Toast.warning(T.payoutsPleaseProvideHoldReason ?? 'Please provide a reason for holding this payout.');
            return;
        }
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text(T.payoutsSaving ?? 'Saving…');

        submitPayoutAction(URLS.hold(), $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? T.payoutsOnHold ?? 'Payout placed on hold.');
                closeModal('hold-modal');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message ?? T.payoutsFailedHold ?? 'Failed to hold payout.';
                Toast.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).text(T.payoutsPutOnHold ?? 'Put on Hold');
            });
    });

    // ── Recalculate ──────────────────────────────────────────────────────────
    $('#recalculate-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text(T.payoutsCalculating ?? 'Calculating…');

        submitPayoutAction(URLS.recalculate(), $(this).serialize())
            .done(function (res) {
                Toast.success(res.message ?? T.payoutsRecalculated ?? 'Payout recalculated.');
                closeModal('recalculate-modal');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message ?? T.payoutsRecalculationFailed ?? 'Recalculation failed.';
                Toast.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).text(T.payoutsRecalculate ?? 'Recalculate');
            });
    });

});
