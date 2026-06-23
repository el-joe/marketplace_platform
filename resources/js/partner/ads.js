import './app.js';
import { createPartnerTable } from './datatable.js';

// ─────────────────────────────────────────────────────────────────────────────
// Config (injected from Blade via window.ADS_CONFIG)
// ─────────────────────────────────────────────────────────────────────────────
const cfg = () => window.ADS_CONFIG || {};

// ─────────────────────────────────────────────────────────────────────────────
// Utilities
// ─────────────────────────────────────────────────────────────────────────────
function formatMoney(cents, currency = 'SAR') {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency }).format(cents / 100);
}

function formatNumber(n) {
    return new Intl.NumberFormat('ar-SA').format(n ?? 0);
}

function statusBadge(status) {
    const map = {
        draft:           { label: 'مسودة',           cls: 'bg-gray-100 text-gray-700' },
        pending_review:  { label: 'بانتظار المراجعة', cls: 'bg-amber-100 text-amber-700' },
        active:          { label: 'نشطة',             cls: 'bg-emerald-100 text-emerald-700' },
        paused:          { label: 'موقوفة',           cls: 'bg-blue-100 text-blue-700' },
        budget_exhausted:{ label: 'نفد الميزانية',    cls: 'bg-red-100 text-red-700' },
        ended:           { label: 'منتهية',           cls: 'bg-gray-100 text-gray-500' },
        rejected:        { label: 'مرفوضة',           cls: 'bg-red-100 text-red-700' },
    };
    const s = map[status] || { label: status, cls: 'bg-gray-100 text-gray-600' };
    return `<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${s.cls}">${s.label}</span>`;
}

function typeBadge(type) {
    return type === 'cpc'
        ? `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700">CPC</span>`
        : `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700">CPM</span>`;
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', ...options.headers },
        ...options,
    });
    const json = await res.json();
    if (!res.ok) throw json;
    return json;
}

// ─────────────────────────────────────────────────────────────────────────────
// Campaign List (index page)
// ─────────────────────────────────────────────────────────────────────────────
let currentPage = 1;
let currentStatus = '';
let campaigns = [];

async function loadCampaigns(page = 1) {
    const params = new URLSearchParams({ page });
    if (currentStatus) params.set('status', currentStatus);

    const tbody = document.getElementById('campaigns-tbody');
    const emptyState = document.getElementById('empty-state');
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-sm text-gray-400">جاري التحميل...</td></tr>`;

    try {
        const data = await apiFetch(`${cfg().listUrl}?${params}`);
        campaigns = data.data?.items || [];
        renderCampaigns(campaigns);
        renderPagination(data.data?.meta);
    } catch (e) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-sm text-red-500">تعذّر تحميل الحملات.</td></tr>`;
    }
}

function renderCampaigns(items) {
    const tbody = document.getElementById('campaigns-tbody');
    const emptyState = document.getElementById('empty-state');
    if (!tbody) return;

    if (!items.length) {
        tbody.innerHTML = '';
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }
    if (emptyState) emptyState.classList.add('hidden');

    tbody.innerHTML = items.map(c => {
        const spentPct = c.budget_total > 0 ? Math.min(100, (c.budget_spent_total / c.budget_total) * 100) : 0;
        const todaySpentPct = c.budget_daily && c.budget_daily > 0
            ? Math.min(100, (c.budget_spent_today / c.budget_daily) * 100) : null;

        const qs = c.quality_score != null ? c.quality_score.toFixed(1) : '—';
        const qsColor = c.quality_score >= 7 ? 'text-emerald-600' : c.quality_score >= 4 ? 'text-amber-600' : 'text-red-500';

        return `<tr class="hover:bg-gray-50 cursor-pointer" data-href="${cfg().showBaseUrl}/${c.id}">
            <td class="px-4 py-3">
                <a href="${cfg().showBaseUrl}/${c.id}" class="font-medium text-gray-900 hover:text-primary-600 line-clamp-1">${c.name}</a>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">${typeBadge(c.type)}</td>
            <td class="px-4 py-3 whitespace-nowrap">${statusBadge(c.status)}</td>
            <td class="px-4 py-3 min-w-[140px]">
                <div class="text-xs text-gray-500 mb-1">${formatMoney(c.budget_spent_total * 100)} / ${formatMoney(c.budget_total * 100)}</div>
                <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-primary-500 transition-all" style="width:${spentPct.toFixed(1)}%"></div>
                </div>
                ${todaySpentPct !== null ? `<div class="mt-1 text-xs text-gray-400">اليوم: ${todaySpentPct.toFixed(0)}%</div>` : ''}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm font-semibold ${qsColor}">${qs}</span>
                <span class="text-xs text-gray-400"> / 10</span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm">
                <span class="text-gray-900 font-medium">${formatNumber(c.today_clicks)}</span>
                <span class="text-gray-400"> / ${formatNumber(c.today_impressions)}</span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
                <a href="${cfg().showBaseUrl}/${c.id}" class="text-xs text-primary-600 hover:underline">عرض</a>
            </td>
        </tr>`;
    }).join('');

    tbody.querySelectorAll('tr[data-href]').forEach(row => {
        row.addEventListener('click', e => {
            if (!e.target.closest('a')) window.location.href = row.dataset.href;
        });
    });
}

