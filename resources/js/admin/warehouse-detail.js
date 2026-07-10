import DataTable from 'datatables.net';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ─── Inventory DataTable ──────────────────────────────────────────────────────

function initInventoryTable() {
    const tableEl = document.getElementById('inventory-table');
    if (!tableEl) return;

    const dt = new DataTable('#inventory-table', {
        processing: true,
        serverSide: true,
        order: [[0, 'asc']],
        ajax: {
            url: tableEl.dataset.url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.filter_status = document.getElementById('inv-filter-status')?.value ?? '';
            },
        },
        columns: [
            { data: 'product' },
            { data: 'vendor', orderable: false },
            { data: 'on_hand', className: 'text-right tabular-nums' },
            { data: 'available', className: 'text-right tabular-nums' },
            { data: 'reserved', className: 'text-right tabular-nums' },
            { data: 'damaged', className: 'text-right tabular-nums' },
            { data: 'bin', orderable: false },
            { data: 'actions', orderable: false },
        ],
        pageLength: 25,
    });

    document.getElementById('inv-filter-status')?.addEventListener('change', () => dt.draw());

    return dt;
}

// ─── Toggle Active ────────────────────────────────────────────────────────────

function initToggleActive() {
    document.querySelector('.js-toggle-active')?.addEventListener('click', async function () {
        const btn = this;
        const isActive = parseInt(btn.dataset.active, 10);

        if (!confirm(isActive ? (window.TRANSLATIONS?.deactivateWarehouseConfirm || 'Deactivate this warehouse?') : (window.TRANSLATIONS?.activateWarehouseConfirm || 'Activate this warehouse?'))) return;

        try {
            const res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({}),
            });
            const json = await res.json();
            if (!res.ok) throw json;
            window.Toast?.success(json.message);
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            window.Toast?.error(err.message ?? (window.TRANSLATIONS?.requestFailed || 'Request failed.'));
        }
    });
}

// ─── Adjust Inventory Modal ───────────────────────────────────────────────────

function initAdjustModal() {
    const modal = document.getElementById('adjust-modal');
    const adjustForm = document.getElementById('adjust-form');
    const adjustUrlEl = document.getElementById('adjust-url');
    const errorEl = document.getElementById('adjust-error');

    if (!modal) return;

    // Open modal on row button click
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-adjust-inventory');
        if (!btn) return;

        adjustUrlEl.value = btn.dataset.url;
        errorEl.classList.add('hidden');
        errorEl.textContent = '';
        adjustForm.reset();
        adjustUrlEl.value = btn.dataset.url; // re-set after reset
        modal.classList.remove('hidden');
    });

    // Close
    const closeModal = () => modal.classList.add('hidden');
    document.getElementById('close-adjust-modal')?.addEventListener('click', closeModal);
    document.getElementById('cancel-adjust')?.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Submit
    adjustForm?.addEventListener('submit', async e => {
        e.preventDefault();

        const url = adjustUrlEl.value;
        const formData = new FormData(adjustForm);
        const payload = {
            delta: parseInt(formData.get('delta'), 10),
            movement_type: formData.get('movement_type'),
            reason: formData.get('reason'),
        };

        errorEl.classList.add('hidden');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();

            if (!res.ok) {
                errorEl.textContent = json.message ?? 'Adjustment failed.';
                errorEl.classList.remove('hidden');
                return;
            }

            window.Toast?.success(json.message ?? 'Inventory adjusted.');
            closeModal();

            // Refresh the DataTable
            const dt = DataTable.instances.find(i => i.table().node().id === 'inventory-table');
            dt?.draw(false);
        } catch (err) {
            errorEl.textContent = err.message ?? 'Network error.';
            errorEl.classList.remove('hidden');
        }
    });
}

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initInventoryTable();
    initToggleActive();
    initAdjustModal();
});
