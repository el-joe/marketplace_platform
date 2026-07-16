import $ from 'jquery';

// ─── Utilities ────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function sendJson(url, method, data = {}) {
    const res = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(data),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

function toast(message, type = 'success') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}

// ─── Cents helpers ────────────────────────────────────────────────────────────

/** Parse a decimal string to integer cents (0.00 → 0, 1.50 → 150). */
function toCents(value) {
    const parsed = parseFloat(value);
    if (isNaN(parsed) || parsed < 0) return 0;
    return Math.round(parsed * 100);
}

/** Sync a display input value to a hidden cents field. */
function syncCentsField(displayId, hiddenId) {
    const displayEl = document.getElementById(displayId);
    const hiddenEl = document.getElementById(hiddenId);
    if (!displayEl || !hiddenEl) return;
    displayEl.addEventListener('input', () => {
        hiddenEl.value = toCents(displayEl.value);
    });
}

// ─── Gateway Testing ──────────────────────────────────────────────────────────

function initGatewayTest() {
    $(document).on('click', '.btn-test-gateway', async function () {
        const $btn = $(this);
        const provider = $btn.data('provider');
        const $status = $(`#gateway-status-${provider}, #test-result-${provider}`);

        $btn.prop('disabled', true).text(window.TRANSLATIONS?.testing || 'Testing…');

        try {
            const res = await sendJson('/payment-methods/test-gateway', 'POST', { provider });
            const data = res.data ?? {};

            $status
                .removeClass('hidden border-danger-200 bg-danger-50 text-danger-700 border-success-200 bg-success-50 text-success-700')
                .addClass(data.success
                    ? 'border-success-200 bg-success-50 text-success-700'
                    : 'border-danger-200 bg-danger-50 text-danger-700')
                .removeClass('hidden')
                .html(data.success
                    ? `✓ ${window.TRANSLATIONS?.connected || 'Connected'} (${data.latency_ms ?? 0}ms)`
                    : `✗ ${data.message ?? (window.TRANSLATIONS?.failed || 'Failed')}`);

            // Simple gateway status badge (index page)
            $(`#gateway-status-${provider}`).text(
                data.success ? `✓ ${data.latency_ms ?? 0}ms` : `✗ ${window.TRANSLATIONS?.failed || 'Failed'}`
            );
        } catch (err) {
            $status
                .removeClass('hidden border-success-200 bg-success-50 text-success-700')
                .addClass('border-danger-200 bg-danger-50 text-danger-700')
                .removeClass('hidden')
                .text(window.TRANSLATIONS?.requestFailed || '✗ Request failed');
        } finally {
            $btn.prop('disabled', false).text(window.TRANSLATIONS?.test || 'Test');
        }
    });
}

// ─── Toggle Active ────────────────────────────────────────────────────────────

function initToggleMethod() {
    $(document).on('click', '.btn-toggle-method', async function () {
        const $btn = $(this);
        const id = $btn.data('id');

        try {
            const res = await sendJson(`/payment-methods/${id}/toggle`, 'POST');
            const active = res.is_active;

            $btn
                .data('active', active ? '1' : '0')
                .text(active ? (window.TRANSLATIONS?.active || 'Active') : (window.TRANSLATIONS?.inactive || 'Inactive'))
                .toggleClass('bg-success-50 text-success-700 hover:bg-success-100', active)
                .toggleClass('bg-gray-100 text-gray-500 hover:bg-gray-200', !active);
        } catch {
            toast(window.TRANSLATIONS?.failedToUpdateStatus || 'Failed to update status.', 'error');
        }
    });
}

// ─── Add / Edit Modal ─────────────────────────────────────────────────────────

