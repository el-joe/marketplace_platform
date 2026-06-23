/**
 * partner/warehouse-create.js
 * Warehouse registration form + Transfer create form
 */

import './app.js';
import { csrfToken, postJson, toast } from './datatable.js';

// ─── Warehouse Create ─────────────────────────────────────────────────────────

function initWarehouseCreate() {
    const cfg = window.WAREHOUSE_CREATE;
    const form = document.getElementById('warehouse-create-form');
    if (!cfg || !form) return;

    // Country → city cascade
    const countrySelect = document.getElementById('wh-country');
    const citySelect    = document.getElementById('wh-city');

    if (countrySelect && citySelect) {
        countrySelect.addEventListener('change', async () => {
            const countryId = countrySelect.value;
            citySelect.innerHTML = '<option value="">Loading…</option>';

            if (!countryId) {
                citySelect.innerHTML = '<option value="">Select country first…</option>';
                return;
            }

            try {
                const res = await fetch(`/api/cities?country_id=${countryId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const json = await res.json();
                const cities = json.data ?? json;

                citySelect.innerHTML = '<option value="">Select city…</option>';
                cities.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name_en ?? c.name;
                    citySelect.appendChild(opt);
                });
            } catch {
                citySelect.innerHTML = '<option value="">Failed to load cities</option>';
            }
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn   = document.getElementById('register-warehouse-btn');
        const errEl = document.getElementById('wh-error');
        errEl.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Registering…';

        const payload = {
            name:               document.getElementById('wh-name')?.value,
            country_id:         document.getElementById('wh-country')?.value,
            city_id:            document.getElementById('wh-city')?.value || null,
            area:               document.getElementById('wh-area')?.value || null,
            street_address:     document.getElementById('wh-street')?.value,
            building:           document.getElementById('wh-building')?.value || null,
            total_capacity_m3:  document.getElementById('wh-capacity')?.value || null,
        };

        const { ok, data } = await postJson(cfg.storeUrl, payload);

        if (ok && data.success) {
            toast('Warehouse registered successfully.');
            setTimeout(() => window.location.href = `/warehouses/${data.data.id}`, 600);
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'Failed to register warehouse.');
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Register warehouse';
        }
    });
}

// ─── Transfer Create ──────────────────────────────────────────────────────────

let transferItems = []; // [{ vendor_listing_id, quantity, name, sku }]

function renderTransferItems() {
    const list  = document.getElementById('transfer-items-list');
    const empty = document.getElementById('transfer-items-empty');
    if (!list) return;

    if (transferItems.length === 0) {
        empty?.classList.remove('hidden');
        list.querySelectorAll('.transfer-item-row').forEach(el => el.remove());
        return;
    }

    empty?.classList.add('hidden');
    list.querySelectorAll('.transfer-item-row').forEach(el => el.remove());

    transferItems.forEach((item, idx) => {
        const row = document.createElement('div');
        row.className = 'transfer-item-row flex items-center gap-3 p-3 bg-gray-50 rounded-xl';
        row.innerHTML = `
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${item.name}</p>
                <p class="text-xs text-gray-500">${item.sku}</p>
            </div>
            <input type="number" min="1" value="${item.quantity}"
                class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-primary-500"
                data-idx="${idx}" />
            <button type="button" class="text-red-400 hover:text-red-600 remove-item-btn" data-idx="${idx}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>`;
        list.appendChild(row);

        row.querySelector('input[type=number]').addEventListener('input', (e) => {
            transferItems[idx].quantity = parseInt(e.target.value, 10) || 1;
        });

        row.querySelector('.remove-item-btn').addEventListener('click', () => {
            transferItems.splice(idx, 1);
            renderTransferItems();
        });
    });
}

function initTransferCreate() {
    const cfg = window.TRANSFER_CREATE_CFG;
    const form = document.getElementById('transfer-create-form');
    if (!cfg || !form) return;

    // Item search
    const addItemBtn = document.getElementById('add-transfer-item-btn');
    const searchInput = document.getElementById('item-search-input');
    const resultsEl  = document.getElementById('item-search-results');
    const selectedInfoEl = document.getElementById('item-selected-info');
    const selectedNameEl = document.getElementById('item-selected-name');
    const selectedSkuEl  = document.getElementById('item-selected-sku');
    const selectedIdInput = document.getElementById('item-selected-listing-id');
    const qtyWrap    = document.getElementById('item-qty-wrap');
    const confirmBtn = document.getElementById('confirm-add-item-btn');

    addItemBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (resultsEl)  resultsEl.innerHTML = '';
        if (selectedInfoEl) selectedInfoEl.classList.add('hidden');
        if (qtyWrap) qtyWrap.classList.add('hidden');
        if (confirmBtn) confirmBtn.disabled = true;
        document.getElementById('add-item-modal')?.classList.remove('hidden');
        document.getElementById('add-item-modal')?.classList.add('flex');
        searchInput?.focus();
    });

    let searchTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) { resultsEl.innerHTML = ''; return; }

        searchTimer = setTimeout(async () => {
            resultsEl.innerHTML = '<p class="text-xs text-gray-400 p-2">Searching…</p>';
            try {
                const res = await fetch(`/partner/listings?search=${encodeURIComponent(q)}&format=json`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const json = await res.json();
                const items = json.data ?? [];

                resultsEl.innerHTML = '';
                if (!items.length) {
                    resultsEl.innerHTML = '<p class="text-xs text-gray-400 p-2">No products found.</p>';
                    return;
                }

                items.slice(0, 8).forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'p-2 rounded-lg hover:bg-gray-100 cursor-pointer text-sm';
                    div.innerHTML = `<p class="font-medium text-gray-800">${item.name_en ?? item.product_name}</p>
                        <p class="text-xs text-gray-500">${item.vendor_sku ?? ''}</p>`;
                    div.addEventListener('click', () => {
                        selectedIdInput.value = item.id;
                        selectedNameEl.textContent = item.name_en ?? item.product_name;
                        selectedSkuEl.textContent  = item.vendor_sku ?? '';
                        selectedInfoEl.classList.remove('hidden');
                        qtyWrap.classList.remove('hidden');
                        confirmBtn.disabled = false;
                        resultsEl.innerHTML = '';
                        searchInput.value = '';
                    });
                    resultsEl.appendChild(div);
                });
            } catch {
                resultsEl.innerHTML = '<p class="text-xs text-red-500 p-2">Search failed.</p>';
            }
        }, 300);
    });

    confirmBtn?.addEventListener('click', () => {
        const listingId = selectedIdInput?.value;
        const qty       = parseInt(document.getElementById('item-qty')?.value, 10) || 1;
        if (!listingId) return;

        // Prevent duplicates
        if (transferItems.some(i => i.vendor_listing_id === listingId)) {
            toast('This product is already in the list.', 'error');
            return;
        }

        transferItems.push({
            vendor_listing_id: listingId,
            quantity:          qty,
            name:              selectedNameEl.textContent,
            sku:               selectedSkuEl.textContent,
        });

        renderTransferItems();
        document.getElementById('add-item-modal').classList.add('hidden');
        document.getElementById('add-item-modal').classList.remove('flex');
    });

    // Form submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn   = document.getElementById('create-transfer-btn');
        const errEl = document.getElementById('transfer-error');
        errEl.classList.add('hidden');

        if (transferItems.length === 0) {
            errEl.textContent = 'Please add at least one item.';
            errEl.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Creating…';

        const payload = {
            source_warehouse_id:      document.getElementById('source-warehouse-select')?.value,
            destination_warehouse_id: document.getElementById('destination-warehouse-select')?.value,
            expected_arrival_date:    document.getElementById('transfer-arrival-date')?.value || null,
            notes:                    document.getElementById('transfer-notes')?.value || null,
            items:                    transferItems.map(i => ({
                vendor_listing_id: i.vendor_listing_id,
                quantity:          i.quantity,
            })),
        };

        const res = await fetch(cfg.storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (res.ok && data.success) {
            toast('Transfer created successfully.');
            setTimeout(() => window.location.href = `/partner/warehouses/transfers/${data.data.id}`, 600);
        } else {
            errEl.textContent = data.message || 'Failed to create transfer.';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Create transfer';
        }
    });
}

// ─── Boot ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initWarehouseCreate();
    initTransferCreate();
});
