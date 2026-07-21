/**
 * resources/js/admin/secret-promotions.js
 *
 * Secret Promotions index page:
 *   - Server-side DataTable
 *   - Commission split calculator (live preview + visual bar)
 *   - Vendor → listings AJAX loader
 *   - Create / Edit modal via $.fn.modal plugin
 *   - Toggle status, expire, duplicate actions
 */

import Chart from 'chart.js/auto';
import DataTable from 'datatables.net';

/* ─── Constants ──────────────────────────────────────────────────────────── */
const MIN_FLOOR = window.MIN_COMMISSION_FLOOR ?? 5.0;

/* ─── Helpers ────────────────────────────────────────────────────────────── */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function ajax(method, url, data = {}) {
    return $.ajax({
        url,
        type: method,
        data,
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function postJson(url, data = {}) {
    return $.ajax({
        url,
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function fmtMoney(val) {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function withLoading($btn, text, fn) {
    const orig = $btn.text();
    $btn.prop('disabled', true).text(text ?? window.TRANSLATIONS?.savingEllipsis ?? 'Loading…');
    return Promise.resolve(fn()).finally(() => $btn.prop('disabled', false).text(orig));
}

const Toast = window.Toast || { success: console.log, error: console.warn, info: console.log };

/* ─── DataTable ──────────────────────────────────────────────────────────── */
let table = null;

function initTable() {
    const el = document.getElementById('secret-promos-table');
    if (!el) return;

    table = new DataTable('#secret-promos-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes?.datatable ?? '/marketers-secret-promotions/datatable',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.status   = document.getElementById('filter-status')?.value ?? '';
                d.vendor   = document.getElementById('filter-vendor')?.value ?? '';
                d.marketer = document.getElementById('filter-marketer')?.value ?? '';
                d.expiry   = document.getElementById('filter-expiry')?.value ?? '';
            },
        },
        columns: [
            { data: 'product' },
            { data: 'vendor' },
            { data: 'marketer',      orderable: false },
            { data: 'listing_price' },
            { data: 'product_value', orderable: false, className: 'bg-amber-50 text-amber-800' },
            { data: 'margin_pct',    orderable: false, className: 'bg-amber-50 text-amber-700' },
            { data: 'total_pct' },
            { data: 'marketer_pct' },
            { data: 'admin_pct',     orderable: false, className: 'bg-amber-50 text-amber-700 font-bold' },
            { data: 'conversions',   orderable: false },
            { data: 'valid_until' },
            { data: 'status',        orderable: false },
            { data: 'actions',       orderable: false },
        ],
        order: [[0, 'asc']],
        columnDefs: [
            { targets: [4, 5, 8], className: 'bg-amber-50 text-amber-800' },
        ],
    });
}

/* ─── Filters ────────────────────────────────────────────────────────────── */
function initFilters() {
    ['filter-status', 'filter-vendor', 'filter-marketer', 'filter-expiry'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => table?.ajax.reload());
    });

    document.getElementById('clear-filters-btn')?.addEventListener('click', () => {
        ['filter-status', 'filter-vendor', 'filter-marketer', 'filter-expiry'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        table?.ajax.reload();
    });
}

/* ─── Commission split calculator ───────────────────────────────────────── */
let listingPriceCents = 0;

function recalculateSplit() {
    const productVal = parseFloat(document.getElementById('product-value-input')?.value) || 0;
    const totalPct   = parseFloat(document.getElementById('total-pct-input')?.value) || 0;
    const marketerPct = parseFloat(document.getElementById('marketer-pct-input')?.value) || 0;
    const adminPct   = Math.max(0, parseFloat((totalPct - marketerPct).toFixed(2)));

    // Update admin auto-display
    const adminDisp = document.getElementById('admin-pct-display');
    const adminHid  = document.getElementById('admin-pct-hidden');
    if (adminDisp) adminDisp.value = adminPct.toFixed(2) + ' %';
    if (adminHid) adminHid.value = adminPct;

    const price = listingPriceCents / 100;

    // Per-sale previews
    const totalPerSale    = price > 0 ? fmtMoney(price * totalPct / 100) : '—';
    const marketerPerSale = price > 0 ? fmtMoney(price * marketerPct / 100) : '—';
    const adminPerSale    = price > 0 ? fmtMoney(price * adminPct / 100) : '—';

    const tPreview = document.getElementById('total-per-sale-preview');
    const mPreview = document.getElementById('marketer-per-sale-preview');
    const aPreview = document.getElementById('admin-per-sale-preview');
    if (tPreview) tPreview.textContent = price > 0 ? `= ${totalPerSale} per sale` : '';
    if (mPreview) mPreview.textContent = price > 0 ? `= ${marketerPerSale} per sale` : '';
    if (aPreview) aPreview.textContent = `= ${adminPerSale}`;

    // Visual split bar (marketer + admin as % of 100, not of total)
    const mBarPct = totalPct > 0 ? (marketerPct / 100 * 100).toFixed(1) : 0;
    const aBarPct = totalPct > 0 ? (adminPct / 100 * 100).toFixed(1) : 0;

    const mBar = document.getElementById('split-bar-marketer');
    const aBar = document.getElementById('split-bar-admin');
    if (mBar) {
        mBar.style.width = mBarPct + '%';
        mBar.textContent = mBarPct > 5 ? marketerPct.toFixed(1) + '%' : '';
    }
    if (aBar) {
        aBar.style.width = aBarPct + '%';
        aBar.textContent = aBarPct > 5 ? adminPct.toFixed(1) + '%' : '';
    }

    // Margin analysis
    const marginEl = document.getElementById('margin-analysis');
    if (marginEl && price > 0 && productVal > 0) {
        marginEl.classList.remove('hidden');
        const marginAmt   = price - productVal;
        const marginPct   = ((marginAmt / price) * 100).toFixed(1);
        const vendorNet   = price - (price * totalPct / 100);

        document.getElementById('ma-price').textContent     = fmtMoney(price);
        document.getElementById('ma-cost').textContent      = fmtMoney(productVal);
        document.getElementById('ma-margin').textContent    = `${fmtMoney(marginAmt)} (${marginPct}%)`;
        document.getElementById('ma-marketer').textContent  = fmtMoney(price * marketerPct / 100);
        document.getElementById('ma-admin').textContent     = fmtMoney(price * adminPct / 100);
        document.getElementById('ma-vendor-net').textContent = fmtMoney(vendorNet);
    } else if (marginEl) {
        marginEl.classList.add('hidden');
    }

    // Validation errors
    validateSplit(totalPct, marketerPct, adminPct);
}

function validateSplit(totalPct, marketerPct, adminPct) {
    const errContainer = document.getElementById('split-errors');
    if (!errContainer) return;

    const errors = [];

    const T = window.TRANSLATIONS || {};
    if (totalPct <= 0)       errors.push(T.totalCommissionError || 'Total commission must be greater than 0%.');
    if (marketerPct <= 0)    errors.push(T.marketerShareError || 'Marketer share must be greater than 0%.');
    if (adminPct < 0)        errors.push(T.adminShareNegativeError || 'Admin share cannot be negative (marketer share exceeds total).');
    if (marketerPct < MIN_FLOOR)
        errors.push((T.marketerShareMinError || `Marketer share must be at least {min}% (platform minimum).`).replace('{min}', MIN_FLOOR));
    if (totalPct > 0 && marketerPct >= totalPct)
        errors.push(T.marketerShareLessThanTotalError || 'Marketer share must be less than the total commission.');

    if (errors.length) {
        errContainer.innerHTML = errors.map(e => `<p class="text-xs text-red-600">⚠ ${e}</p>`).join('');
        errContainer.classList.remove('hidden');
    } else {
        errContainer.innerHTML = '';
        errContainer.classList.add('hidden');
    }
}

function bindCalculatorInputs() {
    ['product-value-input', 'total-pct-input', 'marketer-pct-input'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', recalculateSplit);
    });
}

/* ─── Vendor → Listings loader ───────────────────────────────────────────── */
function loadListingsForVendor(vendorId, preselect = null) {
    const T = window.TRANSLATIONS || {};
    const $select = $('#listing-select');
    if (!vendorId) {
        $select.html(`<option value="">${T.selectVendorFirst || 'Select vendor first…'}</option>`).prop('disabled', true);
        resetListingPreview();
        return;
    }

    $select.html(`<option value="">${T.loadingListings || 'Loading listings…'}</option>`).prop('disabled', true);

    $.get('/marketers-secret-promotions/listings/by-vendor', { vendor_id: vendorId })
        .done(function (data) {
            const listings = data.listings ?? [];
            if (!listings.length) {
                $select.html(`<option value="">${T.noListingsFound || 'No listings found for this vendor'}</option>`);
                return;
            }
            let opts = `<option value="">${T.selectListing || 'Select listing…'}</option>`;
            listings.forEach(l => {
                opts += `<option value="${l.id}" data-price="${l.price}" data-name="${l.name}" data-img="${l.image ?? ''}" data-currency="${l.currency ?? ''}">${l.name} — ${fmtMoney(l.price / 100)}</option>`;
            });
            $select.html(opts).prop('disabled', false);

            if (preselect) {
                $select.val(preselect).trigger('change');
            }
        })
        .fail(function () {
            $select.html(`<option value="">${T.failedToLoadListings || 'Failed to load listings'}</option>`);
            Toast.error(T.couldNotLoadListings || 'Could not load vendor listings.');
        });
}

function updateListingPreview(option) {
    const name  = $(option).data('name');
    const price = $(option).data('price');
    const img   = $(option).data('img');
    listingPriceCents = parseInt(price) || 0;

    if (name) {
        $('#listing-preview').css('display', 'flex');
        $('#listing-preview-name').text(name);
        $('#listing-preview-price').text(fmtMoney(listingPriceCents / 100));
        $('#listing-preview-img').attr('src', img || '').toggle(!!img);
    } else {
        resetListingPreview();
    }

    // Update currency badge from the listing's own currency
    const currencyBadge = document.getElementById('product-value-currency');
    if (currencyBadge) currencyBadge.textContent = $(option).data('currency') || '—';

    recalculateSplit();
}

function resetListingPreview() {
    listingPriceCents = 0;
    $('#listing-preview').hide();
    recalculateSplit();
}

/* ─── Create modal ───────────────────────────────────────────────────────── */
function resetCreateForm() {
    document.getElementById('promo-id').value      = '';
    document.getElementById('vendor-select').value = '';
    document.getElementById('marketer-select').value = '';
    document.getElementById('valid-until-input').value = '';
    document.getElementById('product-value-input').value = '';
    document.getElementById('total-pct-input').value = '';
    document.getElementById('marketer-pct-input').value = '';
    document.getElementById('admin-pct-display').value = '0.00 %';
    document.getElementById('admin-pct-hidden').value = '0';
    const T = window.TRANSLATIONS || {};
    $('#listing-select').html(`<option value="">${T.selectVendorFirst || 'Select vendor first…'}</option>`).prop('disabled', true);
    resetListingPreview();
    document.getElementById('split-errors')?.classList.add('hidden');
    document.getElementById('margin-analysis')?.classList.add('hidden');

    const saveBtn = document.getElementById('promo-save-btn');
    if (saveBtn) saveBtn.textContent = T.saveSecretPromotion || 'Save secret promotion';

    document.getElementById('promo-modal-title').textContent = T.newSecretPromotionTitle || 'New Secret Promotion';
}

function openCreateModal() {
    resetCreateForm();
    $('#promo-modal').modal('open');
}

function initCreateModal() {
    // Open button
    document.getElementById('create-promo-btn')?.addEventListener('click', openCreateModal);

    // Vendor → listings cascade
    $(document).on('change', '#vendor-select', function () {
        loadListingsForVendor(this.value);
    });

    // Listing selection → preview
    $(document).on('change', '#listing-select', function () {
        const opt = this.options[this.selectedIndex];
        updateListingPreview(opt);
    });

    // Form submit (create)
    $(document).on('submit', '#promo-form', function (e) {
        e.preventDefault();

        const promoId     = document.getElementById('promo-id').value;
        const isEdit      = !!promoId;
        const totalPct    = parseFloat(document.getElementById('total-pct-input').value) || 0;
        const marketerPct = parseFloat(document.getElementById('marketer-pct-input').value) || 0;
        const adminPct    = parseFloat(document.getElementById('admin-pct-hidden').value) || 0;

        // Client-side guard
        if (marketerPct < MIN_FLOOR || marketerPct >= totalPct || adminPct < 0) {
            Toast.error(window.TRANSLATIONS?.fixSplitErrors || 'Please fix commission split errors before saving.');
            return;
        }

        const payload = {
            vendor_id:          document.getElementById('vendor-select').value,
            vendor_listing_id:  document.getElementById('listing-select').value,
            marketer_id:        document.getElementById('marketer-select').value || null,
            product_value:      parseFloat(document.getElementById('product-value-input').value) || 0,
            total_commission_pct: totalPct,
            marketer_share_pct: marketerPct,
            valid_until:        document.getElementById('valid-until-input').value || null,
        };

        const $btn = $('#promo-save-btn');
        const url  = isEdit
            ? `/marketers-secret-promotions/${promoId}`
            : '/marketers-secret-promotions';
        const method = isEdit ? 'PUT' : 'POST';

        withLoading($btn, window.TRANSLATIONS?.savingEllipsis || 'Saving…', () =>
            ajax(method, url, payload)
                .done(function (res) {
                    Toast.success(res.message ?? (window.TRANSLATIONS?.promotionSaved || 'Promotion saved successfully.'));
                    $('#promo-modal').modal('close');
                    table?.ajax.reload();
                })
                .fail(function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors ?? {};
                        Object.values(errors).flat().forEach(m => Toast.error(m));
                    } else {
                        Toast.error(xhr.responseJSON?.message ?? (window.TRANSLATIONS?.failedToSavePromotion || 'Failed to save promotion.'));
                    }
                })
        );
    });
}

