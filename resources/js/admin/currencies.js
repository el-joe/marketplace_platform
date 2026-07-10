/**
 * resources/js/admin/currencies.js
 *
 * Handles:
 *  - Manual rate sync dispatch button
 */

$(function () {
    $(document).on('click', '#btn-dispatch-rate-update', function () {
        const url = $(this).data('url');
        const $btn = $(this);

        if (!confirm(window.TRANSLATIONS?.queueSyncConfirm || 'Queue an exchange rate sync from the API now?')) return;

        $btn.prop('disabled', true).text(window.TRANSLATIONS?.queuing || 'Queuing…');

        $.ajax({
            url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.dispatchFailed || 'Dispatch failed.');
            })
            .always(function () {
                $btn.prop('disabled', false).html(
                    '<svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582M20 20v-5h-.581M5.636 15.364A9 9 0 1118.364 8.636"/>' +
                    '</svg>' + (window.TRANSLATIONS?.syncExchangeRates || 'Sync Exchange Rates')
                );
            });
    });
});
