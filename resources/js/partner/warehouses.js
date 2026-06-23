/**
 * partner/warehouses.js
 * Index page + Transfers index & show pages
 */

import './app.js';
import { createPartnerTable, postJson, showModal, hideModal, toast } from './datatable.js';

// ─── Transfers DataTable ──────────────────────────────────────────────────────

function initTransfersDatatable() {
    const cfg = window.TRANSFERS_CFG;
    if (!cfg || !document.getElementById('transfers-table')) return;

    createPartnerTable('transfers-table', {
        url: cfg.datatableUrl,
        columns: [
            { data: 'number' },
            { data: 'source' },
            { data: 'destination' },
            { data: 'status' },
            { data: 'date' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[4, 'desc']],
        searchInputId: 'transfers-search',
        language: {
            emptyTable:  'No transfers found.',
            info:        'Showing _START_–_END_ of _TOTAL_ transfers',
            infoEmpty:   'No transfers',
            zeroRecords: 'No transfers match your search.',
            processing:  '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-primary-400 border-t-transparent rounded-full animate-spin"></div></div>',
        },
    });
}

// ─── Transfer Show ────────────────────────────────────────────────────────────

function initTransferShow() {
    const cfg = window.TRANSFER_SHOW_CFG;
    if (!cfg) return;

    const shipBtn   = document.getElementById('ship-transfer-btn');
    const cancelBtn = document.getElementById('cancel-transfer-btn');

    if (shipBtn) {
        shipBtn.addEventListener('click', () => showModal('ship-modal'));
    }

    const confirmShipBtn = document.getElementById('confirm-ship-btn');
    if (confirmShipBtn) {
        confirmShipBtn.addEventListener('click', async () => {
            confirmShipBtn.disabled = true;
            const errEl = document.getElementById('ship-error');
            errEl.classList.add('hidden');

            const { ok, data } = await postJson(cfg.shipUrl, {
                carrier:          document.getElementById('ship-carrier')?.value || null,
                tracking_number:  document.getElementById('ship-tracking')?.value || null,
            });

            if (ok && data.success) {
                toast('Transfer marked as shipped.');
                setTimeout(() => location.reload(), 800);
            } else {
                errEl.textContent = data.message || 'Failed to update transfer.';
                errEl.classList.remove('hidden');
                confirmShipBtn.disabled = false;
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', async () => {
            if (!confirm('Are you sure you want to cancel this transfer?')) return;
            cancelBtn.disabled = true;

            const { ok, data } = await postJson(cfg.cancelUrl);
            if (ok && data.success) {
                toast('Transfer cancelled.');
                setTimeout(() => location.reload(), 800);
            } else {
                toast(data.message || 'Failed to cancel transfer.', 'error');
                cancelBtn.disabled = false;
            }
        });
    }
}

// ─── Boot ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initTransfersDatatable();
    initTransferShow();
});
