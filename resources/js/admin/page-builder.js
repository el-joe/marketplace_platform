/**
 * Admin Page Builder
 *
 * Drag-and-drop visual page composition tied to the /page-builder/* admin API.
 * Uses jQuery (already loaded globally) + SortableJS + Alpine.js for the
 * version-history drawer.
 */

import $ from 'jquery';
import Sortable from 'sortablejs';

const csrfToken = () => $('meta[name="csrf-token"]').attr('content');

/* ─── State ─────────────────────────────────────────────────────────────── */
const state = {
    currentPageId: null,
    currentPageMeta: null,
    selectedBlockId: null,
    autoSaveTimer: null,
    sortable: null,
};

const ROUTES = {
    load: '/page-builder/load',
    pages: '/page-builder/pages',
    publish: (id) => `/page-builder/pages/${id}/publish`,
    pageRevisions: (id) => `/page-builder/pages/${id}/revisions`,
    pageRevRestore: (id) => `/page-builder/page-revisions/${id}/restore`,

    blocks: '/page-builder/blocks',
    blockConfig: (id) => `/page-builder/blocks/${id}/config`,
    blockAnalytics: (id) => `/page-builder/blocks/${id}/analytics`,
    blockVisibility: (id) => `/page-builder/blocks/${id}/visibility`,
    blockRemove: (id) => `/page-builder/blocks/${id}`,
    blockRevisions: (id) => `/page-builder/blocks/${id}/revisions`,
    blockRevRestore: (id) => `/page-builder/revisions/${id}/restore`,
    reorder: '/page-builder/reorder',
    configForm: '/page-builder/config-form',

    slides: (id) => `/page-builder/blocks/${id}/slides`,
    slideSave: (id) => `/page-builder/blocks/${id}/slides`,
    slideDelete: (id) => `/page-builder/slides/${id}`,
    slideReorder: (id) => `/page-builder/blocks/${id}/slides/reorder`,
    slideUploadImage: '/page-builder/slides/upload-image',

    adImages: (id) => `/page-builder/blocks/${id}/ad-images`,
    adImageSave: (id) => `/page-builder/blocks/${id}/ad-images`,
    adImageDelete: (id) => `/page-builder/ad-images/${id}`,
    adImageReorder: (id) => `/page-builder/blocks/${id}/ad-images/reorder`,

    pageUpdate: (id) => `/page-builder/pages/${id}`,
    pageDelete: (id) => `/page-builder/pages/${id}`,
    pageDuplicate: (id) => `/page-builder/pages/${id}/duplicate`,

    searchVendors: '/page-builder/search/vendors',
    searchFlashSales: '/page-builder/search/flash-sales',
    searchProducts: '/page-builder/search/products',
    searchCategories: '/page-builder/search/categories',

    blockProducts: (id) => `/page-builder/blocks/${id}/products`,
    blockProductRemove: (id) => `/page-builder/block-products/${id}`,
    blockProductReorder: (id) => `/page-builder/blocks/${id}/products/reorder`,

    blockCategories: (id) => `/page-builder/blocks/${id}/categories`,
    blockCategoryRemove: (id) => `/page-builder/block-categories/${id}`,
    blockCategoryReorder: (id) => `/page-builder/blocks/${id}/categories/reorder`,

    blockSellers: (id) => `/page-builder/blocks/${id}/sellers`,
    blockSellerRemove: (id) => `/page-builder/block-sellers/${id}`,
    blockSellerReorder: (id) => `/page-builder/blocks/${id}/sellers/reorder`,
};

/* ─── Helpers ───────────────────────────────────────────────────────────── */
const Toast = window.Toast || { success: alert, error: alert, info: console.log, warning: console.warn };

function setSaveStatus(text, kind = '') {
    const $ind = $('#save-indicator');
    $ind.removeClass('saving saved error').addClass(kind || '').text(text);
}

function withLoading($btn, promise) {
    const originalHtml = $btn.html();
    const originalDisabled = $btn.prop('disabled');
    $btn.prop('disabled', true).addClass('opacity-60 cursor-wait');
    return Promise.resolve(promise).finally(() => {
        $btn.html(originalHtml).prop('disabled', originalDisabled).removeClass('opacity-60 cursor-wait');
    });
}

function ajax(options) {
    return $.ajax({
        ...options,
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
    });
}

