import './app.js';

// ─────────────────────────────────────────────────────────────────────────────
// Config (injected from Blade via window.CLASSIFIEDS_CFG)
// ─────────────────────────────────────────────────────────────────────────────
const cfg = () => window.CLASSIFIEDS_CFG || {};

// ─────────────────────────────────────────────────────────────────────────────
// Utilities
// ─────────────────────────────────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
    const headers = { Accept: 'application/json', ...options.headers };
    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }
    const res = await fetch(url, { ...options, headers });
    const json = await res.json();
    if (!res.ok) throw json;
    return json;
}

function el(id) { return document.getElementById(id); }

function formatMoney(cents, currency = 'SAR') {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency }).format(cents / 100);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('ar-SA', { year: 'numeric', month: 'short', day: 'numeric' });
}

function statusBadge(status) {
    const map = {
        draft:            { label: 'مسودة',           cls: 'bg-gray-100 text-gray-700' },
        pending_contract: { label: 'بانتظار العقد',    cls: 'bg-amber-100 text-amber-700' },
        pending_review:   { label: 'قيد المراجعة',     cls: 'bg-blue-100 text-blue-700' },
        active:           { label: 'نشط',              cls: 'bg-emerald-100 text-emerald-700' },
        paused:           { label: 'موقوف',             cls: 'bg-gray-100 text-gray-600' },
        sold:             { label: 'تم البيع',          cls: 'bg-purple-100 text-purple-700' },
        expired:          { label: 'منتهي',             cls: 'bg-red-100 text-red-600' },
        rejected:         { label: 'مرفوض',             cls: 'bg-red-100 text-red-700' },
    };
    const s = map[status] || { label: status, cls: 'bg-gray-100 text-gray-600' };
    return `<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${s.cls}">${s.label}</span>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// Index page — listing table
// ─────────────────────────────────────────────────────────────────────────────
let currentPage = 1;
let currentStatus = '';
let searchTimer = null;

async function loadListings(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({ page, per_page: 20 });
    if (currentStatus) params.set('status', currentStatus);
    const q = el('cl-search')?.value.trim();
    if (q) params.set('search', q);

    const tbody = el('cl-tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-sm text-gray-400">جاري التحميل...</td></tr>`;

    try {
        const data = await apiFetch(`${cfg().listUrl}?${params}`);
        renderListings(data.data?.items || []);
        renderPagination(data.data?.meta);
    } catch {
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-sm text-red-500">تعذّر تحميل الإعلانات.</td></tr>`;
    }
}

function renderListings(items) {
    const tbody = el('cl-tbody');
    const empty = el('cl-empty');
    if (!tbody) return;

    if (!items.length) {
        tbody.innerHTML = '';
        if (empty) empty.classList.remove('hidden');
        return;
    }
    if (empty) empty.classList.add('hidden');

    tbody.innerHTML = items.map(listing => {
        const img = listing.primary_image
            ? `<img src="${listing.primary_image}" class="h-full w-full object-cover" alt="">`
            : `<svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 3l18 18"/></svg>`;

        const href = `${cfg().showBaseUrl}/${listing.id}`;
        return `<tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='${href}'">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 h-10 w-10 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center">${img}</div>
                    <div class="min-w-0">
                        <a href="${href}" class="font-medium text-gray-900 hover:text-primary-600 line-clamp-1">${listing.title_ar || listing.title_en}</a>
                        <div class="text-xs text-gray-400 font-mono">${listing.listing_number || ''}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">${statusBadge(listing.status)}</td>
            <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-800">${formatMoney(listing.price_cents, listing.currency)}</td>
            <td class="px-4 py-3 whitespace-nowrap text-gray-500">${listing.views_count ?? 0}</td>
            <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-xs">${formatDate(listing.created_at)}</td>
            <td class="px-4 py-3 whitespace-nowrap">
                <a href="${href}" class="text-xs text-primary-600 hover:underline">عرض</a>
            </td>
        </tr>`;
    }).join('');
}

function renderPagination(meta) {
    const infoEl = el('cl-info');
    const pagEl  = el('cl-pagination');
    if (!meta) return;

    if (infoEl) infoEl.textContent = `${meta.from ?? 0}–${meta.to ?? 0} من ${meta.total ?? 0}`;
    if (!pagEl) return;

    if (meta.last_page <= 1) { pagEl.innerHTML = ''; return; }

    let html = '';
    if (meta.current_page > 1) {
        html += `<button onclick="clGotoPage(${meta.current_page - 1})" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-sm">السابق</button>`;
    }
    html += `<span class="text-gray-500 text-sm">صفحة ${meta.current_page} من ${meta.last_page}</span>`;
    if (meta.current_page < meta.last_page) {
        html += `<button onclick="clGotoPage(${meta.current_page + 1})" class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-sm">التالي</button>`;
    }
    pagEl.innerHTML = html;
}

window.clGotoPage = (page) => loadListings(page);

