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
    if (window.Toast) {
        type === 'error' ? window.Toast.error(message) : window.Toast.success(message);
    } else {
        alert(message);
    }
}

function toCents(value) {
    const parsed = parseFloat(value);
    if (isNaN(parsed) || parsed < 0) return 0;
    return Math.round(parsed * 100);
}

function updateUrl(template, id) {
    return template.replace('__ID__', id);
}

const statusBadge = {
    active: '<span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700">Active</span>',
    future: '<span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">Future</span>',
    expired: '<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Expired</span>',
};

// ─── DataTable ──────────────────────────────────────────────────────────────

function initSettingsTable() {
    const routes = window.VENDOR_SUBSIDY_SETTINGS_ROUTES;

    window.initDataTable('subsidy-settings-table', {
        url: routes.index,
        ajaxMethod: 'GET',
        order: [[3, 'desc']],
        columns: [
            { data: 'country' },
            { data: 'admin_support', className: 'text-end' },
            { data: 'vendor_pays', className: 'text-end' },
            { data: 'effective_from' },
            { data: 'effective_until' },
            {
                data: 'status',
                className: 'text-center',
                orderable: false,
                render: (value) => statusBadge[value] ?? value,
            },
            {
                data: 'actions',
                className: 'text-center',
                orderable: false,
                render(actions, type, row) {
                    const editBtn = row.can_edit
                        ? `<button type="button" class="btn-edit-setting p-1 rounded text-gray-400 hover:text-primary-600" data-id="${actions.id}" data-row='${JSON.stringify(row)}' title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>`
                        : '';
                    const deactivateBtn = row.status !== 'expired'
                        ? `<button type="button" class="btn-deactivate-setting p-1 rounded text-gray-400 hover:text-danger-600" data-id="${actions.id}" title="Deactivate">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.36 6.64a9 9 0 11-12.73 0M12 3v9"/>
                            </svg>
                        </button>`
                        : '';
                    return `<div class="flex items-center justify-center gap-1">${editBtn}${deactivateBtn}</div>`;
                },
            },
        ],
        pageLength: 25,
    });
}

// ─── Setting Modal ──────────────────────────────────────────────────────────

function initSettingModal() {
    const $modal = $('#subsidy-setting-modal');
    const $form = $('#subsidy-setting-form');

    $('#btn-add-setting').on('click', function () {
        $form[0].reset();
        $('#setting-id').val('');
        $('#setting-http').val('POST');
        $form.find('[name="country_id"]').val('').trigger('change');
        $('#setting-admin-support-display, #setting-vendor-share-display').val('');
        $modal.modal('open');
    });

    $(document).on('click', '.btn-edit-setting', function () {
        const data = $(this).data('row').actions;

        $form[0].reset();
        $('#setting-id').val(data.id);
        $('#setting-http').val('PUT');

        $form.find('[name="country_id"]').val(data.country_id).trigger('change');
        $('#setting-admin-support-display').val((data.admin_support_cents / 100).toFixed(2));
        $('#setting-vendor-share-display').val((data.vendor_share_cents / 100).toFixed(2));
        $form.find('[name="currency"]').val(data.currency);
        $form.find('[name="effective_from"]')[0]?._flatpickr?.setDate(data.effective_from);
        $form.find('[name="effective_until"]')[0]?._flatpickr?.setDate(data.effective_until);

        $modal.modal('open');
    });

    $(document).on('click', '.btn-deactivate-setting', async function () {
        const id = $(this).data('id');
        if (!confirm('Deactivate this subsidy setting immediately?')) return;

        try {
            await sendJson(updateUrl(window.VENDOR_SUBSIDY_SETTINGS_ROUTES.deactivate, id), 'POST');
            toast('Subsidy setting deactivated.');
            window.reloadDataTable('subsidy-settings-table');
        } catch (err) {
            toast(err.message ?? 'Failed to deactivate.', 'error');
        }
    });

    $form.on('submit', async function (e) {
        e.preventDefault();

        const id = $('#setting-id').val();
        const method = $('#setting-http').val();
        const routes = window.VENDOR_SUBSIDY_SETTINGS_ROUTES;
        const url = id ? updateUrl(routes.update, id) : routes.store;

        const payload = {
            country_id: $form.find('[name="country_id"]').val(),
            admin_support_cents: toCents($('#setting-admin-support-display').val()),
            vendor_share_cents: toCents($('#setting-vendor-share-display').val()),
            currency: $form.find('[name="currency"]').val(),
            effective_from: $form.find('[name="effective_from"]').val(),
            effective_until: $form.find('[name="effective_until"]').val() || null,
        };

        try {
            await sendJson(url, method, payload);
            toast(id ? 'Subsidy setting updated.' : 'Subsidy setting created.');
            $modal.modal('close');
            window.reloadDataTable('subsidy-settings-table');
        } catch (err) {
            if (err.errors) {
                window.injectValidationErrors?.($form, err.errors);
                const firstError = Object.values(err.errors)[0]?.[0];
                if (firstError) toast(firstError, 'error');
            } else {
                toast(err.message ?? 'Save failed.', 'error');
            }
        }
    });
}

// ─── Init ───────────────────────────────────────────────────────────────────

$(function () {
    if (!document.getElementById('subsidy-settings-table')) return;
    initSettingsTable();
    initSettingModal();
});