/* ─── Rendering ─────────────────────────────────────────────────────────── */
function renderBlockCard(block) {
    const icon = block.icon || 'cube';
    const badges = [];
    if (!block.is_visible) badges.push(`<span class="text-xs font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">${window.TRANSLATIONS?.hidden || 'Hidden'}</span>`);
    if (block.visible_from || block.visible_until) badges.push(`<span class="text-xs font-medium px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">${window.TRANSLATIONS?.scheduled || 'Scheduled'}</span>`);
    if (block.device_target && block.device_target !== 'all') badges.push(`<span class="text-xs font-medium px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">${escapeHtml(block.device_target)}</span>`);

    return `
        <div class="block-card group" data-block-id="${block.id}" data-block-type="${escapeHtml(block.block_type || '')}">
            <div class="drag-handle" title="${window.TRANSLATIONS?.dragToReorder || 'Drag to reorder'}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </div>
            <div class="block-icon flex-shrink-0">
                ${heroiconSvg(icon)}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">${escapeHtml(block.label_en || block.block_type)}</div>
                <div class="text-xs text-gray-500 truncate" data-preview>${escapeHtml(block.preview_text || '')}</div>
            </div>
            <div class="flex items-center gap-1.5">${badges.join('')}</div>
            <div class="block-actions flex items-center gap-1">
                <button type="button" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded" data-action="edit-block" title="${window.TRANSLATIONS?.edit || 'Edit'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h-6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6m-6-9 6 6m-6-6L21 3l-4 4"/></svg>
                </button>
                <button type="button" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded" data-action="delete-block" title="${window.TRANSLATIONS?.delete || 'Delete'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M5.84 19.673a2.25 2.25 0 0 0 2.244 2.077h7.832a2.25 2.25 0 0 0 2.244-2.077L19.228 5.79m-14.456 0a48.108 48.108 0 0 1 3.478-.397m11.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </button>
            </div>
        </div>
    `;
}

function heroiconSvg(name) {
    // Minimal inline SVG (we don't have the full heroicon paths in JS — use a generic cube).
    const generic = '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>';
    return `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" data-icon="${name}">${generic}</svg>`;
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* ─── Page loading ──────────────────────────────────────────────────────── */
function loadPage(pageId) {
    state.currentPageId = pageId;
    state.selectedBlockId = null;
    closeConfigPanel();

    if (!pageId) {
        $('#block-canvas').empty().addClass('hidden');
        $('#canvas-empty').removeClass('hidden');
        $('#publish-btn, #version-history-btn, #preview-btn').addClass('hidden');
        $('#page-context-badge').empty();
        $('#home-page-banner').addClass('hidden').empty();
        return;
    }

    setSaveStatus(window.TRANSLATIONS?.loading || t('admin.page_builder.loading'), 'saving');
    return ajax({
        url: ROUTES.load,
        method: 'GET',
        data: { page_id: pageId },
    }).done((res) => {
        state.currentPageMeta = res.page;
        renderCanvas(res.blocks || []);
        renderContextBadge(res.page);
        renderHomeBanner(res.page);
        $('#publish-btn, #version-history-btn').removeClass('hidden');
        $('#preview-btn').attr('href', `/preview/page/${res.page.id}`).removeClass('hidden');
        setSaveStatus('');
    }).fail(() => {
        setSaveStatus(window.TRANSLATIONS?.loadFailed || 'Load failed', 'error');
        Toast.error(window.TRANSLATIONS?.couldNotLoadPage || 'Could not load page.');
    });
}

function renderContextBadge(page) {
    const $badge = $('#page-context-badge');
    if (!page.app_context_key) {
        $badge.html('<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">—</span>');
        return;
    }
    const color = page.app_context_color || '#6b7280';
    $badge.html(`<span class="text-xs font-medium px-2 py-0.5 rounded-full text-white" style="background-color: ${escapeHtml(color)}">${escapeHtml(page.app_context_name || page.app_context_key)}</span>`);
}

function renderHomeBanner(page) {
    const $banner = $('#home-page-banner');
    const assignments = page.home_for || [];
    if (!assignments.length) {
        $banner.addClass('hidden').empty();
        return;
    }
    const list = assignments.map((a) => `${escapeHtml(a.context_name || '')} ${window.TRANSLATIONS.inCountry} ${escapeHtml(a.country_name || '')}`).join(', ');
    $banner.removeClass('hidden').text(
        `${window.TRANSLATIONS.homePageBannerPrefix} ${list}. ${window.TRANSLATIONS.homePageBannerSuffix}`
    );
}

function renderCanvas(blocks) {
    const $canvas = $('#block-canvas');
    if (!blocks.length) {
        $canvas.empty().addClass('hidden');
        $('#canvas-empty').removeClass('hidden').find('p').text(window.TRANSLATIONS?.noBlocksYet || 'This page has no blocks yet. Pick one from the left.');
        return;
    }

    $('#canvas-empty').addClass('hidden');
    $canvas.removeClass('hidden').html(blocks.map(renderBlockCard).join(''));
    initSortable();
}

function initSortable() {
    if (state.sortable) state.sortable.destroy();
    const canvas = document.getElementById('block-canvas');
    if (!canvas) return;
    state.sortable = Sortable.create(canvas, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: persistOrder,
    });
}

function persistOrder() {
    const blocks = $('#block-canvas .block-card').map(function (i) {
        return { id: $(this).data('block-id'), position: i };
    }).get();

    setSaveStatus(window.TRANSLATIONS?.savingOrder || 'Saving order…', 'saving');
    ajax({
        url: ROUTES.reorder,
        method: 'POST',
        data: JSON.stringify({ blocks }),
        contentType: 'application/json',
    }).done(() => setSaveStatus(window.TRANSLATIONS?.orderSaved || 'Order saved', 'saved'))
        .fail(() => { setSaveStatus(window.TRANSLATIONS?.orderSaveFailed || 'Order save failed', 'error'); Toast.error(window.TRANSLATIONS?.couldNotSaveOrder || 'Could not save order.'); });
}