// ─────────────────────────────────────────────────────────────────────────────
// Wizard state
// ─────────────────────────────────────────────────────────────────────────────
let wizStep = 1;
let wizTotalSteps = 6;
let wizSubmitting = false;
let wizCategories = [];
let wizCategoriesLoaded = false;
let wizSelectedCategory = null;   // leaf category object
let wizSelectedParentId = null;
let wizImageFiles = [];
let wizSketchFile = null;
let wizAttachmentFiles = [];
let wizAttributes = {};

const STEP_LABELS_FULL    = ['الفئة', 'الأساسيات', 'الموقع', 'الصور', 'العقد', 'مراجعة'];
const STEP_LABELS_NO_CONTRACT = ['الفئة', 'الأساسيات', 'الموقع', 'الصور', 'مراجعة'];

function wizHasContract()  { return !!wizSelectedCategory?.has_contract; }
function wizNeedsLocation(){ return !!wizSelectedCategory?.requires_location_map; }
function wizNeedsSketch()  { return !!wizSelectedCategory?.requires_sketch_upload; }
function wizNeedsAttachments() { return (wizSelectedCategory?.required_attachment_types?.length ?? 0) > 0; }

function wizStepLabels() {
    return wizHasContract() ? STEP_LABELS_FULL : STEP_LABELS_NO_CONTRACT;
}

// Real DOM step IDs map: logical → DOM step id
// DOM steps 1-6 always exist but step 5 (contract) is skipped if !wizHasContract
function wizDomStep(logicalStep) {
    // logical steps always present: 1-6 in DOM regardless of contract
    return logicalStep;
}

