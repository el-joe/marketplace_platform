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

        $btn.prop('disabled', true).text(t('admin.payment_methods.testing_label'));

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
                    ? `✓ ${t('admin.payment_methods.connected_label')} (${data.latency_ms ?? 0}ms)`
                    : `✗ ${data.message ?? (t('admin.payment_methods.failed_label'))}`);

            // Simple gateway status badge (index page)
            $(`#gateway-status-${provider}`).text(
                data.success ? `✓ ${data.latency_ms ?? 0}ms` : `✗ ${t('admin.payment_methods.failed_label')}`
            );
        } catch (err) {
            $status
                .removeClass('hidden border-success-200 bg-success-50 text-success-700')
                .addClass('border-danger-200 bg-danger-50 text-danger-700')
                .removeClass('hidden')
                .text(t('admin.payment_methods.request_failed_label'));
        } finally {
            $btn.prop('disabled', false).text(t('admin.payment_methods.test_label'));
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
                .text(active ? (t('admin.payment_methods.active_label')) : (t('admin.payment_methods.inactive_label')))
                .toggleClass('bg-success-50 text-success-700 hover:bg-success-100', active)
                .toggleClass('bg-gray-100 text-gray-500 hover:bg-gray-200', !active);
        } catch {
            toast(t('admin.payment_methods.failed_update_status'), 'error');
        }
    });
}

// ─── Gateway Credential Fields ─────────────────────────────────────────────────

const GATEWAY_FIELDS = {
    thawani: [
        { name: 'secret_key', label: 'Secret Key', type: 'password' },
        { name: 'publishable_key', label: 'Publishable Key', type: 'text' },
    ],
    stripe: [
        { name: 'secret_key', label: 'Secret Key (sk_...)', type: 'password' },
        { name: 'publishable_key', label: 'Publishable Key (pk_...)', type: 'text' },
    ],
    paytabs: [
        { name: 'profile_id', label: 'Profile ID', type: 'text' },
        { name: 'server_key', label: 'Server Key', type: 'password' },
        { name: 'base_url', label: 'Base URL', type: 'text' },
    ],
    tabby: [
        { name: 'public_key', label: 'Public Key', type: 'text' },
        { name: 'secret_key', label: 'Secret Key', type: 'password' },
        { name: 'base_url', label: 'Base URL', type: 'text' },
    ],
    noon_pay: [
        { name: 'app_key', label: 'App Key', type: 'text' },
        { name: 'app_secret', label: 'App Secret', type: 'password' },
        { name: 'base_url', label: 'Base URL', type: 'text' },
    ],
    bank_transfer: [
        { name: 'bank_name', label: 'Bank Name', type: 'text' },
        { name: 'account_name', label: 'Account Name', type: 'text' },
        { name: 'account_number', label: 'Account Number', type: 'text' },
        { name: 'iban', label: 'IBAN', type: 'text' },
        { name: 'swift', label: 'SWIFT / BIC', type: 'text' },
        { name: 'reference_instructions', label: 'Reference Instructions', type: 'text' },
    ],
    paypal: [
        { name: 'client_id', label: 'Client ID', type: 'text' },
        { name: 'client_secret', label: 'Client Secret', type: 'password' },
    ],
    cod: [],
};

function renderCredentialFields(code) {
    const $fields = $('#credentials-fields');
    const $testBtn = $('#btn-test-connection');
    $fields.empty();

    const fields = GATEWAY_FIELDS[code] || [];

    if (fields.length === 0) {
        $fields.html(`<p class="text-xs text-gray-400 italic">${t('admin.payment_methods.no_credential_fields')}</p>`);
        $testBtn.prop('disabled', true);
        return;
    }

    fields.forEach((field) => {
        $fields.append(`
            <div class="grid grid-cols-3 gap-2 items-center">
                <label class="text-xs font-medium text-gray-600 col-span-1">${field.label}</label>
                <input type="${field.type}" name="credentials[${field.name}]" autocomplete="off"
                    placeholder="${t('admin.payment_methods.leave_blank_to_keep')}"
                    class="col-span-2 block w-full rounded-lg border border-gray-300 py-1.5 px-2.5 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
            </div>
        `);
    });

    $testBtn.prop('disabled', false);
}

function initGatewayCredentials() {
    $(document).on('change', '#gateway_code', function () {
        renderCredentialFields($(this).val());
        $('#test-connection-result').text('');
    });
}

// ─── Test Connection (per-method modal) ────────────────────────────────────────

