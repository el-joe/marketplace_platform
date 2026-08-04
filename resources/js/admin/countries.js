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
        const message = t('admin.countries.launch_country_confirm', { name });
        if (!confirm(message)) return;

        $(this).prop('disabled', true).text(t('admin.countries.launching_label'));

        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => location.reload(), 1000);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.launch_failed'));
                $('#btn-launch').prop('disabled', false).html('<svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>' + (t('admin.countries.launch_label')));
            });
    });

    $(document).on('click', '#btn-deactivate', function () {
        const url = $(this).data('url');
        if (!confirm(t('admin.countries.deactivate_country_confirm'))) return;

        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => location.reload(), 800);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.deactivate_failed'));
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
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.reactivate_failed'));
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Delete country
    // ─────────────────────────────────────────────────────────────────────────

    $(document).on('click', '#btn-delete-country', async function () {
        const name = $(this).data('country-name');
        const url = $(this).data('url');
        const message = t('admin.countries.delete_country_confirm', { name });
        const confirmed = window.confirmDelete
            ? await window.confirmDelete(message, { title: t('admin.countries.delete_country_title') })
            : confirm(message);
        if (!confirmed) return;

        $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => window.location.href = '/countries/', 900);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.delete_failed'));
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
            $form.find('[name="method_type"]').val(pm.method_type);
            $form.find('[name="provider"]').val(pm.provider || '');
            $form.find('[name="display_name_en"]').val(pm.display_name_en);
            $form.find('[name="display_name_ar"]').val(pm.display_name_ar || '');
            $form.find('[name="fee_pct"]').val(pm.fee_pct);
            $form.find('[name="fee_fixed"]').val(pm.fee_fixed);
            $form.find('[name="sort_order"]').val(pm.sort_order);
            $form.find('[name="min_order"]').val(pm.min_order);
            $form.find('[name="max_order"]').val(pm.max_order || '');
            $form.find('[name="is_active"][type="checkbox"]').prop('checked', !!pm.is_active).trigger('change');
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

        // Determine URL: POST for create, PUT for update
        const baseUrl = window.countryPaymentMethodsUrl || `/countries/${countryId}/payment-methods`;
        const url = pmId ? `${baseUrl}/${pmId}` : baseUrl;
        const method = pmId ? 'PUT' : 'POST';

        $('#pm-submit-btn').prop('disabled', true).text(t('shared.saving'));

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
                    : (t('admin.countries.save_failed'));
                window.Toast && window.Toast.error(msg);
            })
            .always(function () {
                $('#pm-submit-btn').prop('disabled', false).text(t('admin.countries.save_btn_label'));
            });
    });

    $(document).on('click', '.btn-delete-pm', async function () {
        const pmId = $(this).data('pm-id');
        const url = $(this).data('url');
        const confirmed = window.confirmDelete
            ? await window.confirmDelete(t('admin.countries.remove_payment_method_confirm'), { title: t('admin.countries.delete_payment_method_title') })
            : confirm(t('admin.countries.remove_payment_method_confirm'));
        if (!confirmed) return;

        $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                $(`[data-pm-id="${pmId}"]`).remove();
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.delete_failed'));
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

        $btn.prop('disabled', true).text(t('shared.saving'));

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
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.save_failed'));
            })
            .always(function () {
                $btn.prop('disabled', false).html('<svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' + (t('admin.countries.save_shipping_settings_label')));
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Category Override Modal
    // ─────────────────────────────────────────────────────────────────────────

    window.tableActions = window.tableActions || {};

    window.tableActions.editCatOverride = function (id, row) {
        const $form = $('#cat-override-form');
        $('#cat-category-id').val(row.id);
        $('#cat-name-display').text(row.name_en + ' / ' + row.name_ar);
        $form.find('[name="overrides[0][is_available]"][type="checkbox"]').prop('checked', !!row.is_available).trigger('change');
        $form.find('[name="overrides[0][commission_rate]"]').val(row.override_commission_rate ?? '');
        $form.find('[name="overrides[0][unavailable_reason]"]').val(row.unavailable_reason ?? '');
        $form.find('[name="overrides[0][notes]"]').val(row.notes ?? '');
        openModal('cat-override-modal');
    };

    $('#cat-override-form').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $('#cat-override-submit').data('url');
        const data = {
            overrides: [{
                category_id: $('#cat-category-id').val(),
                is_available: $form.find('[name="overrides[0][is_available]"][type="checkbox"]').is(':checked') ? 1 : 0,
                commission_rate: $form.find('[name="overrides[0][commission_rate]"]').val() || null,
                unavailable_reason: $form.find('[name="overrides[0][unavailable_reason]"]').val() || null,
                notes: $form.find('[name="overrides[0][notes]"]').val() || null,
            }],
        };

        $('#cat-override-submit').prop('disabled', true).text(t('shared.saving'));

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
                window.Toast && window.Toast.error(xhr.responseJSON?.message || t('admin.countries.save_failed'));
            })
            .always(function () {
                $('#cat-override-submit').prop('disabled', false).text(t('admin.countries.save_btn_label'));
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers — thin wrappers around whatever modal system is in use
    // ─────────────────────────────────────────────────────────────────────────

    function openModal(id) { $(`#${id}`).modal('open'); }
    function closeModal(id) { $(`#${id}`).modal('close'); }
});