function initMethodModal() {
    const $modal = $('#method-modal');
    const $form = $('#method-form');

    // Open in "add" mode from country button
    $(document).on('click', '.btn-add-country-method', function () {
        const countryId = $(this).data('country-id');
        const countryName = $(this).data('country-name');

        $form[0].reset();
        $('#method-id').val('');
        $('#method-http').val('POST');
        $('#method-country-id').val(countryId);
        $modal.find('[id$="-title"]').text(`${window.TRANSLATIONS?.addPaymentMethod || 'Add Payment Method'} \u2014 ${countryName}`);
        $modal.modal('open');
    });

    // Header "Add Method" button (pick country via form)
    $('#btn-add-method').on('click', function () {
        $form[0].reset();
        $('#method-id').val('');
        $('#method-http').val('POST');
        $('#method-country-id').val('');
        $modal.find('[id$="-title"]').text(window.TRANSLATIONS?.addPaymentMethod || 'Add Payment Method');
        $modal.modal('open');
    });

    // Open in "edit" mode
    $(document).on('click', '.btn-edit-method', function () {
        const row = $(this).data('row');

        $form[0].reset();
        $('#method-id').val(row.id);
        $('#method-http').val('PUT');
        $('#method-country-id').val(row.country_id);

        $form.find('[name="method_type"]').val(row.method_type);
        $form.find('[name="provider"]').val(row.provider ?? '');
        $form.find('[name="display_name_en"]').val(row.display_name_en);
        $form.find('[name="display_name_ar"]').val(row.display_name_ar ?? '');
        $form.find('[name="fee_pct"]').val(row.fee_pct ?? '');

        // Cents → display
        const feeFixed = row.fee_fixed ? (row.fee_fixed / 100).toFixed(2) : '';
        const minOrder = row.min_order ? (row.min_order / 100).toFixed(2) : '';
        const maxOrder = row.max_order ? (row.max_order / 100).toFixed(2) : '';

        $('#fee_fixed_display').val(feeFixed);
        $('#fee_fixed').val(row.fee_fixed ?? '');
        $('#min_order_display').val(minOrder);
        $('#min_order').val(row.min_order ?? '');
        $('#max_order_display').val(maxOrder);
        $('#max_order').val(row.max_order ?? '');

        // Toggle (Alpine)
        const $toggle = $form.find('[name="is_active"]');
        $toggle.prop('checked', !!row.is_active).trigger('change');

        $modal.find('[id$="-title"]').text(window.TRANSLATIONS?.editPaymentMethod || 'Edit Payment Method');
        $modal.modal('open');
    });

    // Form submit
    $form.on('submit', async function (e) {
        e.preventDefault();

        const id = $('#method-id').val();
        const method = $('#method-http').val();
        const url = id ? `/payment-methods/${id}` : '/payment-methods';

        // Build payload
        const payload = {
            country_id: $('#method-country-id').val(),
            method_type: $form.find('[name="method_type"]').val(),
            provider: $form.find('[name="provider"]').val() || null,
            display_name_en: $form.find('[name="display_name_en"]').val(),
            display_name_ar: $form.find('[name="display_name_ar"]').val() || null,
            fee_pct: $form.find('[name="fee_pct"]').val() || null,
            fee_fixed: parseInt($('#fee_fixed').val()) || 0,
            min_order: parseInt($('#min_order').val()) || null,
            max_order: parseInt($('#max_order').val()) || null,
            is_active: $form.find('[name="is_active"]').is(':checked') ? 1 : 0,
        };

        try {
            await sendJson(url, method, payload);
            toast(id ? (window.TRANSLATIONS?.paymentMethodUpdated || 'Payment method updated.') : (window.TRANSLATIONS?.paymentMethodCreated || 'Payment method created.'));
            $modal.modal('close');
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            if (err.errors) {
                window.injectValidationErrors?.($form, err.errors);
            } else {
                toast(err.message ?? (window.TRANSLATIONS?.saveFailed || 'Save failed.'), 'error');
            }
        }
    });
}

// ─── Delete ───────────────────────────────────────────────────────────────────

function initDeleteMethod() {
    const $deleteModal = $('#delete-method-modal');

    $(document).on('click', '.btn-delete-method', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete-method-message').text((window.TRANSLATIONS?.removeConfirm || 'Remove ":name"? This action cannot be undone.').replace(':name', name));
        $('#delete-method-id').val(id);
        $deleteModal.modal('open');
    });

    $('#btn-confirm-delete-method').on('click', async function () {
        const id = $('#delete-method-id').val();

        try {
            await sendJson(`/payment-methods/${id}`, 'DELETE');
            toast(window.TRANSLATIONS?.paymentMethodRemoved || 'Payment method removed.');
            $deleteModal.modal('close');
            setTimeout(() => window.location.reload(), 500);
        } catch {
            toast(window.TRANSLATIONS?.deleteFailed || 'Delete failed.', 'error');
        }
    });
}

// ─── Cents sync ───────────────────────────────────────────────────────────────

function initCentsSync() {
    syncCentsField('fee_fixed_display', 'fee_fixed');
    syncCentsField('min_order_display', 'min_order');
    syncCentsField('max_order_display', 'max_order');
}

// ─── Init ─────────────────────────────────────────────────────────────────────

$(function () {
    initGatewayTest();
    initToggleMethod();
    initMethodModal();
    initDeleteMethod();
    initCentsSync();
});
