/**
 * partner/listings.js
 * Product Listings module: DataTable, product search, price edit, status toggle, stock adjust
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
// Status badge HTML (for DataTable column rendering)
// ─────────────────────────────────────────────────────────────────────────────

function listingStatusBadge(status) {
    const map = {
        active: ['bg-green-100 text-green-700', 'نشط'],
        paused: ['bg-gray-100 text-gray-600', 'موقوف'],
        pending_review: ['bg-yellow-100 text-yellow-700', 'قيد المراجعة'],
        draft: ['bg-gray-100 text-gray-500', 'مسودة'],
        rejected: ['bg-red-100 text-red-700', 'مرفوض'],
        out_of_stock: ['bg-red-50 text-red-500', 'نفد المخزون'],
        archived: ['bg-gray-100 text-gray-400', 'مؤرشف'],
    };
    const [cls, label] = map[status] ?? ['bg-gray-100 text-gray-600', status];
    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${cls}">${label}</span>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// Listings DataTable (index page)
// ─────────────────────────────────────────────────────────────────────────────

let listingsTable = null;
let selectedIds = new Set();

function initListingsDataTable() {
    const tableEl = document.getElementById('listings-table');
    const cfg = window.LISTINGS;
    if (!tableEl || !cfg) return;

    listingsTable = $(tableEl).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => {
                d.status = cfg.statusFilter !== 'all' ? cfg.statusFilter : '';
                const searchEl = document.getElementById('listing-search');
                d.search_term = searchEl ? searchEl.value : '';
            },
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: (data, type, row) =>
                    `<input type="checkbox" class="row-select rounded" data-id="${row.id}" data-status="${row.status}">`,
            },
            {
                data: 'name_ar',
                render: (data, type, row) => {
                    const img = row.image_url
                        ? `<img src="${row.image_url}" class="w-8 h-8 rounded-lg object-cover flex-shrink-0">`
                        : `<div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0"><span class="text-gray-400 text-xs">📦</span></div>`;
                    return `<a href="${row.show_url}" class="flex items-center gap-2 group">
                        ${img}
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-xs group-hover:text-blue-600 leading-tight truncate max-w-[160px]">${data || row.name_en}</p>
                            ${row.name_ar && row.name_en ? `<p class="text-xs text-gray-400 truncate max-w-[160px]">${row.name_en}</p>` : ''}
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
                data: 'status',
                render: (data) => listingStatusBadge(data),
            },
            {
                data: 'price',
                render: (data, type, row) =>
                    `<div class="flex items-center gap-1">
                        <span class="font-semibold text-gray-800" id="price-display-${row.id}">${data}</span>
                        <button class="btn-edit-price text-gray-300 hover:text-yellow-500 transition-colors ml-1"
                                data-listing-id="${row.id}" data-price="${row.price_raw / 100}" title="تعديل السعر">
                            ✏️
                        </button>
                    </div>`,
            },
            {
                data: 'available_stock',
                className: 'text-center',
                render: (data) => data,
            },
            {
                data: 'total_sold',
                className: 'text-center text-xs text-gray-600',
            },
            {
                data: 'rating_avg',
                className: 'text-center text-xs text-gray-600',
                render: (data) => data !== '—' ? `${data} ★` : '—',
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) =>
                    `<a href="${row.show_url}" class="text-xs text-blue-600 hover:underline">تفاصيل</a>`,
            },
        ],
        order: [[7, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/ar.json',
            emptyTable: 'لا توجد قوائم',
            processing: '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin"></div></div>',
        },
        dom: "<'flex items-center justify-between mb-4'<'text-sm text-gray-500'i><'flex gap-2'p>>t<'flex items-center justify-between mt-4'<'text-sm text-gray-500'i><'flex gap-2'p>>",
        pageLength: 25,
        searching: false,
    });

    // Row select
    $(tableEl).on('change', '.row-select', function () {
        const id = this.dataset.id;
        if (this.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        updateBulkActions();
    });

    document.getElementById('select-all-listings')?.addEventListener('change', (e) => {
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.checked = e.target.checked;
            const id = cb.dataset.id;
            if (e.target.checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkActions();
    });

    // Price edit from table
    $(tableEl).on('click', '.btn-edit-price', function () {
        const id = this.dataset.listingId;
        const price = this.dataset.price;
        document.getElementById('price-listing-id').value = id;
        document.getElementById('price-input').value = price;
        hideError('price-error');
        showModal('price-modal');
    });

    // Reload on search
    let searchDebounce = null;
    document.getElementById('listing-search')?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => listingsTable?.ajax.reload(), 400);
    });
}

function updateBulkActions() {
    const container = document.getElementById('bulk-actions');
    const countEl = document.getElementById('bulk-count');
    if (!container) return;
    const count = selectedIds.size;
    if (count > 0) {
        container.classList.remove('hidden');
        if (countEl) countEl.textContent = `${count} محدد`;
    } else {
        container.classList.add('hidden');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Inline Price Edit Modal (index page)
// ─────────────────────────────────────────────────────────────────────────────

function initPriceModal() {
    const modal = document.getElementById('price-modal');
    const form = document.getElementById('price-form');
    if (!modal || !form) return;

    document.getElementById('price-modal-close')?.addEventListener('click', () => hideModal('price-modal'));
    document.getElementById('price-cancel-btn')?.addEventListener('click', () => hideModal('price-modal'));
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal('price-modal'); });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError('price-error');
        const listingId = document.getElementById('price-listing-id').value;
        const price = document.getElementById('price-input').value;
        const submitBtn = form.querySelector('[type=submit]');
        submitBtn.disabled = true;

        const url = window.location.origin + '/listings/' + listingId + '/update-price';
        const { ok, data } = await postJson(url, { price });
        if (ok) {
            hideModal('price-modal');
            toast(data.message ?? 'تم تحديث السعر.');
            // Update the displayed price in the table row
            const displayEl = document.getElementById(`price-display-${listingId}`);
            if (displayEl) displayEl.textContent = data.price_formatted;
            listingsTable?.ajax.reload(null, false);
        } else {
            showError('price-error', data.message ?? 'حدث خطأ.');
        }
        submitBtn.disabled = false;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Product Search  (create page)
// ─────────────────────────────────────────────────────────────────────────────

function initProductSearch() {
    const searchInput = document.getElementById('product-search-input');
    const resultsDiv = document.getElementById('product-search-results');
    const cfg = window.LISTINGS_CREATE;
    if (!searchInput || !resultsDiv || !cfg) return;

    let debounceTimer = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const q = searchInput.value.trim();
            if (q.length < 2) {
                resultsDiv.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">ابدأ الكتابة للبحث...</p>';
                return;
            }

            resultsDiv.innerHTML = '<p class="text-xs text-gray-400 text-center py-4 animate-pulse">جاري البحث...</p>';

            const res = await fetch(`${cfg.productSearchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            const data = await res.json();

            if (!data.length) {
                resultsDiv.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">لا توجد نتائج مطابقة.</p>';
                return;
            }

            resultsDiv.innerHTML = data.map(product => {
                const img = product.image_url
                    ? `<img src="${product.image_url}" class="w-10 h-10 rounded-lg object-cover shrink-0">`
                    : `<div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 text-gray-400 text-xs">📦</div>`;

                const variants = product.variants.map(v => {
                    const disabled = v.already_listed;
                    return `<button type="button"
                        class="variant-select-btn text-xs px-2 py-1 rounded-lg border transition-colors ${disabled ? 'border-gray-100 text-gray-300 cursor-not-allowed' : 'border-gray-200 text-gray-600 hover:border-yellow-400 hover:text-yellow-600'}"
                        data-product-id="${product.id}"
                        data-product-name="${escapeHtml(product.name)}"
                        data-variant-id="${v.id}"
                        data-variant-name="${escapeHtml(v.variant_name)}"
                        data-sku="${escapeHtml(v.sku)}"
                        data-image="${product.image_url || ''}"
                        ${disabled ? 'disabled title="قائمة موجودة بالفعل"' : ''}>
                        ${escapeHtml(v.variant_name)}${disabled ? ' ✓' : ''}
                    </button>`;
                }).join('');

                return `<div class="flex items-start gap-3 p-3 rounded-xl border border-transparent hover:border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                    ${img}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 leading-tight">${escapeHtml(product.name)}</p>
                        ${product.model ? `<p class="text-xs text-gray-400">${escapeHtml(product.model)}</p>` : ''}
                        <div class="flex flex-wrap gap-1.5 mt-2">${variants}</div>
                    </div>
                </div>`;
            }).join('');

            // Bind variant select
            resultsDiv.querySelectorAll('.variant-select-btn:not([disabled])').forEach(btn => {
                btn.addEventListener('click', () => selectVariant(btn.dataset));
            });
        }, 350);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function selectVariant(data) {
    // Fill hidden input
    document.getElementById('form-product-variant-id').value = data.variantId;

    // Update selected product display
    const imgEl = document.getElementById('selected-img');
    if (imgEl) {
        imgEl.innerHTML = data.image
            ? `<img src="${data.image}" class="w-full h-full object-cover">`
            : `<div class="w-full h-full flex items-center justify-center text-gray-300 text-lg">📦</div>`;
    }
    const nameEl = document.getElementById('selected-product-name');
    const variantEl = document.getElementById('selected-variant-name');
    const skuEl = document.getElementById('selected-sku');
    if (nameEl) nameEl.textContent = data.productName;
    if (variantEl) variantEl.textContent = data.variantName;
    if (skuEl) skuEl.textContent = `SKU: ${data.sku}`;

    // Show form, hide placeholder
    document.getElementById('listing-form-placeholder')?.classList.add('hidden');
    document.getElementById('listing-form-container')?.classList.remove('hidden');
}

function initChangeProduct() {
    document.getElementById('change-product-btn')?.addEventListener('click', () => {
        document.getElementById('listing-form-placeholder')?.classList.remove('hidden');
        document.getElementById('listing-form-container')?.classList.add('hidden');
        document.getElementById('form-product-variant-id').value = '';
        document.getElementById('product-search-input').value = '';
        document.getElementById('product-search-results').innerHTML =
            '<p class="text-xs text-gray-400 text-center py-6">ابدأ الكتابة للبحث...</p>';
        document.getElementById('product-search-input').focus();
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Create Form Submit (create page)
// ─────────────────────────────────────────────────────────────────────────────

async function loadWarehousesByCountry(countryId) {
    const cfg = window.LISTINGS_CREATE;
    const select = document.querySelector('select[name="warehouse_id"]');
    if (!select || !cfg) return;

    select.disabled = true;
    select.innerHTML = '<option value="">جاري التحميل...</option>';

    if (!countryId) {
        select.innerHTML = '<option value="">اختر المستودع...</option>';
        select.disabled = false;
        return;
    }

    try {
        const res = await fetch(`${cfg.warehousesByCountryUrl}?country_id=${encodeURIComponent(countryId)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
        const warehouses = await res.json();
        if (!warehouses.length) {
            select.innerHTML = '<option value="">لا توجد مستودعات في هذا البلد</option>';
        } else {
            const typeLabel = (t) => t === 'vendor' ? 'مستودع خاص' : t === 'platform' ? 'مستودع المنصة' : t;
            select.innerHTML = '<option value="">اختر المستودع...</option>' +
                warehouses.map(w =>
                    `<option value="${w.id}">${escapeHtml(w.name)}${w.code ? ` (${escapeHtml(w.code)})` : ''} — ${typeLabel(w.type)}</option>`
                ).join('');
        }
    } catch {
        select.innerHTML = '<option value="">خطأ في تحميل المستودعات</option>';
    }
    select.disabled = false;
}

function initCreateForm() {
    const form = document.getElementById('listing-create-form');
    const cfg = window.LISTINGS_CREATE;
    if (!form || !cfg) return;

    // Reload warehouses when country changes
    const countrySelect = form.querySelector('select[name="country_id"]');
    if (countrySelect) {
        countrySelect.addEventListener('change', () => loadWarehousesByCountry(countrySelect.value));
        // Trigger immediately to populate for the pre-selected vendor country
        if (countrySelect.value) loadWarehousesByCountry(countrySelect.value);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError('create-error');

        const variantId = document.getElementById('form-product-variant-id').value;
        if (!variantId) {
            showError('create-error', 'يرجى اختيار منتج ونسخة أولاً.');
            return;
        }

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.product_variant_id = variantId;

        const submitBtn = document.getElementById('create-submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري الإنشاء...';

        const res = await fetch(cfg.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (res.ok && data.success) {
            toast(data.message ?? 'تم إنشاء القائمة بنجاح.');
            setTimeout(() => { window.location.href = data.redirect; }, 1000);
        } else {
            showError('create-error', data.message ?? 'حدث خطأ. يرجى التحقق من البيانات.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'إنشاء القائمة';
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Listing Detail Page — Price + Status + Adjust Stock
// ─────────────────────────────────────────────────────────────────────────────

function initDetailPage() {
    const cfg = window.LISTING_DETAIL;
    if (!cfg) return;

    // ── Update Price Modal ────────────────────────────────────────────────────
    document.getElementById('btn-update-price')?.addEventListener('click', () => {
        hideError('price-update-error');
        showModal('update-price-modal');
    });
    document.getElementById('price-modal-close-detail')?.addEventListener('click', () => hideModal('update-price-modal'));
    document.getElementById('price-close-btn')?.addEventListener('click', () => hideModal('update-price-modal'));

    document.getElementById('price-update-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError('price-update-error');
        const price = document.getElementById('new-price-input').value;
        const submitBtn = e.target.querySelector('[type=submit]');
        submitBtn.disabled = true;

        const { ok, data } = await postJson(cfg.updatePriceUrl, { price });
        if (ok) {
            hideModal('update-price-modal');
            toast(data.message ?? 'تم تحديث السعر.');
            const displayEl = document.getElementById('display-price');
            if (displayEl) displayEl.textContent = data.price_formatted;
        } else {
            showError('price-update-error', data.message ?? 'حدث خطأ.');
        }
        submitBtn.disabled = false;
    });

    // ── Toggle Status ─────────────────────────────────────────────────────────
    document.getElementById('btn-toggle-status')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-toggle-status');
        btn.disabled = true;
        const { ok, data } = await postJson(cfg.toggleStatusUrl, {});
        if (ok) {
            toast(data.message ?? 'تم تغيير الحالة.');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            toast(data.message ?? 'حدث خطأ.', 'error');
            btn.disabled = false;
        }
    });

    // ── Adjust Stock Buttons ──────────────────────────────────────────────────
    document.querySelectorAll('.btn-adjust-stock').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('adjust-inv-id').value = btn.dataset.invId;
            document.getElementById('adjust-warehouse-name').textContent = btn.dataset.warehouse;
            document.getElementById('adjust-current-qty').textContent = btn.dataset.onHand;
            document.getElementById('adjust-form')?.reset();
            document.getElementById('adjust-inv-id').value = btn.dataset.invId;
            hideError('adjust-error');
            showModal('adjust-stock-modal');
        });
    });

    document.getElementById('adjust-modal-close')?.addEventListener('click', () => hideModal('adjust-stock-modal'));
    document.getElementById('adjust-cancel-btn')?.addEventListener('click', () => hideModal('adjust-stock-modal'));

    document.getElementById('adjust-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError('adjust-error');
        const formData = new FormData(e.target);
        const payload = Object.fromEntries(formData.entries());
        const submitBtn = e.target.querySelector('[type=submit]');
        submitBtn.disabled = true;

        const { ok, data } = await postJson(cfg.adjustStockUrl, payload);
        if (ok) {
            hideModal('adjust-stock-modal');
            toast(data.message ?? 'تم تعديل المخزون.');
            // Update on-hand and available displays in the table
            const invId = payload.warehouse_inventory_id;
            const onHandEl = document.getElementById(`onhand-${invId}`);
            const availEl = document.getElementById(`avail-${invId}`);
            if (onHandEl) onHandEl.textContent = data.new_quantity;
            if (availEl) availEl.textContent = data.new_quantity;
        } else {
            showError('adjust-error', data.message ?? 'حدث خطأ.');
        }
        submitBtn.disabled = false;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Boot
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initListingsDataTable();
    initPriceModal();
    initProductSearch();
    initChangeProduct();
    initCreateForm();
    initDetailPage();
});