/* ─── Row action handlers (via event delegation) ─────────────────────────── */
function initRowActions() {
    // Edit
    $(document).on('click', '.js-edit-promo', function () {
        const data = $(this).data();

        document.getElementById('promo-id').value             = data.id;
        document.getElementById('promo-modal-title').textContent = window.TRANSLATIONS?.editSecretPromotion || 'Edit Secret Promotion';
        document.getElementById('promo-save-btn').textContent = window.TRANSLATIONS?.updatePromotion || 'Update promotion';

        // Pre-fill vendor + trigger listing load
        const $vendorSel = $('#vendor-select');
        $vendorSel.val(data.vendorId ?? data.vendor_id);
        loadListingsForVendor(data.vendorId ?? data.vendor_id, data.listingId ?? data.listing_id);

        $('#marketer-select').val(data.marketerId ?? data.marketer_id ?? '');
        document.getElementById('valid-until-input').value    = data.validUntil ?? data.valid_until ?? '';
        document.getElementById('product-value-input').value  = data.productValue ?? data.product_value ?? '';
        document.getElementById('total-pct-input').value      = data.totalPct ?? data.total_pct ?? '';
        document.getElementById('marketer-pct-input').value   = data.marketerPct ?? data.marketer_pct ?? '';

        listingPriceCents = parseInt(data.listingPrice ?? data.listing_price) || 0;
        recalculateSplit();

        $('#promo-modal').modal('open');
    });

    // Toggle status (pause / resume)
    $(document).on('click', '.js-toggle-status', function () {
        const id     = $(this).data('id');
        const action = $(this).data('action');
        const $btn   = $(this);

        withLoading($btn, '…', () =>
            ajax('POST', `/marketers-secret-promotions/${id}/toggle-status`, { action })
                .done(function (res) {
                    Toast.success(res.message ?? (window.TRANSLATIONS?.statusUpdated || 'Status updated.'));
                    table?.ajax.reload();
                })
                .fail(function (xhr) {
                    Toast.error(xhr.responseJSON?.message ?? (window.TRANSLATIONS?.failedToUpdateStatus || 'Failed to update status.'));
                })
        );
    });

    // Expire
    $(document).on('click', '.js-expire-promo', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name') ?? (window.TRANSLATIONS?.thisPromotionFallback || 'this promotion');
        const $btn = $(this);

        window.confirmDialog({
            title: window.TRANSLATIONS?.forceExpireTitle || 'Force expire?',
            text: (window.TRANSLATIONS?.forceExpireText || `"{name}" will be permanently expired and can no longer be used.`).replace('{name}', name),
            confirmButtonText: window.TRANSLATIONS?.yesExpireIt || 'Yes, expire it',
            confirmButtonColor: '#dc2626',
        }).then(() => {
            withLoading($btn, '…', () =>
                ajax('POST', `/marketers-secret-promotions/${id}/expire`, {})
                    .done(function (res) {
                        Toast.success(res.message ?? (window.TRANSLATIONS?.promotionExpired || 'Promotion expired.'));
                        table?.ajax.reload();
                    })
                    .fail(function (xhr) {
                        Toast.error(xhr.responseJSON?.message ?? (window.TRANSLATIONS?.failedToExpire || 'Failed to expire.'));
                    })
            );
        }).catch(() => {});
    });

    // Duplicate
    $(document).on('click', '.js-duplicate-promo', function () {
        const id   = $(this).data('id');
        const $btn = $(this);

        withLoading($btn, '…', () =>
            ajax('POST', `/marketers-secret-promotions/${id}/duplicate`, {})
                .done(function (res) {
                    Toast.success(res.message ?? (window.TRANSLATIONS?.promotionDuplicated || 'Promotion duplicated.'));
                    table?.ajax.reload();
                })
                .fail(function (xhr) {
                    Toast.error(xhr.responseJSON?.message ?? (window.TRANSLATIONS?.failedToDuplicate || 'Failed to duplicate.'));
                })
        );
    });
}

/* ─── Bootstrap ──────────────────────────────────────────────────────────── */
$(function () {
    initTable();
    initFilters();
    bindCalculatorInputs();
    initCreateModal();
    initRowActions();
});