/* ─── Add block ─────────────────────────────────────────────────────────── */
$(document).on('click', '.palette-btn', function () {
    if (!state.currentPageId) {
        Toast.warning(window.TRANSLATIONS?.selectOrCreatePageFirst || 'Select or create a page first.');
        return;
    }

    const $btn = $(this);
    const code = $btn.data('block-type');
    const position = $('#block-canvas .block-card').length;

    withLoading($btn, ajax({
        url: ROUTES.blocks,
        method: 'POST',
        data: { page_id: state.currentPageId, block_type_code: code, position },
    }).done((res) => {
        const block = {
            id: res.block_id, block_type: res.block_type, label_en: res.label_en,
            icon: res.icon, preview_text: res.preview_text, is_visible: true,
        };
        $('#canvas-empty').addClass('hidden');
        $('#block-canvas').removeClass('hidden').append(renderBlockCard(block));
        initSortable();
        selectBlock(res.block_id);
        Toast.success(`${res.label_en || res.block_type} added.`);
    }).fail((xhr) => {
        const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.block_type_code?.[0] || window.TRANSLATIONS?.couldNotAddBlock || 'Could not add block.';
        Toast.error(msg);
    }));
});

/* ─── Select / edit / delete blocks ─────────────────────────────────────── */
$(document).on('click', '.block-card', function (e) {
    if ($(e.target).closest('[data-action]').length) return;
    selectBlock($(this).data('block-id'));
});

$(document).on('click', '[data-action="edit-block"]', function () {
    selectBlock($(this).closest('.block-card').data('block-id'));
});

$(document).on('click', '[data-action="delete-block"]', async function () {
    const $card = $(this).closest('.block-card');
    const blockId = $card.data('block-id');

    const ok = await window.confirmDialog({
        title: window.TRANSLATIONS?.removeBlockTitle || 'Remove block?',
        message: window.TRANSLATIONS?.removeBlockMessage || 'This block (and its config) will be removed from the page. You can restore it from version history if the page has been published.',
        confirmLabel: window.TRANSLATIONS?.remove || 'Remove',
        danger: true,
    });
    if (!ok) return;

    ajax({ url: ROUTES.blockRemove(blockId), method: 'DELETE' })
        .done(() => {
            $card.remove();
            if (state.selectedBlockId === blockId) closeConfigPanel();
            if (!$('#block-canvas .block-card').length) {
                $('#block-canvas').addClass('hidden');
                $('#canvas-empty').removeClass('hidden');
            }
            Toast.success(window.TRANSLATIONS?.blockRemoved || 'Block removed.');
        })
        .fail(() => Toast.error(window.TRANSLATIONS?.couldNotRemoveBlock || 'Could not remove block.'));
});

function selectBlock(blockId) {
    state.selectedBlockId = blockId;
    $('.block-card').removeClass('is-selected');
    $(`.block-card[data-block-id="${blockId}"]`).addClass('is-selected');
    resetAnalyticsTab();
    switchConfigTab('settings');
    openConfigPanel(blockId);
}

/* ─── Analytics tab ─────────────────────────────────────────────────────── */
const analyticsState = {
    cache: {}, // blockId -> parsed response, cleared whenever a new block is selected
    chart: null,
};

function switchConfigTab(tab) {
    $('.config-tab-btn').each(function () {
        const active = $(this).data('config-tab') === tab;
        $(this).toggleClass('border-primary-600 text-primary-700', active);
        $(this).toggleClass('border-transparent text-gray-500', !active);
    });
    $('[data-config-tab-panel]').addClass('hidden');
    $(`[data-config-tab-panel="${tab}"]`).removeClass('hidden');

    if (tab === 'analytics' && state.selectedBlockId) {
        loadBlockAnalytics(state.selectedBlockId);
    }
}

$(document).on('click', '.config-tab-btn', function () {
    switchConfigTab($(this).data('config-tab'));
});

function resetAnalyticsTab() {
    if (analyticsState.chart) {
        analyticsState.chart.destroy();
        analyticsState.chart = null;
    }
    analyticsState.cache = {};
    $('#analytics-content, #analytics-empty, #analytics-error').addClass('hidden');
    $('#analytics-loading').removeClass('hidden');
}

function loadBlockAnalytics(blockId) {
    if (analyticsState.cache[blockId]) {
        renderBlockAnalytics(analyticsState.cache[blockId]);
        return;
    }

    $('#analytics-content, #analytics-empty, #analytics-error').addClass('hidden');
    $('#analytics-loading').removeClass('hidden');

    ajax({ url: ROUTES.blockAnalytics(blockId), method: 'GET' })
        .done((res) => {
            analyticsState.cache[blockId] = res;
            if (state.selectedBlockId === blockId) renderBlockAnalytics(res);
        })
        .fail(() => {
            if (state.selectedBlockId === blockId) {
                $('#analytics-loading, #analytics-content, #analytics-empty').addClass('hidden');
                $('#analytics-error').removeClass('hidden');
            }
        });
}

