/**
 * partner/inventory.js
 * Inventory management: DataTable + adjust stock modal
 */

import './app.js';

// ─────────────────────────────────────────────────────────────────────────────
// Utilities
// ─────────────────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function showModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
}

function hideModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
}

function showError(elId, msg) {
    const el = document.getElementById(elId);
    if (el) { el.textContent = msg; el.classList.remove('hidden'); }
}

function hideError(elId) {
    const el = document.getElementById(elId);
    if (el) el.classList.add('hidden');
}

function toast(msg, type = 'success') {
    if (window.Toastify) {
        Toastify({
            text: msg,
            duration: 4000,
            gravity: 'top',
            position: 'left',
            style: {
                background: type === 'success' ? '#16a34a' : '#dc2626',
                borderRadius: '0.75rem',
                fontFamily: 'inherit',
            },
        }).showToast();
    }
}

async function postJson(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    });
    return { ok: res.ok, status: res.status, data: await res.json() };
}

// ─────────────────────────────────────────────────────────────────────────────
// Inventory DataTable
// ─────────────────────────────────────────────────────────────────────────────

let inventoryTable = null;

function initInventoryDataTable() {
    const tableEl = document.getElementById('inventory-table');
    const cfg = window.INVENTORY;
    if (!tableEl || !cfg) return;

    inventoryTable = $(tableEl).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => {
                d.filter = cfg.filterParam || '';
            },
        },
        columns: [
            {
                data: 'name_ar',
                render: (data, type, row) => {
                    const img = row.image_url
                        ? `<img src="${row.image_url}" class="w-8 h-8 rounded-lg object-cover">`
                        : `<div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">📦</div>`;
                    return `<a href="${row.listing_url}" class="flex items-center gap-2 group">
                        ${img}
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-800 group-hover:text-blue-600 truncate max-w-[150px]">${data || row.name_en}</p>
                        </div>
                    </a>`;
                },
            },
            {
                data: 'variant_name',
                render: (data, type, row) =>
                    `<div class="text-xs"><p class="text-gray-700">${data}</p><p class="font-mono text-gray-400">${row.sku}</p></div>`,
            },
            {
                data: 'warehouse_name',
                render: (data, type, row) =>
                    `<span class="text-xs text-gray-700">${data}</span>`,
            },
            {
                data: 'quantity_on_hand',
                className: 'text-center text-sm font-medium text-gray-800',
            },
            {
                data: 'quantity_reserved',
                className: 'text-center text-sm text-gray-500',
            },
            {
                data: 'available',
                className: 'text-center',
                render: (data) => data,
            },
            {
                data: 'quantity_inbound',
                className: 'text-center text-xs text-blue-500',
                render: (data) => data > 0 ? data : '<span class="text-gray-300">—</span>',
            },
            {
                data: 'quantity_damaged',
                className: 'text-center text-xs text-red-400',
                render: (data) => data > 0 ? `<span class="text-red-400">${data}</span>` : '<span class="text-gray-300">—</span>',
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) =>
                    `<button type="button"
                        class="btn-inv-adjust text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded-lg transition-colors"
                        data-listing-id="${row.listing_id}"
                        data-inv-id="${row.id}"
                        data-warehouse="${row.warehouse_name}"
                        data-on-hand="${row.quantity_on_hand}">
                        تعديل
                    </button>`,
            },
        ],
        order: [[3, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/ar.json',
            emptyTable: 'لا توجد بيانات مخزون',
            processing: '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin"></div></div>',
        },
        dom: "<'flex items-center justify-between mb-4'<'text-sm text-gray-500'i><'flex gap-2'p>>t<'flex items-center justify-between mt-4'<'text-sm text-gray-500'i><'flex gap-2'p>>",
        pageLength: 25,
        searching: true,
    });

    // Bind adjust buttons (delegated — rows load async)
    $(tableEl).on('click', '.btn-inv-adjust', function () {
        const { listingId, invId, warehouse, onHand } = this.dataset;
        document.getElementById('inv-adjust-listing-id').value = listingId;
        document.getElementById('inv-adjust-inv-id').value = invId;
        document.getElementById('inv-adjust-warehouse').textContent = warehouse;
        document.getElementById('inv-adjust-current').textContent = onHand;
        document.getElementById('inv-adjust-form')?.reset();
        document.getElementById('inv-adjust-inv-id').value = invId; // re-set after reset
        hideError('inv-adjust-error');
        showModal('inventory-adjust-modal');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Adjust Stock Modal
// ─────────────────────────────────────────────────────────────────────────────

function initAdjustModal() {
    const modal = document.getElementById('inventory-adjust-modal');
    if (!modal) return;

    document.getElementById('inv-adjust-close')?.addEventListener('click', () => hideModal('inventory-adjust-modal'));
    document.getElementById('inv-adjust-cancel')?.addEventListener('click', () => hideModal('inventory-adjust-modal'));
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal('inventory-adjust-modal'); });

    document.getElementById('inv-adjust-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError('inv-adjust-error');

        const listingId = document.getElementById('inv-adjust-listing-id').value;
        const formData = new FormData(e.target);
        const payload = Object.fromEntries(formData.entries());

        const submitBtn = e.target.querySelector('[type=submit]');
        submitBtn.disabled = true;

        // Build the adjust-stock URL for this listing
        const cfg = window.INVENTORY;
        const url = `${cfg.adjustBaseUrl}/listings/${listingId}/adjust-stock`;

        const { ok, data } = await postJson(url, payload);
        if (ok) {
            hideModal('inventory-adjust-modal');
            toast(data.message ?? 'تم تعديل المخزون.');
            inventoryTable?.ajax.reload(null, false);
        } else {
            showError('inv-adjust-error', data.message ?? 'حدث خطأ.');
        }
        submitBtn.disabled = false;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Boot
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initInventoryDataTable();
    initAdjustModal();
});
