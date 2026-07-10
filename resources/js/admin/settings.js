/**
 * Admin Settings JS
 * Uses jQuery + window.Toast + window.confirmDialog (SweetAlert2-backed)
 */

// ─── withLoading helper ───────────────────────────────────────────────────────
// Disables a button while a jQuery XHR runs; re-enables on completion.
function withLoading($btn, jqXhr) {
    const origText = $btn.html();
    $btn.prop('disabled', true).html(
        '<svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
        '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
        '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>' +
        (window.TRANSLATIONS?.saving || 'Saving…')
    );
    jqXhr.always(function () {
        $btn.prop('disabled', false).html(origText);
    });
    return jqXhr;
}

// ─── Cents display helper ─────────────────────────────────────────────────────
function updateCentsDisplay($input) {
    const val = parseFloat($input.val()) || 0;
    const formatted = (val / 100).toFixed(2);
    $input.closest('.flex').find('.js-cents-display').text(formatted);
}

// ─── Main settings form ───────────────────────────────────────────────────────
$(function () {

    // ── Form submit ──────────────────────────────────────────────────────────
    $('#settings-form').on('submit', function (e) {
        e.preventDefault();

        const $form     = $(this);
        const category  = $form.data('category');
        const $btn      = $('#btn-save-settings');
        const settings  = {};

        // Collect all settings[key] fields (including hidden inputs from Alpine toggles)
        $form.find('[name^="settings["]').each(function () {
            const match = this.name.match(/^settings\[(.+)\]$/);
            if (match) {
                // Use last value for duplicate keys (Alpine toggle sends hidden input last)
                settings[match[1]] = $(this).val();
            }
        });

        const jqXhr = $.ajax({
            url:         '/settings/group/' + category,
            method:      'POST',
            contentType: 'application/json',
            headers:     { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:        JSON.stringify({ settings }),
        });

        withLoading($btn, jqXhr);

        jqXhr.done(function (res) {
            window.Toast.success(res.message || window.TRANSLATIONS?.settingsSaved || 'Settings saved successfully.');
        });

        jqXhr.fail(function (xhr) {
            const data = xhr.responseJSON || {};
            if (data.errors) {
                const msgs = Object.values(data.errors).flat().join('\n');
                window.Toast.error(msgs || window.TRANSLATIONS?.validationFailed || 'Validation failed.');
            } else {
                window.Toast.error(data.message || window.TRANSLATIONS?.failedToSaveSettings || 'Failed to save settings.');
            }
        });
    });

    // ── Clear cache button ───────────────────────────────────────────────────
    $('#btn-clear-cache').on('click', function () {
        const $btn = $(this);

        window.confirmDialog({
            title:   window.TRANSLATIONS?.clearCacheTitle || 'Clear Application Cache?',
            message: window.TRANSLATIONS?.clearCacheMessage || 'This will clear the config, view, and application cache. The next request may be slower.',
            confirmText: window.TRANSLATIONS?.clearCacheConfirm || 'Clear Cache',
            onConfirm: function () {
                const jqXhr = $.ajax({
                    url:     '/settings/clear-cache',
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                });

                withLoading($btn, jqXhr);

                jqXhr.done(function (res) {
                    window.Toast.success(res.message || window.TRANSLATIONS?.cacheCleared || 'Cache cleared.');
                });
                jqXhr.fail(function (xhr) {
                    window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.failedToClearCache || 'Failed to clear cache.');
                });
            },
        });
    });

    // ── Test payment gateway ─────────────────────────────────────────────────
    $('#btn-test-gateway').on('click', function () {
        const $btn = $(this);

        const jqXhr = $.ajax({
            url:     '/settings/test-gateway',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        });

        withLoading($btn, jqXhr);

        jqXhr.done(function (res) {
            window.Toast.success(res.message || window.TRANSLATIONS?.gatewayReachable || 'Gateway reachable.');
        });
        jqXhr.fail(function (xhr) {
            window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.gatewayTestFailed || 'Gateway test failed.');
        });
    });

    // ── Currency: save individual rate ───────────────────────────────────────
    $(document).on('click', '.btn-save-rate', function () {
        const $btn  = $(this);
        const code  = $btn.data('currency-code');
        const $row  = $btn.closest('tr');
        const rate  = $row.find('.rate-input[data-currency-code="' + code + '"]').val();

        if (!rate || isNaN(parseFloat(rate)) || parseFloat(rate) <= 0) {
            window.Toast.error(window.TRANSLATIONS?.enterValidRate || 'Please enter a valid positive rate.');
            return;
        }

        const jqXhr = $.ajax({
            url:         '/currencies/' + code + '/rate',
            method:      'PATCH',
            contentType: 'application/json',
            headers:     { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data:        JSON.stringify({ rate: parseFloat(rate) }),
        });

        withLoading($btn, jqXhr);

        jqXhr.done(function (res) {
            window.Toast.success(res.message || code + ' ' + (window.TRANSLATIONS?.rateUpdated || 'rate updated.'));
        });
        jqXhr.fail(function (xhr) {
            window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.failedToUpdateRate || 'Failed to update rate.');
        });
    });

    // ── Currency: refresh all rates from API ─────────────────────────────────
    $('#btn-refresh-rates').on('click', function () {
        const $btn = $(this);

        const jqXhr = $.ajax({
            url:     '/currencies/refresh-rates',
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        });

        withLoading($btn, jqXhr);

        jqXhr.done(function (res) {
            window.Toast.success(res.message || window.TRANSLATIONS?.ratesRefreshQueued || 'Exchange rates refresh queued.');
            // Reload the table
            $.get('/currencies/rates-table', function (html) {
                $('#rates-table-body').html(html);
            });
        });
        jqXhr.fail(function (xhr) {
            window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.failedToQueueRefresh || 'Failed to queue refresh.');
        });
    });

    // ── Announcement bar live preview ────────────────────────────────────────
    $(document).on('input', '[name="settings[announcement_bar_text_en]"]', function () {
        $('#announcement-preview').text($(this).val());
    });

    $(document).on('input', '[name="settings[announcement_bar_color]"]', function () {
        $('#announcement-preview').css('background-color', $(this).val());
    });

    // ── Cents input: live display ────────────────────────────────────────────
    $(document).on('input', '.js-cents-display', function () {
        updateCentsDisplay($(this).closest('.flex').find('input[type="number"]'));
    });

    $('input[type="number"]').filter(function () {
        return $(this).closest('.flex').find('.js-cents-display').length > 0;
    }).on('input', function () {
        updateCentsDisplay($(this));
    });

});