function renderBlockAnalytics(res) {
    $('#analytics-loading, #analytics-error').addClass('hidden');

    const totals = res.totals || {};
    if (!totals.impressions && !totals.clicks) {
        $('#analytics-content').addClass('hidden');
        $('#analytics-empty').removeClass('hidden');
        return;
    }
    $('#analytics-empty').addClass('hidden');
    $('#analytics-content').removeClass('hidden');

    $('#analytics-stat-impressions').text(formatCompactNumber(totals.impressions));
    $('#analytics-stat-clicks').text(formatCompactNumber(totals.clicks));
    $('#analytics-stat-ctr').text(`${(Number(totals.ctr || 0) * 100).toFixed(2)}%`);
    $('#analytics-stat-add-to-cart').text(formatCompactNumber(totals.add_to_cart_count));
    $('#analytics-stat-orders').text(formatCompactNumber(totals.orders_attributed));
    $('#analytics-stat-revenue').text(formatCents(totals.revenue_attributed));

    const targets = res.top_click_targets || [];
    const $list = $('#analytics-top-targets');
    $list.empty();
    if (!targets.length) {
        $list.append(`<li class="text-gray-400">${window.TRANSLATIONS?.analyticsNoData || 'No analytics recorded for this block in the selected range.'}</li>`);
    } else {
        targets.forEach((t) => {
            $list.append(`
                <li class="flex items-center justify-between gap-2 py-1 border-b border-gray-100 last:border-0">
                    <span class="truncate text-gray-700" title="${escapeHtml(t.click_target)}">${escapeHtml(t.click_target)}</span>
                    <span class="flex-shrink-0 text-gray-400">${escapeHtml(t.click_target_type)} · ${formatCompactNumber(t.count)}</span>
                </li>
            `);
        });
    }

    renderAnalyticsSparkline(res.chart || []);
}

function renderAnalyticsSparkline(chart) {
    const canvas = document.getElementById('analytics-sparkline');
    if (!canvas) return;

    if (analyticsState.chart) {
        analyticsState.chart.destroy();
        analyticsState.chart = null;
    }

    import('chart.js/auto').then(({ default: Chart }) => {
        analyticsState.chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: chart.map((d) => d.date),
                datasets: [
                    {
                        label: window.TRANSLATIONS?.analyticsImpressions || 'Impressions',
                        data: chart.map((d) => d.impressions),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 1.5,
                    },
                    {
                        label: window.TRANSLATIONS?.analyticsClicks || 'Clicks',
                        data: chart.map((d) => d.clicks),
                        borderColor: '#10b981',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 1.5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: { x: { display: false }, y: { display: false } },
                elements: { point: { radius: 0 } },
            },
        });
    });
}

function formatCompactNumber(value) {
    const n = Number(value || 0);
    return new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(n);
}

function formatCents(cents) {
    // Blocks can span multiple countries/currencies, so we show a plain compact
    // number (major units) rather than assuming a single currency symbol.
    return new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 })
        .format(Number(cents || 0) / 100);
}

function closeConfigPanel() {
    state.selectedBlockId = null;
    $('.block-card').removeClass('is-selected');
    $('#config-panel').removeClass('flex').addClass('hidden');
    $('#config-empty').removeClass('hidden');
    $('#config-form-body').empty();
    resetAnalyticsTab();
}

$('#close-config-btn').on('click', closeConfigPanel);

/* ─── Config panel ──────────────────────────────────────────────────────── */
function openConfigPanel(blockId) {
    $('#config-empty').addClass('hidden');
    $('#config-panel').removeClass('hidden').addClass('flex');
    $('#config-form-body').html(`<div class="text-sm text-gray-400 text-center py-8">${t('admin.page_builder.loading')}</div>`);
    $('#config-save-status').text('');

    const $card = $(`.block-card[data-block-id="${blockId}"]`);
    const blockType = $card.find('[data-preview]').length ? null : null;

    // Two parallel requests: the form HTML + the current config values.
    $.when(
        ajax({ url: ROUTES.configForm, method: 'GET', data: { block_id: blockId, block_type_code: getBlockTypeOf(blockId) } }),
        ajax({ url: ROUTES.blockConfig(blockId), method: 'GET' })
    ).done((formRes, configRes) => {
        const html = formRes[0];
        const cfg = configRes[0] || {};
        $('#config-title').text(($card.find('.text-sm.font-medium').text() || window.TRANSLATIONS?.blockSettings || 'Block settings').trim());
        $('#config-form-body').html(html);
        if (window.initDatePickers) window.initDatePickers($('#config-form-body'));
        // Apply config after flatpickr async init settles (initDatePicker uses await internally)
        Promise.resolve().then(() => {
            applyConfigToForm(cfg);
            if (getBlockTypeOf(blockId) === 'hero_slider') loadSlidesList(blockId);
            if ($('#config-form-body [data-block-products-list]').length) loadPickerList('products', blockId);
            if ($('#config-form-body [data-block-categories-list]').length) loadPickerList('categories', blockId);
            if ($('#config-form-body [data-block-sellers-list]').length) loadPickerList('sellers', blockId);
        });
    }).fail(() => {
        $('#config-form-body').html('<div class="text-sm text-rose-600 text-center py-8">Failed to load config form.</div>');
    });
}