function renderPagination(meta) {
    const el = document.getElementById('pagination');
    if (!el || !meta) return;

    if (meta.last_page <= 1) { el.innerHTML = ''; return; }

    let html = `<div class="flex items-center gap-2 text-sm">`;
    if (meta.current_page > 1) {
        html += `<button onclick="gotoPage(${meta.current_page - 1})" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">السابق</button>`;
    }
    html += `<span class="text-gray-500">صفحة ${meta.current_page} من ${meta.last_page}</span>`;
    if (meta.current_page < meta.last_page) {
        html += `<button onclick="gotoPage(${meta.current_page + 1})" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">التالي</button>`;
    }
    html += `</div>`;
    el.innerHTML = html;
}

window.gotoPage = function (page) {
    currentPage = page;
    loadCampaigns(page);
};

// ─────────────────────────────────────────────────────────────────────────────
// Create Campaign Wizard (vanilla JS)
// ─────────────────────────────────────────────────────────────────────────────
const STEP_LABELS = ['الأساسيات', 'الاستهداف', 'المنتجات', 'الميزانية', 'مراجعة'];
const TOTAL_STEPS = 5;

let wizStep = 1;
let wizSubmitting = false;
let wizListings = [];
let wizListingsLoaded = false;
let wizCategories = [];
let wizCategoriesLoaded = false;
let wizSelectedProducts = [];
let wizSelectedCategories = []; // [{category_id, bid_override}]
let wizKeywords = [{ keyword: '', match_type: 'broad', bid_override: '' }];

function el(id) { return document.getElementById(id); }

function wizShow(id, displayType = 'block') {
    const e = el(id);
    if (e) e.style.display = displayType;
}
function wizHide(id) {
    const e = el(id);
    if (e) e.style.display = 'none';
}

function wizSetError(msg) {
    const e = el('wiz-error');
    if (!e) return;
    if (msg) { e.textContent = msg; e.style.display = 'block'; }
    else e.style.display = 'none';
}

function wizGetType() {
    return document.querySelector('input[name="wiz-type"]:checked')?.value || 'cpc';
}
function wizGetTargeting() {
    return el('wiz-targeting')?.value || 'auto';
}
function wizNeedsKeywords() {
    return ['keyword', 'mixed'].includes(wizGetTargeting());
}
function wizNeedsCategories() {
    return ['category', 'mixed'].includes(wizGetTargeting());
}

function wizRenderProgress() {
    const container = el('wiz-progress');
    if (!container) return;
    container.innerHTML = STEP_LABELS.map((label, i) => {
        const num = i + 1;
        const done = num < wizStep;
        const active = num === wizStep;
        const dotCls = done
            ? 'bg-primary-500 text-white'
            : active
                ? 'bg-primary-600 text-white ring-4 ring-primary-100'
                : 'bg-gray-100 text-gray-400';
        const lineBefore = i === 0 ? 'bg-transparent' : (i < wizStep ? 'bg-primary-500' : 'bg-gray-200');
        const lineAfter = i === STEP_LABELS.length - 1 ? 'bg-transparent' : (num < wizStep ? 'bg-primary-500' : 'bg-gray-200');
        return `<div class="flex-1 flex flex-col items-center">
            <div class="flex items-center w-full">
                <div class="h-0.5 flex-1 ${lineBefore}"></div>
                <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${dotCls}">${done ? '✓' : num}</div>
                <div class="h-0.5 flex-1 ${lineAfter}"></div>
            </div>
            <span class="mt-1 text-xs text-gray-500 whitespace-nowrap hidden sm:block">${label}</span>
        </div>`;
    }).join('');
}

