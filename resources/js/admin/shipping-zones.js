import DataTable from 'datatables.net';

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

// ─── Shipping Zones DataTable ─────────────────────────────────────────────────

function initShippingZonesTable() {
    const tableEl = document.getElementById('shipping-zones-table');
    if (!tableEl) return;

    const dt = new DataTable('#shipping-zones-table', {
        processing: true,
        serverSide: true,
        order: [[0, 'asc']],
        ajax: {
            url: tableEl.dataset.url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.country_id = document.getElementById('filter-country')?.value ?? '';
                d.is_active  = document.getElementById('filter-status')?.value ?? '';
            },
        },
        columns: [
            { data: 'name' },
            { data: 'country' },
            { data: 'description', orderable: false },
            { data: 'cities_count' },
            { data: 'is_active' },
            { data: 'actions', orderable: false },
        ],
        pageLength: 25,
    });

    // Filters
    ['filter-country', 'filter-status'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dt.draw());
    });

    document.getElementById('btn-reset-filters')?.addEventListener('click', () => {
        ['filter-country', 'filter-status'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        dt.draw();
    });

    return dt;
}

// ─── Zone Modal ───────────────────────────────────────────────────────────────

function openZoneModal(zone = null) {
    const modal = document.getElementById('zone-modal');
    const form  = document.getElementById('zone-form');
    const idEl  = document.getElementById('zone-id');
    const methodEl = document.getElementById('zone-method');

    // Reset
    form.reset();
    idEl.value = '';
    methodEl.value = 'POST';
    document.querySelector('#zone-modal .modal-title, #zone-modal h2, #zone-modal [data-modal-title]')?.textContent;

    if (zone) {
        idEl.value = zone.id;
        methodEl.value = 'PUT';
        document.getElementById('zone-name').value        = zone.name ?? '';
        document.getElementById('zone-country').value     = zone.country_id ?? '';
        document.getElementById('zone-description').value = zone.description ?? '';

        // Toggle active state
        const toggle = document.getElementById('zone-is-active');
        if (toggle) toggle.checked = Boolean(parseInt(zone.isActive, 10));
    }

    modal?.modal?.('open');
}

function initZoneModal(dt) {
    // Open via Add button
    document.getElementById('btn-add-zone')?.addEventListener('click', () => openZoneModal());

    // Open via Edit button in table (event delegation)
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-edit-zone');
        if (!btn) return;
        openZoneModal({
            id:          btn.dataset.id,
            name:        btn.dataset.name,
            description: btn.dataset.description,
            country_id:  btn.dataset.countryId,
            isActive:    btn.dataset.isActive,
        });
    });

    // Delete
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-delete-zone');
        if (!btn) return;
        if (!confirm(`Delete zone "${btn.dataset.name}"? Cities in this zone will be unassigned.`)) return;
        sendJson(btn.dataset.url, 'DELETE')
            .then(res => { window.Toast?.success(res.message); dt?.draw(); })
            .catch(err => window.Toast?.error(err.message ?? 'Delete failed.'));
    });

    // Submit
    document.getElementById('zone-form')?.addEventListener('submit', async e => {
        e.preventDefault();
        const id     = document.getElementById('zone-id').value;
        const method = document.getElementById('zone-method').value;
        const isActive = document.getElementById('zone-is-active');

        const payload = {
            name:        document.getElementById('zone-name').value,
            country_id:  document.getElementById('zone-country').value,
            description: document.getElementById('zone-description').value,
            is_active:   isActive?.checked ? 1 : 0,
        };

        const baseUrl = '/admin/shipping-zones';
        const url     = id ? `${baseUrl}/${id}` : baseUrl;

        const submitBtn = document.getElementById('zone-submit-btn');
        submitBtn.disabled = true;

        try {
            const res = await sendJson(url, method, payload);
            window.Toast?.success(res.message);
            document.getElementById('zone-modal')?.modal?.('close');
            dt?.draw();
        } catch (err) {
            const msg = Object.values(err.errors ?? {}).flat()[0] ?? err.message ?? 'Save failed.';
            window.Toast?.error(msg);
        } finally {
            submitBtn.disabled = false;
        }
    });
}

// ─── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const dt = initShippingZonesTable();
    initZoneModal(dt);
});