/* ─── Manual picker managers: products (product_row), categories (category_pills), sellers (brand_strip) ── */
const PICKER_CONFIG = {
    products: {
        listUrl: ROUTES.blockProducts,
        removeUrl: ROUTES.blockProductRemove,
        reorderUrl: ROUTES.blockProductReorder,
        searchUrl: ROUTES.searchProducts,
        idField: 'product_variant_id',
        reorderPayloadKey: 'products',
        listSelector: '[data-block-products-list]',
        searchInputSelector: '[data-action="search-block-products"]',
        resultsSelector: '[data-block-product-search-results]',
        emptyText: window.TRANSLATIONS?.noProductsYet || 'No products added yet. Search above to add some.',
    },
    categories: {
        listUrl: ROUTES.blockCategories,
        removeUrl: ROUTES.blockCategoryRemove,
        reorderUrl: ROUTES.blockCategoryReorder,
        searchUrl: ROUTES.searchCategories,
        idField: 'category_id',
        reorderPayloadKey: 'categories',
        listSelector: '[data-block-categories-list]',
        searchInputSelector: '[data-action="search-block-categories"]',
        resultsSelector: '[data-block-category-search-results]',
        emptyText: window.TRANSLATIONS?.noCategoriesYet || 'No categories added yet. Search above to add some.',
    },
    sellers: {
        listUrl: ROUTES.blockSellers,
        removeUrl: ROUTES.blockSellerRemove,
        reorderUrl: ROUTES.blockSellerReorder,
        searchUrl: ROUTES.searchVendors,
        idField: 'seller_id',
        reorderPayloadKey: 'sellers',
        listSelector: '[data-block-sellers-list]',
        searchInputSelector: '[data-action="search-block-sellers"]',
        resultsSelector: '[data-block-seller-search-results]',
        emptyText: window.TRANSLATIONS?.noVendorsYet || 'No vendors added yet. Search above to add some.',
    },
};

function loadPickerList(kind, blockId) {
    const cfg = PICKER_CONFIG[kind];
    const $container = $(`#config-form-body ${cfg.listSelector}[data-block-id="${blockId}"]`);
    if (!$container.length) return;

    ajax({ url: cfg.listUrl(blockId), method: 'GET' }).done((res) => {
        const rows = res.results || [];
        if (!rows.length) {
            $container.html(`<div class="text-xs text-gray-400 px-2 py-3 text-center">${escapeHtml(cfg.emptyText)}</div>`);
            return;
        }
        $container.html(rows.map((row) => `
            <div class="flex items-center gap-2 px-2 py-1.5 border border-gray-100 rounded hover:bg-gray-50" data-picker-item-id="${row.id}">
                <span class="drag-handle text-gray-300 cursor-move">⠿</span>
                <span class="flex-1 truncate text-sm text-gray-700">${escapeHtml(row.text || '')}</span>
                <button type="button" class="text-xs text-rose-500 hover:text-rose-700" data-action="remove-picker-item" data-kind="${kind}" data-item-id="${row.id}" data-block-id="${blockId}">Remove</button>
            </div>
        `).join(''));

        if (window.Sortable) {
            Sortable.create($container[0], {
                handle: '.drag-handle',
                animation: 150,
                onEnd: () => {
                    const ordered = $container.find('[data-picker-item-id]').map(function (i) {
                        return { id: $(this).data('picker-item-id'), position: i };
                    }).get();
                    ajax({
                        url: cfg.reorderUrl(blockId), method: 'POST',
                        data: JSON.stringify({ [cfg.reorderPayloadKey]: ordered }), contentType: 'application/json',
                    });
                },
            });
        }
    });
}

$(document).on('input', Object.values(PICKER_CONFIG).map((c) => c.searchInputSelector).join(', '), function () {
    const $input = $(this);
    const kind = Object.keys(PICKER_CONFIG).find((k) => $input.is(PICKER_CONFIG[k].searchInputSelector));
    const cfg = PICKER_CONFIG[kind];
    const blockId = $input.data('block-id');
    const q = $input.val().trim();
    const $results = $(`#config-form-body ${cfg.resultsSelector}[data-block-id="${blockId}"]`);

    clearTimeout($input.data('searchTimer'));
    if (q.length < 2) { $results.addClass('hidden').empty(); return; }

    $input.data('searchTimer', setTimeout(() => {
        ajax({ url: cfg.searchUrl, method: 'GET', data: { q } }).done((res) => {
            const rows = res.results || [];
            if (!rows.length) {
                $results.removeClass('hidden').html(`<div class="px-3 py-2 text-gray-400">${t('admin.page_builder.no_results')}</div>`);
                return;
            }
            $results.removeClass('hidden').html(rows.map((row) => `
                <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50" data-action="add-picker-item" data-kind="${kind}" data-item-id="${row.id}" data-block-id="${blockId}">
                    ${escapeHtml(row.text || '')}
                </button>
            `).join(''));
        });
    }, 300));
});

$(document).on('click', '[data-action="add-picker-item"]', function () {
    const $btn = $(this);
    const kind = $btn.data('kind');
    const cfg = PICKER_CONFIG[kind];
    const blockId = $btn.data('block-id');
    const itemId = $btn.data('item-id');

    ajax({
        url: cfg.listUrl(blockId), method: 'POST',
        data: JSON.stringify({ [cfg.idField]: itemId }), contentType: 'application/json',
    }).done(() => {
        $(`#config-form-body ${cfg.resultsSelector}[data-block-id="${blockId}"]`).addClass('hidden').empty();
        $(`#config-form-body ${cfg.searchInputSelector}[data-block-id="${blockId}"]`).val('');
        loadPickerList(kind, blockId);
    }).fail((xhr) => Toast.error(xhr.responseJSON?.message || t('admin.page_builder.could_not_add_item')));
});

