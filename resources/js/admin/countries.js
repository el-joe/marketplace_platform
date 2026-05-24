/**
 * resources/js/admin/countries.js
 *
 * Handles:
 *  - Launch / Deactivate / Reactivate country actions
 *  - Delete country confirmation
 *  - Payment method add/edit modal via AJAX
 *  - Delete payment method
 *  - Shipping settings AJAX save
 *  - Category override modal
 */

$(function () {
    // ─────────────────────────────────────────────────────────────────────────
    // Launch / Deactivate / Reactivate
    // ─────────────────────────────────────────────────────────────────────────

    $(document).on('click', '#btn-launch', function () {
        const name = $(this).data('country-name');
        const url = $(this).data('url');
        if (!confirm(`Launch ${name}?\n\nThis will make all active products available in this country. This action runs in the background.`)) return;

        $(this).prop('disabled', true).text('Launching…');

        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => location.reload(), 1000);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Launch failed.');
                $('#btn-launch').prop('disabled', false).html('<svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Launch');
            });
    });

    $(document).on('click', '#btn-deactivate', function () {
        const url = $(this).data('url');
        if (!confirm('Deactivate this country? Customers will no longer be able to place orders.')) return;

        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => location.reload(), 800);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Deactivate failed.');
            });
    });

    $(document).on('click', '#btn-reactivate', function () {
        const url = $(this).data('url');
        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => location.reload(), 800);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Reactivate failed.');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Delete country
    // ─────────────────────────────────────────────────────────────────────────

    $(document).on('click', '#btn-delete-country', function () {
        const name = $(this).data('country-name');
        const url = $(this).data('url');
        if (!confirm(`Permanently delete "${name}"?\n\nThis cannot be undone.`)) return;

        $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => window.location.href = '/countries/', 900);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Payment Method Modal
    // ─────────────────────────────────────────────────────────────────────────

    let pmStoreUrl = null;
    let pmUpdateBase = null;

    // Infer URLs from page meta if available
    const countryId = $('[data-country-id]').first().data('country-id');

    function openPmModal(pm = null) {
        const $form = $('#pm-form');
        $form[0].reset();
        $('#pm-id').val('');

        if (pm) {
            $('#pm-id').val(pm.id);
            $('#pm-method-type').val(pm.method_type);
            $('#pm-provider').val(pm.provider || '');
            $('#pm-display-en').val(pm.display_name_en);
            $('#pm-display-ar').val(pm.display_name_ar || '');
            $('#pm-fee-pct').val(pm.fee_pct);
            $('#pm-fee-fixed').val(pm.fee_fixed_cents);
            $('#pm-sort-order').val(pm.sort_order);
            $('#pm-min-order').val(pm.min_order_cents);
            $('#pm-max-order').val(pm.max_order_cents || '');
            $('#pm-is-active').prop('checked', !!pm.is_active);
        }

        openModal('pm-modal');
    }

    $(document).on('click', '#btn-add-payment-method', function () {
        openPmModal(null);
    });

    $(document).on('click', '.btn-edit-pm', function () {
        const pm = $(this).data('pm');
        openPmModal(pm);
    });

    $('#pm-form').on('submit', function (e) {
        e.preventDefault();

        const pmId = $('#pm-id').val();
        const data = $(this).serializeArray().reduce((acc, { name, value }) => { acc[name] = value; return acc; }, {});
        data.is_active = $('#pm-is-active').is(':checked') ? 1 : 0;

        // Determine URL: POST for create, PUT for update
        const baseUrl = window.countryPaymentMethodsUrl || `/countries/${countryId}/payment-methods`;
        const url = pmId ? `${baseUrl}/${pmId}` : baseUrl;
        const method = pmId ? 'PUT' : 'POST';

        $('#pm-submit-btn').prop('disabled', true).text('Saving…');

        $.ajax({
            url,
            method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                closeModal('pm-modal');
                location.reload();
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors || {}).flat().join(' | ')
                    : 'Save failed.';
                window.Toast && window.Toast.error(msg);
            })
            .always(function () {
                $('#pm-submit-btn').prop('disabled', false).text('Save');
            });
    });

    $(document).on('click', '.btn-delete-pm', function () {
        const pmId = $(this).data('pm-id');
        const url = $(this).data('url');
        if (!confirm('Remove this payment method?')) return;

        $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                $(`[data-pm-id="${pmId}"]`).remove();
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Shipping Settings Save
    // ─────────────────────────────────────────────────────────────────────────

    $(document).on('click', '#btn-save-shipping', function () {
        const url = $(this).data('url');
        const $btn = $(this);

        const formData = {};
        $('#shipping-settings-form').find('[name]').each(function () {
            const name = $(this).attr('name');
            const val = $(this).is('[type=checkbox]') ? (this.checked ? 1 : 0) : $(this).val();
            // Parse settings[0][field] structure
            const match = name.match(/settings\[(\d+)]\[(.+)]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!formData[idx]) formData[idx] = {};
                formData[idx][field] = val;
            }
        });

        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url,
            method: 'POST',
            data: JSON.stringify({ settings: Object.values(formData) }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Save failed.');
            })
            .always(function () {
                $btn.prop('disabled', false).html('<svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Save Shipping Settings');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Category Override Modal
    // ─────────────────────────────────────────────────────────────────────────

    window.tableActions = window.tableActions || {};

    window.tableActions.editCatOverride = function (id, row) {
        $('#cat-category-id').val(row.id);
        $('#cat-name-display').text(row.name_en + ' / ' + row.name_ar);
        $('#cat-is-available').prop('checked', row.is_available);
        $('#cat-commission-rate').val(row.override_commission_rate || '');
        $('#cat-unavailable-reason').val(row.unavailable_reason || '');
        $('#cat-notes').val(row.notes || '');
        openModal('cat-override-modal');
    };

    $('#cat-override-form').on('submit', function (e) {
        e.preventDefault();
        const url = $('#cat-override-submit').data('url');
        const data = {
            overrides: [{
                category_id: $('#cat-category-id').val(),
                is_available: $('#cat-is-available').is(':checked') ? 1 : 0,
                commission_rate: $('#cat-commission-rate').val() || null,
                unavailable_reason: $('#cat-unavailable-reason').val() || null,
                notes: $('#cat-notes').val() || null,
            }],
        };

        $('#cat-override-submit').prop('disabled', true).text('Saving…');

        $.ajax({
            url,
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                closeModal('cat-override-modal');
                window.reloadDataTable && window.reloadDataTable('categories-override-table');
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Save failed.');
            })
            .always(function () {
                $('#cat-override-submit').prop('disabled', false).text('Save');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers — thin wrappers around whatever modal system is in use
    // ─────────────────────────────────────────────────────────────────────────

    function openModal(id) { window.openModal ? window.openModal(id) : $(`#${id}`).removeClass('hidden').show(); }
    function closeModal(id) { window.closeModal ? window.closeModal(id) : $(`#${id}`).addClass('hidden').hide(); }
});