function wizShowStep(step) {
    for (let i = 1; i <= TOTAL_STEPS; i++) {
        const s = el(`wiz-step-${i}`);
        if (s) s.style.display = i === step ? 'block' : 'none';
    }
    el('wiz-step-label').textContent = step;
    wizRenderProgress();

    // prev button
    if (step > 1) wizShow('wiz-prev', 'inline-flex'); else wizHide('wiz-prev');
    const spacer = el('wiz-prev-spacer');
    if (spacer) spacer.style.display = step > 1 ? 'none' : 'inline';

    // next / submit
    if (step < TOTAL_STEPS) {
        wizShow('wiz-next', 'inline-flex');
        wizHide('wiz-submit');
    } else {
        wizHide('wiz-next');
        wizShow('wiz-submit', 'inline-flex');
        wizRenderReview();
    }

    // step 2 targeting sections
    if (step === 2) wizRenderTargetingUI();

    // step 3 products
    if (step === 3) wizRenderProducts();

    // bid label
    const bidLabel = el('wiz-bid-label');
    if (bidLabel) bidLabel.textContent = wizGetType() === 'cpc' ? 'مزايدة النقرة (ر.س)' : 'مزايدة الألف مشاهدة (ر.س)';
}

function wizRenderTargetingUI() {
    const targeting = wizGetTargeting();
    const autoMsg = el('wiz-auto-msg');
    const kwSection = el('wiz-keywords-section');
    const catSection = el('wiz-categories-section');

    if (autoMsg) autoMsg.style.display = targeting === 'auto' ? 'block' : 'none';
    if (kwSection) kwSection.style.display = wizNeedsKeywords() ? 'block' : 'none';
    if (catSection) catSection.style.display = wizNeedsCategories() ? 'block' : 'none';

    if (wizNeedsKeywords()) wizRenderKeywords();
    if (wizNeedsCategories()) wizLoadAndRenderCategories();
}