$(document).on('click', '[data-action="remove-picker-item"]', function () {
    const $btn = $(this);
    const kind = $btn.data('kind');
    const cfg = PICKER_CONFIG[kind];
    const blockId = $btn.data('block-id');
    const itemId = $btn.data('item-id');

    ajax({ url: cfg.removeUrl(itemId), method: 'DELETE' }).done(() => {
        loadPickerList(kind, blockId);
    }).fail(() => Toast.error(t('admin.page_builder.could_not_remove_item')));
});

function getBlockTypeOf(blockId) {
    const $card = $(`.block-card[data-block-id="${blockId}"]`);
    return $card.attr('data-block-type') || '';
}

function inferBlockTypeFromAttrs($card) {
    return $card.attr('data-block-type') || '';
}

function applyConfigToForm(cfg) {
    const $form = $('#config-form-body form[data-config-form]');
    if (!$form.length) return;

    // Apply server-rendered data-selected-value for slot-based selects (before config override)
    $form.find('select[data-selected-value]').each(function () {
        $(this).val($(this).data('selected-value'));
    });

    Object.entries(cfg.config || {}).forEach(([key, val]) => {
        const $f = $form.find(`[name="${key}"]`);
        if (!$f.length) return;
        if ($f.is(':checkbox')) $f.prop('checked', !!val);
        else $f.val(val);
    });

    // Visibility section (prefixed)
    $form.find('[name="__vis_is_visible"]').prop('checked', !!cfg.is_visible);
    $form.find('[name="__vis_visible_from"]').val(cfg.visible_from || '');
    $form.find('[name="__vis_visible_until"]').val(cfg.visible_until || '');
    $form.find('[name="__vis_device_target"]').val(cfg.device_target || 'all');
    $form.find('[name="__vis_audience"]').val(cfg.audience || 'all');
}

/* ─── Auto-save on config change ────────────────────────────────────────── */
$(document).on('input change', '#config-form-body form[data-config-form] :input', function () {
    if (!state.selectedBlockId) return;
    clearTimeout(state.autoSaveTimer);
    $('#config-save-status').text(window.TRANSLATIONS?.saving || 'Saving…').removeClass('text-emerald-600 text-rose-600').addClass('text-blue-600');
    state.autoSaveTimer = setTimeout(saveConfig, 700);
});

function saveConfig() {
    const blockId = state.selectedBlockId;
    if (!blockId) return;

    const $form = $('#config-form-body form[data-config-form]');
    if (!$form.length) return;

    const { config, visibility } = collectFormData($form);

    // Save config
    ajax({
        url: ROUTES.blockConfig(blockId),
        method: 'POST',
        data: JSON.stringify({ config, change_type: 'config_updated' }),
        contentType: 'application/json',
    }).done((res) => {
        $('#config-save-status').text(t('admin.page_builder.saved_revision', { revision: res.revision_number })).removeClass('text-blue-600 text-rose-600').addClass('text-emerald-600');
        const $card = $(`.block-card[data-block-id="${blockId}"]`);
        if (res.preview_text != null) $card.find('[data-preview]').text(res.preview_text);
    }).fail(() => {
        $('#config-save-status').text(window.TRANSLATIONS?.saveFailed || 'Save failed').removeClass('text-blue-600 text-emerald-600').addClass('text-rose-600');
    });

    // Save visibility separately (different endpoint)
    ajax({
        url: ROUTES.blockVisibility(blockId),
        method: 'POST',
        data: visibility,
    });
}

function collectFormData($form) {
    const config = {};
    const visibility = {};
    $form.find(':input[name]').each(function () {
        const $f = $(this);
        const name = $f.attr('name');
        if (!name || name === '_token') return;
        let val;
        if ($f.is(':checkbox')) val = $f.is(':checked') ? 1 : 0;
        else val = $f.val();

        if (name === '__raw_json') {
            try { Object.assign(config, JSON.parse(val || '{}')); } catch (e) { /* ignore */ }
            return;
        }
        if (name.startsWith('__vis_')) {
            visibility[name.replace('__vis_', '')] = val;
            return;
        }
        config[name] = val;
    });
    // Convert booleans for visibility
    if ('is_visible' in visibility) visibility.is_visible = !!Number(visibility.is_visible);
    return { config, visibility };
}