function initTestConnection() {
    $('#btn-test-connection').on('click', async function () {
        const $btn = $(this);
        const $result = $('#test-connection-result');
        const id = $('#method-id').val();

        if (!id) {
            $result.removeClass('text-green-600 text-red-600').addClass('text-gray-500')
                .text(t('admin.payment_methods.save_first_to_test'));
            return;
        }

        $btn.prop('disabled', true);
        $result.removeClass('text-green-600 text-red-600').addClass('text-gray-500').text(t('admin.payment_methods.testing_label'));

        try {
            const res = await sendJson(`/payment-methods/${id}/test-connection`, 'POST');
            $result.removeClass('text-gray-500 text-green-600 text-red-600')
                .addClass(res.success ? 'text-green-600' : 'text-red-600')
                .text((res.success ? '✅ ' : '❌ ') + (res.message ?? (res.success ? t('admin.payment_methods.connected_label') : t('admin.payment_methods.failed_label'))));
        } catch (err) {
            $result.removeClass('text-gray-500 text-green-600').addClass('text-red-600')
                .text('❌ ' + (err.message ?? t('admin.payment_methods.request_failed_label')));
        } finally {
            $btn.prop('disabled', false);
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
        if (window.Alpine) window.Alpine.$data(document.getElementById('method-form')).methodType = '';
        renderCredentialFields('');
        $('#test-connection-result').text('');
        $modal.find('[id$="-title"]').text(`${t('admin.payment_methods.add_payment_method_label')} \u2014 ${countryName}`);
        $modal.modal('open');
    });

    // Header "Add Method" button (pick country via form)
    $('#btn-add-method').on('click', function () {
        $form[0].reset();
        $('#method-id').val('');
        $('#method-http').val('POST');
        $('#method-country-id').val('');
        if (window.Alpine) window.Alpine.$data(document.getElementById('method-form')).methodType = '';
        renderCredentialFields('');
        $('#test-connection-result').text('');
        $modal.find('[id$="-title"]').text(t('admin.payment_methods.add_payment_method_label'));
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
        $form.find('[name="installments_count"]').val(row.installments_count ?? '');
        $form.find('[name="installment_label_en"]').val(row.installment_label_en ?? '');
        $form.find('[name="installment_label_ar"]').val(row.installment_label_ar ?? '');
        $form.find('[name="provider_logo_path"]').val(row.provider_logo_path ?? '');
        $form.find('[name="learn_more_url"]').val(row.learn_more_url ?? '');
        $form.find('[name="gateway_code"]').val(row.gateway_code ?? '');
        $form.find('[name="environment"]').val(row.environment ?? 'sandbox');
        renderCredentialFields(row.gateway_code ?? '');
        $('#test-connection-result').text('');
        if (window.Alpine) window.Alpine.$data(document.getElementById('method-form')).methodType = row.method_type ?? '';

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

        $modal.find('[id$="-title"]').text(t('admin.payment_methods.edit_payment_method_label'));
        $modal.modal('open');
    });

    // Form submit
    $form.on('submit', async function (e) {
        e.preventDefault();

        const id = $('#method-id').val();
        const method = $('#method-http').val();
        const url = id ? `/payment-methods/${id}` : '/payment-methods';

        // Collect non-empty credential fields
        const credentials = {};
        $form.find('[name^="credentials["]').each(function () {
            const val = $(this).val();
            if (val) {
                const match = /^credentials\[(.+)\]$/.exec($(this).attr('name'));
                if (match) credentials[match[1]] = val;
            }
        });

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
            installments_count: parseInt($form.find('[name="installments_count"]').val()) || null,
            installment_label_en: $form.find('[name="installment_label_en"]').val() || null,
            installment_label_ar: $form.find('[name="installment_label_ar"]').val() || null,
            provider_logo_path: $form.find('[name="provider_logo_path"]').val() || null,
            learn_more_url: $form.find('[name="learn_more_url"]').val() || null,
            gateway_code: $form.find('[name="gateway_code"]').val() || null,
            environment: $form.find('[name="environment"]').val() || null,
            credentials,
            webhook_secret: $form.find('[name="webhook_secret"]').val() || null,
        };

        try {
            await sendJson(url, method, payload);
            toast(id ? (t('admin.payment_methods.payment_method_updated')) : (t('admin.payment_methods.payment_method_created')));
            $modal.modal('close');
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            if (err.errors) {
                window.injectValidationErrors?.($form, err.errors);
            } else {
                toast(err.message ?? (t('admin.payment_methods.save_failed')), 'error');
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

        $('#delete-method-message').text(t('admin.payment_methods.remove_confirm', { name }));
        $('#delete-method-id').val(id);
        $deleteModal.modal('open');
    });

    $('#btn-confirm-delete-method').on('click', async function () {
        const id = $('#delete-method-id').val();

        try {
            await sendJson(`/payment-methods/${id}`, 'DELETE');
            toast(t('admin.payment_methods.payment_method_removed'));
            $deleteModal.modal('close');
            setTimeout(() => window.location.reload(), 500);
        } catch {
            toast(t('admin.payment_methods.delete_failed'), 'error');
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
    initGatewayCredentials();
    initTestConnection();
    initDeleteMethod();
    initCentsSync();
});