function wizRenderProgress() {
    const container = el('cl-wiz-progress');
    if (!container) return;
    const labels = wizStepLabels();
    const total = labels.length;

    container.innerHTML = labels.map((label, i) => {
        const num  = i + 1;
        const done = num < wizStep;
        const active = num === wizStep;
        const dotCls = done
            ? 'bg-primary-500 text-white'
            : active
                ? 'bg-primary-600 text-white ring-4 ring-primary-100'
                : 'bg-gray-100 text-gray-400';
        const lineBefore = i === 0           ? 'bg-transparent' : (i < wizStep ? 'bg-primary-500' : 'bg-gray-200');
        const lineAfter  = i === total - 1   ? 'bg-transparent' : (num < wizStep ? 'bg-primary-500' : 'bg-gray-200');
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

// Logical step → DOM step id (contract step may be skipped in flow)
// We keep DOM steps fixed at 1-6 and just skip step 5 in navigation if no contract
function wizNextDomStep(current) {
    if (current === 4 && !wizHasContract()) return 6;
    return current + 1;
}
function wizPrevDomStep(current) {
    if (current === 6 && !wizHasContract()) return 4;
    return current - 1;
}

function wizShowStep(domStep) {
    for (let i = 1; i <= 6; i++) {
        const s = el(`cl-wiz-step-${i}`);
        if (s) s.style.display = i === domStep ? 'block' : 'none';
    }

    // Map domStep to logical step for progress
    const logicalStep = (!wizHasContract() && domStep === 6) ? 5 : domStep;
    wizStep = domStep;

    el('cl-wiz-step-label').textContent = logicalStep;
    el('cl-wiz-total-label').textContent = wizStepLabels().length;
    wizRenderProgress();

    const isFirst = domStep === 1;
    const isLast  = domStep === 6;

    // prev button
    if (!isFirst) { el('cl-wiz-prev').style.display = 'inline-flex'; el('cl-wiz-prev-spacer').style.display = 'none'; }
    else          { el('cl-wiz-prev').style.display = 'none';        el('cl-wiz-prev-spacer').style.display = 'inline'; }

    // next / submit
    if (!isLast) { el('cl-wiz-next').style.display = 'inline-flex'; el('cl-wiz-submit').style.display = 'none'; }
    else         { el('cl-wiz-next').style.display = 'none';        el('cl-wiz-submit').style.display = 'inline-flex'; }

    // Side effects per step
    if (domStep === 1 && !wizCategoriesLoaded) wizLoadCategories();
    if (domStep === 3) wizRenderAttributeFields();
    if (domStep === 4) wizRenderConditionalFileFields();
    if (domStep === 5 && wizHasContract()) wizLoadContractInWizard();
    if (domStep === 6) wizRenderReview();
}

// ─────────────────────────────────────────────────────────────────────────────
// Step 1: Categories
// ─────────────────────────────────────────────────────────────────────────────
async function wizLoadCategories() {
    if (wizCategoriesLoaded) { wizRenderCategories(); return; }
    try {
        const data = await apiFetch(cfg().categoriesUrl);
        wizCategories = data.data || [];
        wizCategoriesLoaded = true;
        wizRenderCategories();
    } catch {
        el('cl-categories-loading').textContent = 'تعذّر تحميل الفئات.';
    }
}

function wizRenderCategories() {
    el('cl-categories-loading').style.display = 'none';
    const grid = el('cl-categories-grid');
    if (!grid) return;
    grid.style.display = 'grid';
    grid.innerHTML = wizCategories.map(cat => {
        const isSelected = wizSelectedParentId === cat.id;
        return `<button type="button" onclick="wizSelectParent('${cat.id}')"
            class="flex items-center gap-3 rounded-xl border-2 p-3 text-start transition-colors ${isSelected ? 'border-primary-500 bg-primary-50' : 'border-gray-100 hover:border-gray-200'}">
            ${cat.icon ? `<span class="text-xl shrink-0">${cat.icon}</span>` : ''}
            <div class="min-w-0">
                <div class="text-sm font-semibold ${isSelected ? 'text-primary-700' : 'text-gray-800'} leading-snug">${cat.name_ar}</div>
                <div class="text-xs text-gray-400">${cat.name_en}</div>
            </div>
        </button>`;
    }).join('');
}

window.wizSelectParent = function(parentId) {
    wizSelectedParentId = parentId;
    wizSelectedCategory = null;
    wizRenderCategories();

    const parent = wizCategories.find(c => c.id === parentId);
    const subSection = el('cl-subcategory-section');
    const subGrid = el('cl-subcategories-grid');

    if (!parent) return;

    if (parent.children?.length) {
        subSection.classList.remove('hidden');
        subGrid.innerHTML = parent.children.map(child => {
            const isSelected = wizSelectedCategory?.id === child.id;
            return `<button type="button" onclick="wizSelectCategory('${child.id}')"
                class="flex items-center gap-2 rounded-xl border-2 p-2.5 text-start text-sm transition-colors ${isSelected ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-100 hover:border-gray-200 text-gray-800'}">
                <span class="truncate">${child.name_ar}</span>
            </button>`;
        }).join('');
    } else {
        // Parent is itself the leaf
        subSection.classList.add('hidden');
        wizSelectedCategory = parent;
    }
};

window.wizSelectCategory = function(id) {
    const parent = wizCategories.find(c => c.id === wizSelectedParentId);
    wizSelectedCategory = parent?.children?.find(c => c.id === id) || null;
    // Re-render sub-grid to update selection highlight
    const subGrid = el('cl-subcategories-grid');
    if (subGrid && parent?.children) {
        subGrid.innerHTML = parent.children.map(child => {
            const isSelected = wizSelectedCategory?.id === child.id;
            return `<button type="button" onclick="wizSelectCategory('${child.id}')"
                class="flex items-center gap-2 rounded-xl border-2 p-2.5 text-start text-sm transition-colors ${isSelected ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-100 hover:border-gray-200 text-gray-800'}">
                <span class="truncate">${child.name_ar}</span>
            </button>`;
        }).join('');
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// Step 3: Location & attribute fields (dynamic)
// ─────────────────────────────────────────────────────────────────────────────
function wizRenderAttributeFields() {
    const locSection  = el('cl-location-section');
    const attrSection = el('cl-attributes-section');
    const attrFields  = el('cl-attributes-fields');

    if (!wizNeedsLocation()) locSection?.classList.add('hidden');
    else locSection?.classList.remove('hidden');

    // Generic attribute fields from category (could be extended; for now provide common ones)
    const commonAttrs = [
        { key: 'rooms',     label: 'عدد الغرف',    type: 'number' },
        { key: 'bathrooms', label: 'عدد الحمامات', type: 'number' },
        { key: 'area',      label: 'المساحة (م²)', type: 'number' },
        { key: 'floor',     label: 'الطابق',        type: 'text' },
        { key: 'year_built',label: 'سنة البناء',    type: 'number' },
        { key: 'condition', label: 'الحالة',         type: 'select',
          options: ['جديد', 'ممتاز', 'جيد', 'يحتاج تجديد'] },
    ];

    if (attrFields) {
        attrFields.innerHTML = commonAttrs.map(attr => {
            if (attr.type === 'select') {
                return `<div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">${attr.label}</label>
                    <select id="cl-attr-${attr.key}" onchange="wizSetAttr('${attr.key}', this.value)"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <option value="">— اختياري —</option>
                        ${attr.options.map(o => `<option value="${o}" ${wizAttributes[attr.key] === o ? 'selected' : ''}>${o}</option>`).join('')}
                    </select>
                </div>`;
            }
            return `<div>
                <label class="block text-xs font-medium text-gray-600 mb-1">${attr.label}</label>
                <input type="${attr.type}" id="cl-attr-${attr.key}" value="${wizAttributes[attr.key] ?? ''}"
                    oninput="wizSetAttr('${attr.key}', this.value)"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    placeholder="اختياري">
            </div>`;
        }).join('');

        // Wrap in 2-col grid
        attrFields.className = 'grid grid-cols-2 gap-3';
    }

    if (attrSection) attrSection.classList.remove('hidden');
}

window.wizSetAttr = function(key, val) {
    if (val) wizAttributes[key] = val;
    else delete wizAttributes[key];
};

// ─────────────────────────────────────────────────────────────────────────────
// Step 4: Conditional file sections
// ─────────────────────────────────────────────────────────────────────────────
function wizRenderConditionalFileFields() {
    const sketchSection = el('cl-sketch-section');
    const attSection    = el('cl-attachments-section');

    if (wizNeedsSketch()) sketchSection?.classList.remove('hidden');
    else sketchSection?.classList.add('hidden');

    if (wizNeedsAttachments()) {
        attSection?.classList.remove('hidden');
        const hint = el('cl-attachments-hint');
        if (hint && wizSelectedCategory?.required_attachment_types?.length) {
            hint.textContent = 'مطلوب: ' + wizSelectedCategory.required_attachment_types.join('، ');
        }
    } else {
        attSection?.classList.add('hidden');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Step 5: Contract (wizard)
// ─────────────────────────────────────────────────────────────────────────────
let wizContractLoaded = false;
let wizContractTemplateId = null;

async function wizLoadContractInWizard() {
    if (wizContractLoaded) return;
    // We don't have a listing yet, so load contract from category's template via categories endpoint
    // The categories endpoint already returns has_contract; we need the template content
    // We can't get the contract content without a listing id — so we show a placeholder
    const loading = el('cl-contract-loading');
    const content = el('cl-contract-content');
    if (loading) loading.style.display = 'none';
    if (content) {
        content.classList.remove('hidden');
        const textEl = el('cl-contract-text');
        if (textEl) textEl.textContent = 'سيتم إنشاء العقد بعد إنشاء الإعلان. يُرجى الموافقة على الشروط العامة للنشر في السوق المفتوح، وسيُرسَل إليك العقد التفصيلي للمراجعة.';
    }
    wizContractLoaded = true;
}

// ─────────────────────────────────────────────────────────────────────────────
// Step 6: Review
// ─────────────────────────────────────────────────────────────────────────────
function wizRenderReview() {
    const table = el('cl-review-table');
    if (!table) return;

    const rows = [
        ['الفئة',    wizSelectedCategory ? (wizSelectedCategory.name_ar || wizSelectedCategory.name_en) : '—'],
        ['العنوان (عربي)',  el('cl-title-ar')?.value || '—'],
        ['العنوان (إنجليزي)', el('cl-title-en')?.value || '—'],
        ['الغرض',    el('cl-purpose')?.value === 'sale' ? 'بيع' : 'إيجار'],
        ['السعر',    `${el('cl-price')?.value || 0} ${el('cl-currency')?.value || 'SAR'}`],
        ['قابل للتفاوض', el('cl-negotiable')?.checked ? 'نعم' : 'لا'],
        ['عدد الصور', wizImageFiles.length],
        wizNeedsSketch() ? ['مخطط', wizSketchFile ? wizSketchFile.name : 'لم يُرفع'] : null,
        wizNeedsLocation() ? ['إحداثيات', `${el('cl-latitude')?.value || '—'} / ${el('cl-longitude')?.value || '—'}`] : null,
        wizHasContract()   ? ['العقد',    el('cl-contract-agree')?.checked ? 'تمت الموافقة' : 'لم توافق بعد'] : null,
    ].filter(Boolean);

    table.innerHTML = rows.map(([label, val]) =>
        `<div class="flex justify-between px-4 py-2.5">
            <span class="text-gray-500">${label}</span>
            <span class="font-medium text-gray-800">${val}</span>
        </div>`
    ).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// Validation
// ─────────────────────────────────────────────────────────────────────────────
function wizValidate(domStep) {
    if (domStep === 1 && !wizSelectedCategory) return 'يرجى اختيار فئة للإعلان.';
    if (domStep === 2) {
        if (!el('cl-title-ar')?.value.trim()) return 'العنوان بالعربية مطلوب.';
        if (!el('cl-title-en')?.value.trim()) return 'العنوان بالإنجليزية مطلوب.';
        if (!el('cl-price')?.value || Number(el('cl-price').value) < 0) return 'يرجى إدخال سعر صحيح.';
    }
    if (domStep === 4 && wizImageFiles.length === 0) return 'يرجى رفع صورة واحدة على الأقل.';
    if (domStep === 5 && wizHasContract()) {
        if (!el('cl-contract-agree')?.checked) return 'يجب الموافقة على العقد للمتابعة.';
        if (!el('cl-signature-name')?.value.trim()) return 'يرجى إدخال الاسم للتوقيع.';
    }
    return null;
}

// ─────────────────────────────────────────────────────────────────────────────
// Submit
// ─────────────────────────────────────────────────────────────────────────────
async function wizSubmit() {
    if (wizSubmitting) return;
    wizSetError(null);

    if (!wizImageFiles.length) { wizSetError('يرجى رفع صورة واحدة على الأقل.'); return; }
    if (wizHasContract() && !el('cl-contract-agree')?.checked) { wizSetError('يجب الموافقة على العقد.'); return; }

    wizSubmitting = true;
    const submitBtn = el('cl-wiz-submit');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'جاري الإرسال...'; }

    const formData = new FormData();
    formData.append('classified_category_id', wizSelectedCategory.id);
    formData.append('country_id',             cfg().countryId);
    formData.append('listing_purpose',        el('cl-purpose')?.value || 'sale');
    formData.append('title_ar',               el('cl-title-ar')?.value.trim());
    formData.append('title_en',               el('cl-title-en')?.value.trim());
    formData.append('description_ar',         el('cl-desc-ar')?.value.trim() || '');
    formData.append('description_en',         el('cl-desc-en')?.value.trim() || '');
    formData.append('price_cents',            Math.round(Number(el('cl-price')?.value || 0) * 100));
    formData.append('currency',               el('cl-currency')?.value || 'SAR');
    formData.append('price_negotiable',       el('cl-negotiable')?.checked ? '1' : '0');

    if (wizNeedsLocation()) {
        const lat = el('cl-latitude')?.value;
        const lng = el('cl-longitude')?.value;
        if (lat) formData.append('latitude', lat);
        if (lng) formData.append('longitude', lng);
    }

    if (Object.keys(wizAttributes).length) {
        formData.append('attributes', JSON.stringify(wizAttributes));
    }

    wizImageFiles.forEach((f, i) => formData.append(`images[${i}]`, f));
    if (wizSketchFile) formData.append('sketch_file', wizSketchFile);
    wizAttachmentFiles.forEach((f, i) => formData.append(`attachments[${i}]`, f));

    // Contract signature (if applicable — included in request, backend will handle on acceptContract step)
    if (wizHasContract() && el('cl-signature-name')?.value.trim()) {
        formData.append('_signature_name', el('cl-signature-name').value.trim());
    }

    try {
        const data = await apiFetch(cfg().storeUrl, { method: 'POST', body: formData });
        window.location.href = `${cfg().showBaseUrl}/${data.data.id}`;
    } catch (e) {
        const firstError = e.errors ? Object.values(e.errors)[0]?.[0] : e.message;
        wizSetError(firstError || 'حدث خطأ أثناء الإرسال. حاول مرة أخرى.');
        wizSubmitting = false;
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'إرسال الإعلان'; }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Wizard open / close / reset
// ─────────────────────────────────────────────────────────────────────────────
function wizSetError(msg) {
    const e = el('cl-wiz-error');
    if (!e) return;
    if (msg) { e.textContent = msg; e.style.display = 'block'; }
    else e.style.display = 'none';
}

function wizOpen() {
    wizStep          = 1;
    wizSubmitting    = false;
    wizSelectedCategory = null;
    wizSelectedParentId = null;
    wizImageFiles    = [];
    wizSketchFile    = null;
    wizAttachmentFiles = [];
    wizAttributes    = {};
    wizContractLoaded = false;
    wizContractTemplateId = null;
    wizSetError(null);

    ['cl-title-ar','cl-title-en','cl-desc-ar','cl-desc-en','cl-price',
     'cl-latitude','cl-longitude','cl-signature-name'].forEach(id => {
        const e = el(id); if (e) e.value = '';
    });
    const neg = el('cl-negotiable'); if (neg) neg.checked = false;
    const agree = el('cl-contract-agree'); if (agree) agree.checked = false;
    const purpose = el('cl-purpose'); if (purpose) purpose.value = 'sale';
    const currency = el('cl-currency'); if (currency) currency.value = 'SAR';

    el('cl-images-preview').innerHTML = '';
    el('cl-sketch-name').classList.add('hidden');
    el('cl-attachments-list').innerHTML = '';
    el('cl-subcategory-section').classList.add('hidden');

    if (wizCategoriesLoaded) wizRenderCategories();

    wizShowStep(1);
    const overlay = el('cl-wizard-overlay');
    if (overlay) overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function wizClose() {
    if (wizSubmitting) return;
    const overlay = el('cl-wizard-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// Image / file handling
// ─────────────────────────────────────────────────────────────────────────────
function initImageUpload() {
    const input = el('cl-images-input');
    if (!input) return;
    input.addEventListener('change', () => {
        const newFiles = Array.from(input.files).slice(0, 10 - wizImageFiles.length);
        wizImageFiles = [...wizImageFiles, ...newFiles].slice(0, 10);
        renderImagePreviews();
        input.value = '';
    });
}

function renderImagePreviews() {
    const preview = el('cl-images-preview');
    if (!preview) return;
    preview.innerHTML = wizImageFiles.map((f, i) => {
        const url = URL.createObjectURL(f);
        return `<div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100">
            <img src="${url}" class="h-full w-full object-cover" alt="">
            <button type="button" onclick="wizRemoveImage(${i})"
                class="absolute top-1 end-1 h-5 w-5 rounded-full bg-black/60 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
        </div>`;
    }).join('');
}

window.wizRemoveImage = function(i) {
    wizImageFiles.splice(i, 1);
    renderImagePreviews();
};

function initSketchUpload() {
    const input = el('cl-sketch-input');
    if (!input) return;
    input.addEventListener('change', () => {
        wizSketchFile = input.files[0] || null;
        const nameEl = el('cl-sketch-name');
        if (nameEl) {
            if (wizSketchFile) { nameEl.textContent = wizSketchFile.name; nameEl.classList.remove('hidden'); }
            else nameEl.classList.add('hidden');
        }
    });
}

function initAttachmentUpload() {
    const input = el('cl-attachments-input');
    if (!input) return;
    input.addEventListener('change', () => {
        wizAttachmentFiles = [...wizAttachmentFiles, ...Array.from(input.files)];
        const list = el('cl-attachments-list');
        if (list) {
            list.innerHTML = wizAttachmentFiles.map((f, i) =>
                `<div class="flex items-center justify-between text-xs text-gray-600 py-1">
                    <span class="truncate">${f.name}</span>
                    <button type="button" onclick="wizRemoveAttachment(${i})" class="text-red-400 hover:text-red-600 ms-2">✕</button>
                </div>`
            ).join('');
        }
        input.value = '';
    });
}

window.wizRemoveAttachment = function(i) {
    wizAttachmentFiles.splice(i, 1);
    const list = el('cl-attachments-list');
    if (list) {
        list.innerHTML = wizAttachmentFiles.map((f, idx) =>
            `<div class="flex items-center justify-between text-xs text-gray-600 py-1">
                <span class="truncate">${f.name}</span>
                <button type="button" onclick="wizRemoveAttachment(${idx})" class="text-red-400 hover:text-red-600 ms-2">✕</button>
            </div>`
        ).join('');
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// Wizard init (event bindings)
// ─────────────────────────────────────────────────────────────────────────────
function wizInit() {
    el('btn-open-wizard')?.addEventListener('click', wizOpen);
    el('btn-open-wizard-empty')?.addEventListener('click', wizOpen);
    el('cl-wiz-close')?.addEventListener('click', wizClose);
    el('cl-wiz-cancel')?.addEventListener('click', wizClose);
    el('cl-wizard-backdrop')?.addEventListener('click', wizClose);

    el('cl-wiz-next')?.addEventListener('click', () => {
        const err = wizValidate(wizStep);
        if (err) { wizSetError(err); return; }
        wizSetError(null);
        wizShowStep(wizNextDomStep(wizStep));
    });

    el('cl-wiz-prev')?.addEventListener('click', () => {
        wizSetError(null);
        wizShowStep(wizPrevDomStep(wizStep));
    });

    el('cl-wiz-submit')?.addEventListener('click', wizSubmit);

    initImageUpload();
    initSketchUpload();
    initAttachmentUpload();
}

// ─────────────────────────────────────────────────────────────────────────────
// Show page
// ─────────────────────────────────────────────────────────────────────────────
async function initShowPage() {
    const loadingEl = el('cl-show-loading');
    const errorEl   = el('cl-show-error');
    const contentEl = el('cl-show-content');

    try {
        const data = await apiFetch(cfg().showUrl);
        const listing = data.data;

        if (loadingEl) loadingEl.classList.add('hidden');
        if (contentEl) contentEl.classList.remove('hidden');

        renderShowHeader(listing);
        renderShowImages(listing.images || []);
        renderShowMeta(listing);
        renderShowDescription(listing);
        renderShowAttachments(listing.attachments || [], listing.sketch_file_url);
        setupShowActions(listing);
        loadInquiries();
    } catch {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (errorEl) errorEl.classList.remove('hidden');
    }
}

function renderShowHeader(listing) {
    const set = (id, val) => { const e = el(id); if (e) e.textContent = val ?? ''; };

    set('sh-listing-number', listing.listing_number ? `#${listing.listing_number}` : '');
    el('sh-status-badge').innerHTML = statusBadge(listing.status);
    set('sh-title', listing.title_ar || listing.title_en);
    set('sh-category-name', listing.category?.name_ar || listing.category?.name_en || '—');
    set('sh-views-count', listing.views_count ?? 0);
    set('sh-created-at', formatDate(listing.created_at));

    el('sh-price').textContent = formatMoney(listing.price_cents, listing.currency);
    if (listing.price_negotiable) el('sh-negotiable').classList.remove('hidden');

    if (listing.rejection_reason) {
        el('sh-rejection-block').classList.remove('hidden');
        set('sh-rejection-reason', listing.rejection_reason);
    }
}

function renderShowImages(images) {
    const loadingEl = el('sh-images-loading');
    const grid      = el('sh-images-grid');
    const emptyEl   = el('sh-images-empty');

    if (loadingEl) loadingEl.style.display = 'none';

    if (!images.length) { if (emptyEl) emptyEl.classList.remove('hidden'); return; }

    if (grid) {
        grid.style.display = 'grid';
        grid.innerHTML = images.map(img =>
            `<a href="${img.url}" target="_blank" class="block aspect-square rounded-lg overflow-hidden bg-gray-100 hover:opacity-90 transition-opacity">
                <img src="${img.url}" class="h-full w-full object-cover" alt="">
            </a>`
        ).join('');
    }
}

function renderShowMeta(listing) {
    const set = (id, val) => { const e = el(id); if (e) e.textContent = val ?? ''; };
    set('sh-purpose',      listing.listing_purpose === 'sale' ? 'بيع' : 'إيجار');
    set('sh-currency',     listing.currency || '—');
    set('sh-meta-category', listing.category?.name_ar || listing.category?.name_en || '—');

    if (listing.expires_at) {
        set('sh-expires', formatDate(listing.expires_at));
    } else {
        el('sh-expires-row')?.classList.add('hidden');
    }
}

function renderShowDescription(listing) {
    const descEl = el('sh-description');
    if (descEl) descEl.textContent = listing.description_ar || listing.description_en || 'لا يوجد وصف.';

    const attrs = listing.attributes;
    if (attrs && Object.keys(attrs).length) {
        el('sh-attributes-section')?.classList.remove('hidden');
        const dl = el('sh-attributes');
        if (dl) {
            dl.innerHTML = Object.entries(attrs).map(([k, v]) =>
                `<div class="col-span-1">
                    <dt class="text-gray-400 text-xs">${k}</dt>
                    <dd class="font-medium text-gray-800">${v}</dd>
                </div>`
            ).join('');
        }
    }
}

const ATTACHMENT_STATUS = {
    pending:  { label: 'قيد المراجعة', cls: 'bg-amber-100 text-amber-700' },
    verified: { label: 'مقبول',         cls: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'مرفوض',         cls: 'bg-red-100 text-red-700' },
};

function renderShowAttachments(attachments, sketchUrl) {
    if (sketchUrl) {
        const row = el('sh-sketch-row');
        if (row) row.style.display = 'flex';
        const link = el('sh-sketch-link');
        if (link) link.href = sketchUrl;
    }

    if (attachments.length) {
        el('sh-attachments-card')?.classList.remove('hidden');
        const list = el('sh-attachments-list');
        if (list) {
            list.innerHTML = attachments.map(att => {
                const s = ATTACHMENT_STATUS[att.status] || { label: att.status || 'قيد المراجعة', cls: 'bg-gray-100 text-gray-600' };
                const inner = att.url
                    ? `<a href="${att.url}" target="_blank" class="flex-1 flex items-center gap-2 min-w-0 hover:text-primary-600 transition-colors">
                            <svg class="h-4 w-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                            <span class="text-sm truncate">${att.attachment_type || 'مرفق'}</span>
                       </a>`
                    : `<div class="flex-1 flex items-center gap-2 min-w-0">
                            <svg class="h-4 w-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                            <span class="text-sm text-gray-500 truncate">${att.attachment_type || 'مرفق'}</span>
                       </div>`;
                return `<div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2">
                    ${inner}
                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${s.cls}">${s.label}</span>
                </div>`;
            }).join('');
        }
    }
}

function setupShowActions(listing) {
    const status = listing.status;

    const pauseBtn    = el('sh-btn-pause');
    const resumeBtn   = el('sh-btn-resume');
    const soldBtn     = el('sh-btn-sold');
    const contractBtn = el('sh-btn-contract');

    if (status === 'active') {
        pauseBtn?.style.setProperty('display', 'inline-flex');
        soldBtn?.style.setProperty('display', 'inline-flex');
    }
    if (status === 'paused') {
        resumeBtn?.style.setProperty('display', 'inline-flex');
        soldBtn?.style.setProperty('display', 'inline-flex');
    }
    if (status === 'pending_contract') {
        contractBtn?.style.setProperty('display', 'inline-flex');
    }

    pauseBtn?.addEventListener('click', async () => {
        if (!confirm('هل تريد إيقاف الإعلان مؤقتاً؟')) return;
        try { await apiFetch(cfg().pauseUrl, { method: 'PUT' }); location.reload(); }
        catch(e) { alert(e.message || 'تعذّر الإيقاف.'); }
    });

    resumeBtn?.addEventListener('click', async () => {
        if (!confirm('هل تريد استئناف الإعلان؟')) return;
        try { await apiFetch(cfg().resumeUrl, { method: 'PUT' }); location.reload(); }
        catch(e) { alert(e.message || 'تعذّر الاستئناف.'); }
    });

    soldBtn?.addEventListener('click', async () => {
        if (!confirm('هل تريد تمييز الإعلان كـ "تم البيع / الإيجار"؟')) return;
        try { await apiFetch(cfg().markSoldUrl, { method: 'PUT' }); location.reload(); }
        catch(e) { alert(e.message || 'تعذّرت العملية.'); }
    });

    contractBtn?.addEventListener('click', () => openContractModal());
}

// ─────────────────────────────────────────────────────────────────────────────
// Contract modal (show page)
// ─────────────────────────────────────────────────────────────────────────────
async function openContractModal() {
    const modal   = el('sh-contract-modal');
    const loading = el('sh-contract-modal-loading');
    const body    = el('sh-contract-modal-body');
    const textEl  = el('sh-contract-modal-text');
    const acceptBtn = el('sh-contract-accept-btn');

    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (loading) loading.style.display = 'block';
    if (body) body.classList.add('hidden');

    try {
        const data = await apiFetch(cfg().contractUrl);
        const content = data.data?.content_ar || data.data?.content_en || 'محتوى العقد غير متاح.';
        if (textEl) textEl.textContent = content;
        if (loading) loading.style.display = 'none';
        if (body) body.classList.remove('hidden');
        if (acceptBtn) acceptBtn.style.display = 'inline-flex';
    } catch {
        if (loading) loading.textContent = 'تعذّر تحميل العقد.';
    }
}

function closeContractModal() {
    const modal = el('sh-contract-modal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// Inquiries (show page)
// ─────────────────────────────────────────────────────────────────────────────
async function loadInquiries() {
    const loadingEl = el('sh-inquiries-loading');
    const emptyEl   = el('sh-inquiries-empty');
    const listEl    = el('sh-inquiries-list');
    const countEl   = el('sh-inquiries-count');

    try {
        const data = await apiFetch(cfg().inquiriesUrl);
        const items = data.data?.items || [];

        if (loadingEl) loadingEl.style.display = 'none';

        if (countEl) countEl.textContent = items.length;

        if (!items.length) {
            if (emptyEl) emptyEl.classList.remove('hidden');
            return;
        }

        if (listEl) {
            listEl.classList.remove('hidden');
            listEl.innerHTML = items.map(inq => renderInquiryRow(inq)).join('');
        }
    } catch {
        if (loadingEl) loadingEl.textContent = 'تعذّر تحميل الاستفسارات.';
    }
}

function renderInquiryRow(inq) {
    const statusMap = {
        new:      { label: 'جديد',          cls: 'bg-blue-100 text-blue-700' },
        read:     { label: 'مقروء',          cls: 'bg-gray-100 text-gray-600' },
        replied:  { label: 'تم الرد',        cls: 'bg-emerald-100 text-emerald-700' },
        closed:   { label: 'مغلق',           cls: 'bg-gray-100 text-gray-500' },
    };
    const s = statusMap[inq.status] || { label: inq.status, cls: 'bg-gray-100 text-gray-600' };

    const statusOptions = ['new','read','replied','closed'].map(v =>
        `<option value="${v}" ${inq.status === v ? 'selected' : ''}>${statusMap[v]?.label || v}</option>`
    ).join('');

    return `<div class="px-5 py-4 space-y-2" id="inq-row-${inq.id}">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${s.cls}">${s.label}</span>
                <span class="text-xs text-gray-500">${inq.buyer_name || 'مشترٍ'}</span>
                <span class="text-xs text-gray-400">${formatDate(inq.created_at)}</span>
            </div>
            <select onchange="updateInquiryStatus('${inq.id}', this.value)"
                class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-primary-400">
                ${statusOptions}
            </select>
        </div>
        <p class="text-sm text-gray-700 leading-relaxed">${inq.message || ''}</p>
        ${inq.contact_phone ? `<p class="text-xs text-gray-500">📞 ${inq.contact_phone}</p>` : ''}
    </div>`;
}

window.updateInquiryStatus = async function(inquiryId, status) {
    const url = cfg().inquiryStatusBaseUrl.replace('__ID__', inquiryId);
    try {
        await apiFetch(url, { method: 'PUT', body: JSON.stringify({ status }) });
    } catch(e) {
        alert(e.message || 'تعذّر تحديث الحالة.');
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // ── Index page ────────────────────────────────────────────────────────────
    if (el('cl-tbody')) {
        wizInit();
        loadListings();

        el('cl-filter-status')?.addEventListener('change', e => {
            currentStatus = e.target.value;
            loadListings(1);
        });

        el('cl-search')?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadListings(1), 400);
        });
    }

    // ── Show page ─────────────────────────────────────────────────────────────
    if (cfg().showId) {
        initShowPage();

        el('sh-contract-close')?.addEventListener('click', closeContractModal);
        el('sh-contract-cancel-btn')?.addEventListener('click', closeContractModal);
        el('sh-contract-backdrop')?.addEventListener('click', closeContractModal);

        el('sh-contract-modal-agree')?.addEventListener('change', e => {
            const btn = el('sh-contract-accept-btn');
            if (btn) btn.disabled = !e.target.checked;
        });

        el('sh-contract-accept-btn')?.addEventListener('click', async () => {
            const sigName = el('sh-contract-sig-name')?.value.trim();
            if (!el('sh-contract-modal-agree')?.checked) {
                el('sh-contract-modal-error').textContent = 'يجب الموافقة على العقد.';
                el('sh-contract-modal-error').classList.remove('hidden');
                return;
            }
            if (!sigName) {
                el('sh-contract-modal-error').textContent = 'يرجى إدخال الاسم للتوقيع.';
                el('sh-contract-modal-error').classList.remove('hidden');
                return;
            }
            const btn = el('sh-contract-accept-btn');
            btn.disabled = true;
            btn.textContent = 'جاري القبول...';
            try {
                await apiFetch(cfg().contractAcceptUrl, {
                    method: 'POST',
                    body: JSON.stringify({ signature_name: sigName }),
                });
                location.reload();
            } catch(e) {
                const msg = e.errors ? Object.values(e.errors)[0]?.[0] : e.message;
                el('sh-contract-modal-error').textContent = msg || 'حدث خطأ.';
                el('sh-contract-modal-error').classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'قبول العقد وإرسال للمراجعة';
            }
        });
    }
});