/* ─── Slides ────────────────────────────────────────────────────────────── */
function loadSlidesList(blockId) {
    const $container = $(`[data-slides-list][data-block-id="${blockId}"]`);
    if (!$container.length) return;

    ajax({ url: ROUTES.slides(blockId), method: 'GET' })
        .done((res) => {
            const slides = res.slides || [];
            if (!slides.length) {
                $container.html(`<div class="text-xs text-gray-400 px-2 py-3 text-center">${t('admin.page_builder.no_slides_yet')}</div>`);
                return;
            }
            const html = slides.map((s) => `
                <div class="flex items-center gap-2 px-2 py-1.5 border border-gray-100 rounded hover:bg-gray-50" data-slide-id="${s.id}">
                    <span class="text-xs text-gray-400">#${s.position + 1}</span>
                    <span class="flex-1 truncate text-sm text-gray-700">${escapeHtml(s.title_en || s.cta_label_en || 'Slide')}</span>
                    <button type="button" class="text-xs text-gray-500 hover:text-gray-900" data-action="edit-slide" data-slide-id="${s.id}" data-block-id="${blockId}">${t('admin.page_builder.edit_label')}</button>
                    <button type="button" class="text-xs text-rose-500 hover:text-rose-700" data-action="delete-slide" data-slide-id="${s.id}">${t('admin.page_builder.delete_label')}</button>
                </div>
            `).join('');
            $container.html(html);
        });
}

$(document).on('click', '[data-action="add-slide"]', function () {
    const blockId = $(this).data('block-id');
    openSlideModal(blockId, null, {});
});

$(document).on('click', '[data-action="edit-slide"]', function () {
    const blockId = $(this).data('block-id');
    const slideId = $(this).data('slide-id');
    // Re-fetch this slide's full data from the slides list response (cached lazily)
    ajax({ url: ROUTES.slides(blockId), method: 'GET' }).done((res) => {
        const slide = (res.slides || []).find((s) => s.id === slideId);
        openSlideModal(blockId, slideId, slide || {});
    });
});

$(document).on('click', '[data-action="delete-slide"]', async function () {
    const slideId = $(this).data('slide-id');
    const ok = await window.confirmDialog({
        title: window.TRANSLATIONS?.deleteSlideTitle || 'Delete slide?', message: window.TRANSLATIONS?.deleteSlideMessage || 'This slide will be removed.', confirmLabel: window.TRANSLATIONS?.delete || 'Delete', danger: true,
    });
    if (!ok) return;
    ajax({ url: ROUTES.slideDelete(slideId), method: 'DELETE' })
        .done(() => { Toast.success(window.TRANSLATIONS?.slideDeleted || 'Slide deleted.'); loadSlidesList(state.selectedBlockId); })
        .fail(() => Toast.error(window.TRANSLATIONS?.couldNotDeleteSlide || 'Could not delete slide.'));
});

function setSlideImagePreview(slot, fileId, url) {
    const $hidden = $(`#slide-${slot}-file-id`);
    const $preview = $(`#slide-${slot}-preview`);
    const $img = $(`#slide-${slot}-img`);
    $hidden.val(fileId || '');
    if (url) {
        $img.attr('src', url);
        $preview.removeClass('hidden');
    } else {
        $preview.addClass('hidden');
        $img.attr('src', '');
    }
}

function openSlideModal(blockId, slideId, slide) {
    $('#slide-block-id').val(blockId);
    $('#slide-id').val(slideId || '');
    const $form = $('#slide-form');
    $form[0].reset();

    // Reset image previews
    setSlideImagePreview('desktop', '', '');
    setSlideImagePreview('mobile', '', '');

    Object.entries(slide || {}).forEach(([k, v]) => {
        const $f = $form.find(`[name="${k}"]`);
        if (!$f.length) return;
        if ($f.is(':checkbox')) $f.prop('checked', !!v);
        else if ($f[0]?._flatpickr) $f[0]._flatpickr.setDate(v || '', true);
        else $f.val(v ?? '');
    });

    // Populate image previews when editing existing slide
    if (slide.desktop_file_url) setSlideImagePreview('desktop', slide.desktop_file_id, slide.desktop_file_url);
    if (slide.mobile_file_url) setSlideImagePreview('mobile', slide.mobile_file_id, slide.mobile_file_url);

    $('#slide-modal').modal('open');
}

$(document).on('change', '[data-slide-upload]', function () {
    const slot = $(this).data('slide-upload');
    const file = this.files[0];
    if (!file) return;

    const fd = new FormData();
    fd.append('image', file);
    fd.append('slot', slot);
    fd.append('_token', csrfToken());

    const $label = $(this).closest('label');
    $label.addClass('opacity-50 pointer-events-none');

    $.ajax({
        url: ROUTES.slideUploadImage,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
    }).done((res) => {
        setSlideImagePreview(slot, res.file_id, res.url);
        Toast.success(window.TRANSLATIONS?.imageUploaded || 'Image uploaded.');
    }).fail((xhr) => {
        Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.uploadFailed || 'Upload failed.');
    }).always(() => {
        $label.removeClass('opacity-50 pointer-events-none');
        this.value = '';
    });
});

$(document).on('click', '[data-clear-image]', function (e) {
    e.preventDefault();
    const slot = $(this).data('clear-image');
    setSlideImagePreview(slot, '', '');
});