function wizRenderKeywords() {
    const list = el('wiz-keywords-list');
    if (!list) return;
    list.innerHTML = wizKeywords.map((kw, i) => `
        <div class="flex gap-2 items-start mb-2" data-kw-row="${i}">
            <input type="text" value="${kw.keyword}" placeholder="كلمة مفتاحية..."
                class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                onchange="wizUpdateKeyword(${i},'keyword',this.value)">
            <select class="rounded-lg border border-gray-200 px-2 py-2 text-sm"
                onchange="wizUpdateKeyword(${i},'match_type',this.value)">
                <option value="broad" ${kw.match_type === 'broad' ? 'selected' : ''}>واسع</option>
                <option value="phrase" ${kw.match_type === 'phrase' ? 'selected' : ''}>عبارة</option>
                <option value="exact" ${kw.match_type === 'exact' ? 'selected' : ''}>مطابق</option>
            </select>
            <input type="number" value="${kw.bid_override}" placeholder="مزايدة اختياري"
                class="w-28 rounded-lg border border-gray-200 px-2 py-2 text-sm" step="0.01" min="0.01"
                onchange="wizUpdateKeyword(${i},'bid_override',this.value)">
            ${wizKeywords.length > 1 ? `<button type="button" onclick="wizRemoveKeyword(${i})" class="text-gray-300 hover:text-red-400 pt-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>` : '<span class="w-6"></span>'}
        </div>`).join('');
}

window.wizUpdateKeyword = function(i, field, val) { wizKeywords[i][field] = val; };
window.wizRemoveKeyword = function(i) { wizKeywords.splice(i, 1); wizRenderKeywords(); };

async function wizLoadAndRenderCategories() {
    if (wizCategoriesLoaded) { wizRenderCategories(); return; }
    wizShow('wiz-categories-loading');
    wizHide('wiz-categories-grid');
    try {
        const data = await apiFetch(cfg().categoriesUrl);
        wizCategories = data.data || [];
        wizCategoriesLoaded = true;
        wizRenderCategories();
    } catch {
        wizCategories = [];
    }
}

function wizRenderCategories() {
    wizHide('wiz-categories-loading');
    const grid = el('wiz-categories-grid');
    if (!grid) return;
    grid.style.display = 'grid';
    grid.innerHTML = wizCategories.map(cat => {
        const selected = wizSelectedCategories.some(c => c.category_id === cat.id);
        return `<label class="flex items-center gap-2 rounded-lg border-2 p-2.5 cursor-pointer text-sm transition-colors ${selected ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-100 hover:border-gray-200'}"
            onclick="wizToggleCategory(${cat.id}, this)">
            <input type="checkbox" class="sr-only" ${selected ? 'checked' : ''}>
            <span class="truncate">${cat.name_ar || cat.name_en}</span>
        </label>`;
    }).join('');
}

window.wizToggleCategory = function(id, labelEl) {
    const idx = wizSelectedCategories.findIndex(c => c.category_id === id);
    if (idx === -1) {
        wizSelectedCategories.push({ category_id: id, bid_override: '' });
        labelEl.className = labelEl.className.replace('border-gray-100 hover:border-gray-200', 'border-primary-500 bg-primary-50 text-primary-700');
    } else {
        wizSelectedCategories.splice(idx, 1);
        labelEl.className = labelEl.className.replace('border-primary-500 bg-primary-50 text-primary-700', 'border-gray-100 hover:border-gray-200');
    }
};

async function wizRenderProducts() {
    if (wizListingsLoaded) { wizDisplayProducts(); return; }
    wizShow('wiz-products-loading');
    wizHide('wiz-products-empty');
    wizHide('wiz-products-grid');
    try {
        const data = await apiFetch(cfg().listingsUrl);
        wizListings = data.data?.items || [];
        wizListingsLoaded = true;
        wizDisplayProducts();
    } catch {
        wizListings = [];
        wizListingsLoaded = true;
        wizDisplayProducts();
    }
}

function wizDisplayProducts() {
    wizHide('wiz-products-loading');
    if (!wizListings.length) { wizShow('wiz-products-empty'); return; }
    const grid = el('wiz-products-grid');
    if (!grid) return;
    grid.style.display = 'grid';
    grid.innerHTML = wizListings.map(listing => {
        const id = listing.product_variant_id;
        const selected = wizSelectedProducts.includes(id);
        const img = listing.primary_image
            ? `<img src="${listing.primary_image}" class="h-full w-full object-cover" alt="">`
            : `<svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>`;
        return `<label class="flex items-center gap-3 rounded-xl border-2 p-3 cursor-pointer transition-colors ${selected ? 'border-primary-500 bg-primary-50' : 'border-gray-100 hover:border-gray-200'}"
            onclick="wizToggleProduct(${id}, this)">
            <input type="checkbox" class="sr-only" ${selected ? 'checked' : ''}>
            <div class="shrink-0 h-10 w-10 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center">${img}</div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-800 line-clamp-1">${listing.name_ar || listing.name_en || listing.sku}</div>
                <div class="text-xs text-gray-500">${listing.sku}</div>
            </div>
            <div class="${selected ? '' : 'hidden'} shrink-0 h-5 w-5 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs wiz-check-icon">✓</div>
        </label>`;
    }).join('');
}

window.wizToggleProduct = function(id, labelEl) {
    const idx = wizSelectedProducts.indexOf(id);
    const checkIcon = labelEl.querySelector('.wiz-check-icon');
    if (idx === -1) {
        wizSelectedProducts.push(id);
        labelEl.className = labelEl.className.replace('border-gray-100 hover:border-gray-200', 'border-primary-500 bg-primary-50');
        if (checkIcon) checkIcon.classList.remove('hidden');
    } else {
        wizSelectedProducts.splice(idx, 1);
        labelEl.className = labelEl.className.replace('border-primary-500 bg-primary-50', 'border-gray-100 hover:border-gray-200');
        if (checkIcon) checkIcon.classList.add('hidden');
    }
};

function wizRenderReview() {
    const table = el('wiz-review-table');
    if (!table) return;
    const targeting = wizGetTargeting();
    const targetingLabel = { auto: 'تلقائي', keyword: 'كلمات مفتاحية', category: 'فئات', mixed: 'مختلط' }[targeting];
    const budgetTotal = el('wiz-budget-total')?.value || '';
    const budgetDaily = el('wiz-budget-daily')?.value || '';
    const bid = el('wiz-bid')?.value || '';
    const startsAt = el('wiz-starts-at')?.value || '';
    const endsAt = el('wiz-ends-at')?.value || '';
    const rows = [
        ['اسم الحملة', el('wiz-name')?.value || ''],
        ['النموذج', wizGetType().toUpperCase()],
        ['الاستهداف', targetingLabel],
        ['الميزانية الإجمالية', budgetTotal ? budgetTotal + ' ر.س' : ''],
        budgetDaily ? ['الميزانية اليومية', budgetDaily + ' ر.س'] : null,
        ['المزايدة', bid ? bid + ' ر.س' : ''],
        ['عدد المنتجات', wizSelectedProducts.length],
        ['تاريخ البدء', startsAt],
        endsAt ? ['تاريخ الانتهاء', endsAt] : null,
    ].filter(Boolean);
    table.innerHTML = rows.map(([label, val]) =>
        `<div class="flex justify-between px-4 py-2.5">
            <span class="text-gray-500">${label}</span>
            <span class="font-medium text-gray-800">${val}</span>
        </div>`
    ).join('');
}

function wizValidateStep(s) {
    if (s === 1 && !el('wiz-name')?.value.trim()) return 'اسم الحملة مطلوب.';
    if (s === 2 && wizNeedsKeywords() && !wizKeywords.some(k => k.keyword.trim())) return 'أدخل كلمة مفتاحية واحدة على الأقل.';
    if (s === 2 && wizNeedsCategories() && !wizSelectedCategories.length) return 'اختر فئة واحدة على الأقل.';
    if (s === 3 && !wizSelectedProducts.length) return 'اختر منتجاً واحداً على الأقل.';
    if (s === 4) {
        const bt = Number(el('wiz-budget-total')?.value);
        const bd = Number(el('wiz-budget-daily')?.value);
        const bid = Number(el('wiz-bid')?.value);
        if (!bt || bt < 1) return 'الميزانية الإجمالية مطلوبة.';
        if (!bid || bid < 0.01) return 'مبلغ المزايدة مطلوب.';
        if (!el('wiz-starts-at')?.value) return 'تاريخ البدء مطلوب.';
        if (bd && bd > bt) return 'الميزانية اليومية لا يمكن أن تتجاوز الميزانية الإجمالية.';
    }
    return null;
}

function wizOpen() {
    wizStep = 1;
    wizSubmitting = false;
    wizSelectedProducts = [];
    wizSelectedCategories = [];
    wizKeywords = [{ keyword: '', match_type: 'broad', bid_override: '' }];
    wizListingsLoaded = false;
    wizCategoriesLoaded = false;
    wizSetError(null);

    // reset form fields
    ['wiz-name','wiz-budget-total','wiz-budget-daily','wiz-bid','wiz-starts-at','wiz-ends-at'].forEach(id => {
        const e = el(id); if (e) e.value = '';
    });
    const cpcRadio = document.querySelector('input[name="wiz-type"][value="cpc"]');
    if (cpcRadio) cpcRadio.checked = true;
    const targeting = el('wiz-targeting');
    if (targeting) targeting.value = 'auto';

    // set min date
    const today = new Date().toISOString().split('T')[0];
    const startsAt = el('wiz-starts-at');
    if (startsAt) startsAt.min = today;

    wizShowStep(1);
    const overlay = el('wizard-overlay');
    if (overlay) overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function wizClose() {
    if (wizSubmitting) return;
    const overlay = el('wizard-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
}

async function wizSubmit() {
    const err = wizValidateStep(4);
    if (err) { wizSetError(err); return; }

    wizSubmitting = true;
    wizSetError(null);
    const submitBtn = el('wiz-submit');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'جاري الإرسال...'; }

    const payload = {
        name: el('wiz-name')?.value.trim(),
        type: wizGetType(),
        targeting_type: wizGetTargeting(),
        budget_total: Number(el('wiz-budget-total')?.value),
        budget_daily: el('wiz-budget-daily')?.value ? Number(el('wiz-budget-daily').value) : null,
        bid: Number(el('wiz-bid')?.value),
        starts_at: el('wiz-starts-at')?.value,
        ends_at: el('wiz-ends-at')?.value || null,
        country_id: cfg().defaultCountryId || '',
        product_variant_ids: wizSelectedProducts,
        keywords: wizNeedsKeywords()
            ? wizKeywords.filter(k => k.keyword.trim()).map(k => ({
                keyword: k.keyword,
                match_type: k.match_type,
                bid_override: k.bid_override ? Number(k.bid_override) : null,
            }))
            : undefined,
        category_ids: wizNeedsCategories()
            ? wizSelectedCategories.map(c => ({
                category_id: c.category_id,
                bid_override: c.bid_override ? Number(c.bid_override) : null,
            }))
            : undefined,
    };

    try {
        const data = await apiFetch(cfg().createUrl, { method: 'POST', body: JSON.stringify(payload) });
        window.location.href = `${cfg().showBaseUrl}/${data.data.id}`;
    } catch (e) {
        const firstError = e.errors ? Object.values(e.errors)[0]?.[0] : e.message;
        wizSetError(firstError || 'حدث خطأ أثناء الإنشاء. حاول مرة أخرى.');
        wizSubmitting = false;
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'إرسال للمراجعة'; }
    }
}

function wizInit() {
    el('btn-open-wizard')?.addEventListener('click', wizOpen);
    el('btn-open-wizard-empty')?.addEventListener('click', wizOpen);
    el('wiz-close')?.addEventListener('click', wizClose);
    el('wiz-cancel')?.addEventListener('click', wizClose);
    el('wizard-backdrop')?.addEventListener('click', wizClose);

    el('wiz-next')?.addEventListener('click', () => {
        const err = wizValidateStep(wizStep);
        if (err) { wizSetError(err); return; }
        wizSetError(null);
        wizStep = Math.min(wizStep + 1, TOTAL_STEPS);
        wizShowStep(wizStep);
    });

    el('wiz-prev')?.addEventListener('click', () => {
        wizSetError(null);
        wizStep = Math.max(wizStep - 1, 1);
        wizShowStep(wizStep);
    });

    el('wiz-submit')?.addEventListener('click', wizSubmit);

    // type radio styling
    document.querySelectorAll('input[name="wiz-type"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('input[name="wiz-type"]').forEach(r => {
                const lbl = r.closest('label');
                if (!lbl) return;
                if (r.checked) {
                    lbl.className = lbl.className.replace('border-gray-200', 'border-primary-500 bg-primary-50');
                    lbl.querySelector('div')?.classList.replace('text-gray-700', 'text-primary-700');
                } else {
                    lbl.className = lbl.className.replace('border-primary-500 bg-primary-50', 'border-gray-200');
                    lbl.querySelector('div')?.classList.replace('text-primary-700', 'text-gray-700');
                }
            });
        });
    });

    el('btn-add-keyword')?.addEventListener('click', () => {
        wizKeywords.push({ keyword: '', match_type: 'broad', bid_override: '' });
        wizRenderKeywords();
    });
}


// ─────────────────────────────────────────────────────────────────────────────
// Show page — performance chart & campaign actions
// ─────────────────────────────────────────────────────────────────────────────
window.initShowPage = function () {
    loadQualityScore();
    initPerformanceChart();
};

async function loadQualityScore() {
    const el = document.getElementById('quality-score-section');
    if (!el) return;

    try {
        const data = await apiFetch(cfg().qualityScoreUrl);
        if (!data.data) return;
        const qs = data.data;

        document.getElementById('qs-overall').textContent = (qs.score * 10).toFixed(1);

        const bars = [
            { key: 'ctr_score',       id: 'qs-ctr',       label: 'معدل النقر' },
            { key: 'relevance_score', id: 'qs-relevance', label: 'الصلة بالكلمات' },
            { key: 'landing_score',   id: 'qs-landing',   label: 'صفحة الوجهة' },
            { key: 'vendor_score',    id: 'qs-vendor',    label: 'تقييم المتجر' },
        ];

        bars.forEach(({ key, id }) => {
            const pct = (qs[key] / 10 * 100).toFixed(1);
            const bar = document.getElementById(id + '-bar');
            const val = document.getElementById(id + '-val');
            if (bar) bar.style.width = pct + '%';
            if (val) val.textContent = (qs[key] * 10).toFixed(1);
        });
    } catch {}
}

let chart = null;

async function initPerformanceChart() {
    const canvas = document.getElementById('perf-chart');
    if (!canvas || typeof Chart === 'undefined') return;

    const dateFrom = document.getElementById('perf-from')?.value || '';
    const dateTo   = document.getElementById('perf-to')?.value   || '';

    try {
        const params = new URLSearchParams();
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);

        const data = await apiFetch(`${cfg().performanceUrl}?${params}`);
        const rows = data.data?.rows || [];

        const labels = rows.map(r => r.date);
        const impressions = rows.map(r => r.impressions);
        const clicks = rows.map(r => r.clicks);
        const spendArr = rows.map(r => r.spend_cents / 100);

        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'مشاهدات',
                        data: impressions,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.08)',
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'نقرات',
                        data: clicks,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'الإنفاق (ر.س)',
                        data: spendArr,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,0.08)',
                        tension: 0.3,
                        yAxisID: 'y2',
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y:  { position: 'right', title: { display: true, text: 'عدد' } },
                    y2: { position: 'left',  title: { display: true, text: 'ر.س' }, grid: { drawOnChartArea: false } },
                },
            },
        });

        // Summary totals
        const totals = data.data?.totals;
        if (totals) {
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('total-impressions', formatNumber(totals.impressions));
            set('total-clicks',      formatNumber(totals.clicks));
            set('total-conversions', formatNumber(totals.conversions));
            set('total-spend',       formatMoney(totals.spend_cents));
            set('total-revenue',     formatMoney(totals.revenue_attributed_cents));
        }
    } catch {}
}

window.refreshChart = function () { initPerformanceChart(); };

// Pause / Resume
window.pauseCampaign = async function (url) {
    if (!confirm('هل تريد إيقاف الحملة مؤقتاً؟')) return;
    try {
        await apiFetch(url, { method: 'PUT' });
        location.reload();
    } catch (e) {
        alert(e.message || 'تعذّر إيقاف الحملة.');
    }
};

window.resumeCampaign = async function (url) {
    if (!confirm('هل تريد استئناف الحملة؟')) return;
    try {
        await apiFetch(url, { method: 'PUT' });
        location.reload();
    } catch (e) {
        alert(e.message || 'تعذّر استئناف الحملة.');
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Index page
    if (document.getElementById('campaigns-tbody')) {
        wizInit();
        loadCampaigns();

        document.getElementById('filter-status')?.addEventListener('change', e => {
            currentStatus = e.target.value;
            loadCampaigns(1);
        });
    }

    // Show page
    if (document.getElementById('perf-chart')) {
        window.initShowPage();

        document.getElementById('apply-dates')?.addEventListener('click', () => {
            initPerformanceChart();
        });
    }

    // ─── Ads DataTable (new index page) ──────────────────────────────────────
    if (window.ADS_CFG && document.getElementById('ads-table')) {
        wizInit();
        const dtCfg = window.ADS_CFG;

        const table = createPartnerTable('ads-table', {
            url: dtCfg.datatableUrl,
            columns: [
                { data: 'name' },
                { data: 'type' },
                { data: 'status' },
                { data: 'budget' },
                { data: 'bid' },
                { data: 'date' },
                { data: 'actions', orderable: false, searchable: false },
            ],
            order: [[5, 'desc']],
            searchInputId: 'ads-search',
            ajaxData: (d) => {
                const status = document.getElementById('ads-filter-status')?.value;
                if (status) d.status = status;
                const search = document.getElementById('ads-search')?.value;
                if (search) d.search = { value: search };
            },
            language: {
                emptyTable:  'No campaigns found.',
                info:        'Showing _START_–_END_ of _TOTAL_ campaigns',
                infoEmpty:   'No campaigns',
                zeroRecords: 'No campaigns match your search.',
                processing:  '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-primary-400 border-t-transparent rounded-full animate-spin"></div></div>',
            },
        });

        document.getElementById('ads-filter-status')?.addEventListener('change', () => {
            table.ajax.reload();
        });
    }
});