$('#slide-form').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    const blockId = $('#slide-block-id').val();
    const data = {};
    $form.find(':input[name]').each(function () {
        const $f = $(this);
        const name = $f.attr('name');
        if (!name || name === '_token') return;
        if ($f.is(':checkbox')) data[name] = $f.is(':checked') ? 1 : 0;
        else data[name] = $f.val();
    });
    if (!data.id) delete data.id;

    ajax({ url: ROUTES.slideSave(blockId), method: 'POST', data })
        .done(() => {
            Toast.success(window.TRANSLATIONS?.slideSaved || 'Slide saved.');
            $('#slide-modal').modal('close');
            loadSlidesList(blockId);
            // Trigger preview refresh on block card
            $(`.block-card[data-block-id="${blockId}"] [data-preview]`).text(t('admin.page_builder.slider_updated'));
        })
        .fail((xhr) => Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.couldNotSaveSlide || 'Could not save slide.'));
});

/* ─── Page creation ─────────────────────────────────────────────────────── */
$('#create-page-btn').on('click', () => $('#create-page-modal').modal('open'));

function toggleReferenceField() {
    const type = $('#page_type').val();
    $('[data-reference-field]').addClass('hidden');
    $(`[data-reference-field="${type}"]`).removeClass('hidden');
}

function toggleSetAsHome() {
    const hasContext = !!$('[name="app_context_key"]').val();
    const hasCountry = !!$('[name="country_id"]').val();
    $('#set-as-home-wrap').toggleClass('hidden', !(hasContext && hasCountry));
}

$('#page_type').on('change', toggleReferenceField);
$('#create-page-modal').on('modal:opened', toggleReferenceField);
$(document).on('change', '[name="app_context_key"], [name="country_id"]', toggleSetAsHome);

/* ─── Context filter ────────────────────────────────────────────────────── */
$('#context-filter').on('change', function () {
    const value = $(this).val();
    $('#page-select option[data-context]').each(function () {
        const matches = !value || $(this).data('context') === value;
        $(this).toggle(matches);
    });
});

$('#create-page-form').on('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this).entries());

    const type = data.page_type;
    data.reference_id = type ? data[`reference_${type}_id`] || null : null;
    delete data.reference_category_id;
    delete data.reference_brand_id;
    delete data.reference_vendor_id;

    ajax({ url: ROUTES.pages, method: 'POST', data })
        .done((res) => {
            Toast.success(window.TRANSLATIONS?.pageCreated || 'Page created.');
            $('#create-page-modal').modal('close');

            const p = res.page;
            const option = new Option(`${p.name} (${p.page_type})`, p.id, true, true);
            $(option).attr('data-context', p.app_context_key || '__none');
            $('#page-select').append(option).val(p.id).trigger('change');
        })
        .fail((xhr) => {
            const errs = xhr.responseJSON?.errors || {};
            const first = Object.values(errs)[0]?.[0] || xhr.responseJSON?.message || window.TRANSLATIONS?.couldNotCreatePage || 'Could not create page.';
            Toast.error(first);
        });
});

/* ─── Page selection / publish / preview / history ──────────────────────── */
$('#page-select').on('change', function () {
    loadPage($(this).val());
});

$('#publish-btn').on('click', async function () {
    if (!state.currentPageId) return;
    const ok = await window.confirmDialog({
        title: window.TRANSLATIONS?.publishPageTitle || 'Publish page?',
        message: window.TRANSLATIONS?.publishPageMessage || 'This will make the current draft live. A new version snapshot will be created.',
        confirmLabel: window.TRANSLATIONS?.publish || 'Publish',
    });
    if (!ok) return;

    const $btn = $(this);
    withLoading($btn, ajax({
        url: ROUTES.publish(state.currentPageId), method: 'POST',
        data: { reason: 'Published from page builder' },
    }).done(() => Toast.success(window.TRANSLATIONS?.pagePublished || 'Page published.'))
        .fail((xhr) => Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.couldNotPublish || 'Could not publish.')));
});

$('#version-history-btn').on('click', function () {
    if (!state.currentPageId) return;
    window.dispatchEvent(new CustomEvent('open-version-drawer'));
    ajax({ url: ROUTES.pageRevisions(state.currentPageId), method: 'GET' })
        .done((res) => {
            const drawerEl = document.getElementById('version-drawer');
            if (drawerEl && drawerEl._x_dataStack) {
                const data = drawerEl._x_dataStack[0];
                data.revisions = (res.data || []).map((r) => ({
                    id: r.id, version: r.version,
                    publish_reason: r.reason,
                    published_by: r.published_by,
                    created_at: r.created_at,
                }));
                data.loading = false;
            }
        });
});

$(document).on('click', '[data-action="restore-page-revision"]', async function () {
    const revId = $(this).data('revision-id');
    const ok = await window.confirmDialog({
        title: window.TRANSLATIONS?.restoreVersionTitle || 'Restore this version?',
        message: window.TRANSLATIONS?.restoreVersionMessage || 'The current page blocks will be replaced with the selected version. A new revision snapshot will be created.',
        confirmLabel: window.TRANSLATIONS?.restore || 'Restore',
        danger: true,
    });
    if (!ok) return;

    ajax({ url: ROUTES.pageRevRestore(revId), method: 'POST' })
        .done(() => { Toast.success(window.TRANSLATIONS?.versionRestored || 'Version restored.'); loadPage(state.currentPageId); })
        .fail((xhr) => Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS?.couldNotRestore || 'Could not restore.'));
});

/* ─── Boot ──────────────────────────────────────────────────────────────── */
$(function () {
    setSaveStatus('');
});
