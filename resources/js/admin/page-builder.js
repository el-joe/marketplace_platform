// ─────────────────────────────────────────────────────────────────────────────
// Page Builder
// 3-pane builder: sidebar palette · canvas · slide-out config panel
// ─────────────────────────────────────────────────────────────────────────────

const $ = window.jQuery;
const Toast = window.Toast || {
    success: (m) => console.log('[ok]', m),
    error: (m) => console.warn('[err]', m),
    info: (m) => console.log('[info]', m),
};

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

function ajax(method, url, data) {
    const opts = {
        url,
        method,
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    };
    if (method === 'GET') opts.data = data;
    else {
        opts.contentType = 'application/json';
        opts.data = JSON.stringify(data || {});
    }
    return $.ajax(opts);
}

const ajaxGet = (url) => ajax('GET', url);
const ajaxPost = (url, data) => ajax('POST', url, data);
const ajaxPut = (url, data) => ajax('PUT', url, data);
const ajaxDel = (url) => ajax('DELETE', url);

function blockUrl(blockId, suffix = '') {
    return `/page-builder/blocks/${blockId}${suffix}`;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCKS registry — preview + config per block_type code
// Codes mirror BlockTypeSeeder. Unknown types fall through to defaults.
// ─────────────────────────────────────────────────────────────────────────────

const BLOCKS = {
    // ── HERO ────────────────────────────────────────────────────────────────
    hero_slider: {
        label: 'Hero Slider', icon: 'ti-photo',
        preview: (c) => `
            <div class="prev-slider">
                <span>◀</span>
                <div style="text-align:center">
                    <div style="font-weight:600;font-size:13px">${escapeHtml(c.headline || 'Hero Headline')}</div>
                    <div style="font-size:10px;opacity:.85;margin-top:2px">${escapeHtml(c.subhead || 'Slide preview')}</div>
                </div>
                <span>▶</span>
            </div>`,
        config: (c) => `
            ${fieldText('headline', 'Default headline', c.headline)}
            ${fieldText('subhead', 'Default subheadline', c.subhead)}
            ${fieldNumber('autoplay_seconds', 'Autoplay (seconds)', c.autoplay_seconds ?? 6, 0, 60)}
            ${fieldToggle('show_indicators', 'Show indicator dots', c.show_indicators ?? true)}
            ${manageButton('slides', 'Manage slides')}
        `,
    },
    countdown_deal: {
        label: 'Countdown Deal', icon: 'ti-clock-hour-4',
        preview: (c) => `
            <div class="prev-countdown">
                <span>${escapeHtml(c.label || 'Ends in')}:</span>
                <span class="box">02</span>:<span class="box">14</span>:<span class="box">37</span>
            </div>`,
        config: (c) => `
            ${fieldText('label', 'Label', c.label || 'Ends in')}
            ${fieldText('ends_at', 'Ends at (ISO 8601)', c.ends_at, 'datetime-local')}
            ${fieldText('cta_url', 'CTA URL', c.cta_url)}
        `,
    },
    video_banner: {
        label: 'Video Banner', icon: 'ti-player-play',
        preview: () => `
            <div class="prev-video">
                <i class="ti ti-player-play-filled" style="font-size:22px"></i>
                <span>Video banner preview</span>
            </div>`,
        config: (c) => `
            ${fieldText('video_url', 'Video URL', c.video_url)}
            ${fieldText('poster_url', 'Poster image URL', c.poster_url)}
            ${fieldToggle('autoplay', 'Autoplay', c.autoplay ?? true)}
            ${fieldToggle('muted', 'Muted', c.muted ?? true)}
            ${fieldToggle('loop', 'Loop', c.loop ?? true)}
        `,
    },
    occasion_banner: {
        label: 'Occasion Banner', icon: 'ti-confetti',
        preview: (c) => `
            <div class="prev-banner" style="background:#fef3c7;color:#92400e">
                <i class="ti ti-confetti" style="font-size:18px"></i>
                <strong>${escapeHtml(c.title || 'Eid Mubarak')}</strong>
                <span style="margin-left:auto">${escapeHtml(c.cta_label || 'Shop now')} →</span>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title)}
            ${fieldText('cta_label', 'CTA label', c.cta_label)}
            ${fieldText('cta_url', 'CTA URL', c.cta_url)}
            ${fieldColor('background', 'Background color', c.background || '#fef3c7')}
        `,
    },

    // ── PRODUCTS ────────────────────────────────────────────────────────────
    product_row: {
        label: 'Product Row', icon: 'ti-shopping-bag',
        preview: (c) => `
            <div style="font-size:11px;font-weight:600;margin-bottom:6px;color:#111827">${escapeHtml(c.title || 'Product row')}</div>
            <div class="prev-products">${productSkeletons(4)}</div>`,
        config: (c) => `
            ${fieldText('title', 'Row title', c.title)}
            ${fieldSelect('source', 'Product source', c.source || 'curated', [
            ['curated', 'Curated list'],
            ['category', 'By category'],
            ['bestsellers', 'Bestsellers'],
            ['new_arrivals', 'New arrivals'],
        ])}
            ${fieldNumber('limit', 'Max products', c.limit ?? 12, 1, 50)}
            ${manageButton('products', 'Manage products')}
        `,
    },
    flash_sale: {
        label: 'Flash Sale', icon: 'ti-bolt',
        preview: (c) => `
            <div class="prev-flash">
                <span class="prev-flash-badge"><i class="ti ti-bolt"></i> FLASH SALE</span>
                <span class="prev-flash-timer">02:14:37</span>
                <div class="prev-flash-products">
                    <div class="prev-flash-item"></div><div class="prev-flash-item"></div>
                    <div class="prev-flash-item"></div><div class="prev-flash-item"></div>
                </div>
            </div>`,
        config: (c) => `
            ${fieldText('flash_sale_id', 'Flash Sale ID', c.flash_sale_id)}
            ${fieldText('title', 'Title', c.title || 'Flash Sale')}
            ${fieldNumber('limit', 'Max products', c.limit ?? 8, 1, 24)}
        `,
    },
    deal_of_day: {
        label: 'Deal of the Day', icon: 'ti-discount-2',
        preview: () => `
            <div class="prev-banner" style="background:#fef2f2;color:#991b1b">
                <i class="ti ti-discount-2" style="font-size:18px"></i>
                <div><strong>Deal of the Day</strong> · save up to 70%</div>
                <span style="margin-left:auto">Shop now →</span>
            </div>`,
        config: (c) => `
            ${fieldText('product_variant_id', 'Featured variant ID', c.product_variant_id)}
            ${fieldText('headline', 'Headline', c.headline || 'Deal of the Day')}
        `,
    },
    recently_viewed: {
        label: 'Recently Viewed', icon: 'ti-history',
        preview: () => `
            <div style="font-size:11px;font-weight:600;margin-bottom:6px;color:#111827">Recently viewed</div>
            <div class="prev-products">${productSkeletons(4)}</div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Recently viewed')}
            ${fieldNumber('limit', 'Max items', c.limit ?? 8, 1, 20)}
        `,
    },
    top_rated: {
        label: 'Top Rated', icon: 'ti-star',
        preview: () => `
            <div style="font-size:11px;font-weight:600;margin-bottom:6px;color:#111827">⭐ Top rated</div>
            <div class="prev-products">${productSkeletons(4)}</div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Top rated')}
            ${fieldNumber('min_rating', 'Min rating', c.min_rating ?? 4, 1, 5)}
            ${fieldNumber('limit', 'Max items', c.limit ?? 8, 1, 20)}
        `,
    },
    new_arrivals: {
        label: 'New Arrivals', icon: 'ti-sparkles',
        preview: () => `
            <div style="font-size:11px;font-weight:600;margin-bottom:6px;color:#111827">✨ New arrivals</div>
            <div class="prev-products">${productSkeletons(4)}</div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'New arrivals')}
            ${fieldNumber('days', 'Last N days', c.days ?? 14, 1, 90)}
            ${fieldNumber('limit', 'Max items', c.limit ?? 8, 1, 20)}
        `,
    },
    seller_spotlight: {
        label: 'Seller Spotlight', icon: 'ti-building-store',
        preview: () => `
            <div class="prev-banner" style="background:#eff6ff;color:#1e3a8a">
                <i class="ti ti-building-store" style="font-size:18px"></i>
                <strong>Vendor spotlight</strong>
                <span style="margin-left:auto">View store →</span>
            </div>`,
        config: (c) => `
            ${fieldText('vendor_id', 'Vendor ID', c.vendor_id)}
            ${fieldText('title', 'Title', c.title || 'Seller spotlight')}
        `,
    },
    comparison_table: {
        label: 'Comparison Table', icon: 'ti-table',
        preview: () => `
            <div class="prev-products">${productSkeletons(3)}</div>
            <div style="font-size:10px;color:#9ca3af;margin-top:6px;text-align:center">Comparison table — 3 columns</div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Compare')}
            ${manageButton('products', 'Choose products to compare')}
        `,
    },

    // ── ADS & BANNERS ───────────────────────────────────────────────────────
    ad_images_2col: adGrid(2, 'Ad images · 2 columns'),
    ad_images_3col: adGrid(3, 'Ad images · 3 columns'),
    ad_images_4col: adGrid(4, 'Ad images · 4 columns'),

    full_banner: {
        label: 'Full Banner', icon: 'ti-rectangle',
        preview: (c) => `
            <div class="prev-banner" style="height:60px;background:linear-gradient(90deg,#1e40af,#7c3aed);color:#fff">
                <strong>${escapeHtml(c.headline || 'Full-width banner')}</strong>
                <span style="margin-left:auto;font-size:10px;opacity:.9">${escapeHtml(c.cta_label || 'Shop now')} →</span>
            </div>`,
        config: (c) => `
            ${fieldText('headline', 'Headline', c.headline)}
            ${fieldText('cta_label', 'CTA label', c.cta_label)}
            ${fieldText('cta_url', 'CTA URL', c.cta_url)}
            ${manageButton('ad_images', 'Manage banner image')}
        `,
    },
    split_banner: {
        label: 'Split Banner', icon: 'ti-layout-columns',
        preview: () => `
            <div class="prev-adgrid col2">
                <div class="prev-ad" style="height:64px">LEFT</div>
                <div class="prev-ad" style="height:64px">RIGHT</div>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title)}
            ${manageButton('ad_images', 'Manage banner images')}
        `,
    },
    sponsored_products: {
        label: 'Sponsored Products', icon: 'ti-badge-ad',
        preview: () => `
            <div class="prev-sponsored">
                <i class="ti ti-badge-ad" style="font-size:18px"></i>
                <strong>Sponsored</strong>
                <span style="margin-left:auto">View all →</span>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Sponsored')}
            ${fieldNumber('limit', 'Slots', c.limit ?? 4, 1, 12)}
        `,
    },
    paid_banner: {
        label: 'Paid Banner', icon: 'ti-coin',
        preview: (c) => `
            <div class="prev-banner" style="background:#fef9c3;color:#854d0e">
                <i class="ti ti-coin" style="font-size:18px"></i>
                <strong>${escapeHtml(c.headline || 'Sponsored placement')}</strong>
                <span style="margin-left:auto;font-size:10px">Ad</span>
            </div>`,
        config: (c) => `
            ${fieldText('campaign_id', 'Ad campaign ID', c.campaign_id)}
            ${fieldText('headline', 'Fallback headline', c.headline)}
            ${manageButton('ad_images', 'Manage banner image')}
        `,
    },

    // ── DISCOVERY ───────────────────────────────────────────────────────────
    category_pills: {
        label: 'Category Pills', icon: 'ti-category',
        preview: () => `
            <div class="prev-cats">
                <span class="prev-cat">Electronics</span>
                <span class="prev-cat">Fashion</span>
                <span class="prev-cat">Home</span>
                <span class="prev-cat">Beauty</span>
                <span class="prev-cat">Sports</span>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Shop by category')}
            <div class="field"><label>Category IDs (comma separated)</label>
                <input type="text" data-config-key="category_ids" value="${escapeHtml((c.category_ids || []).join(','))}"></div>
        `,
    },
    brand_strip: {
        label: 'Brand Strip', icon: 'ti-tag',
        preview: () => `
            <div class="prev-cats">
                <span class="prev-cat">Apple</span><span class="prev-cat">Samsung</span>
                <span class="prev-cat">Sony</span><span class="prev-cat">Nike</span>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Top brands')}
            <div class="field"><label>Brand IDs (comma separated)</label>
                <input type="text" data-config-key="brand_ids" value="${escapeHtml((c.brand_ids || []).join(','))}"></div>
        `,
    },
    search_trends: {
        label: 'Search Trends', icon: 'ti-trending-up',
        preview: () => `
            <div class="prev-cats">
                <span class="prev-cat">📈 iphone 15</span>
                <span class="prev-cat">📈 ramadan deals</span>
                <span class="prev-cat">📈 air fryer</span>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Trending searches')}
            ${fieldNumber('limit', 'Max trends', c.limit ?? 8, 1, 20)}
        `,
    },
    geo_recommendations: {
        label: 'Geo Recommendations', icon: 'ti-map-pin',
        preview: () => `
            <div class="prev-banner" style="background:#f0fdf4;color:#166534">
                <i class="ti ti-map-pin" style="font-size:18px"></i>
                <strong>Popular near you</strong>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'Popular near you')}
            ${fieldNumber('radius_km', 'Radius (km)', c.radius_km ?? 50, 5, 500)}
        `,
    },

    // ── ENGAGEMENT ──────────────────────────────────────────────────────────
    countdown_timer: {
        label: 'Countdown Timer', icon: 'ti-clock',
        preview: (c) => `
            <div class="prev-countdown">
                <span>${escapeHtml(c.label || 'Sale ends in')}:</span>
                <span class="box">12</span>:<span class="box">34</span>:<span class="box">56</span>
            </div>`,
        config: (c) => `
            ${fieldText('label', 'Label', c.label || 'Sale ends in')}
            ${fieldText('ends_at', 'Ends at', c.ends_at, 'datetime-local')}
        `,
    },
    how_it_works: {
        label: 'How It Works', icon: 'ti-list-numbers',
        preview: () => `
            <div class="prev-steps">
                <div class="prev-step"><b>1</b>Browse</div>
                <div class="prev-step"><b>2</b>Order</div>
                <div class="prev-step"><b>3</b>Enjoy</div>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'How it works')}
            <div class="field"><label>Steps (one per line, format: title|description)</label>
                <textarea data-config-key="steps_raw" rows="4">${escapeHtml(stepsToText(c.steps))}</textarea></div>
        `,
    },
    loyalty_banner: {
        label: 'Loyalty Banner', icon: 'ti-gift',
        preview: () => `
            <div class="prev-banner" style="background:#fdf4ff;color:#86198f">
                <i class="ti ti-gift" style="font-size:18px"></i>
                <strong>Earn points · 2× this week</strong>
            </div>`,
        config: (c) => `
            ${fieldText('headline', 'Headline', c.headline || 'Earn points on every order')}
            ${fieldText('cta_url', 'CTA URL', c.cta_url)}
        `,
    },
    loyalty_progress: {
        label: 'Loyalty Progress', icon: 'ti-progress',
        preview: () => `
            <div class="prev-banner" style="background:#fff7ed;color:#9a3412">
                <i class="ti ti-progress" style="font-size:18px"></i>
                <div style="flex:1">
                    <div style="font-size:10px">450 / 1000 points to Gold</div>
                    <div class="prev-option-bar" style="margin-top:4px"><div class="prev-option-fill" style="width:45%"></div></div>
                </div>
            </div>`,
        config: () => `<div class="prev-text">Dynamic per-user component — no extra config.</div>`,
    },
    poll_widget: {
        label: 'Poll Widget', icon: 'ti-chart-bar',
        preview: (c) => `
            <div style="font-size:11px;font-weight:600;margin-bottom:6px">${escapeHtml(c.question || 'Quick poll')}</div>
            <div class="prev-poll">
                <div class="prev-option">Option A<div class="prev-option-bar"><div class="prev-option-fill" style="width:60%"></div></div></div>
                <div class="prev-option">Option B<div class="prev-option-bar"><div class="prev-option-fill" style="width:40%"></div></div></div>
            </div>`,
        config: (c) => `
            ${fieldText('question', 'Question', c.question)}
            <div class="field"><label>Options (one per line)</label>
                <textarea data-config-key="options_raw" rows="3">${escapeHtml((c.options || []).join('\n'))}</textarea></div>
        `,
    },
    review_highlights: {
        label: 'Review Highlights', icon: 'ti-quote',
        preview: () => `
            <div class="prev-banner" style="background:#f0f9ff;color:#075985">
                <i class="ti ti-quote" style="font-size:18px"></i>
                <em style="font-size:11px">"Fast delivery, great prices…"</em>
            </div>`,
        config: (c) => `
            ${fieldText('title', 'Title', c.title || 'What customers say')}
            ${fieldNumber('min_rating', 'Min rating', c.min_rating ?? 4, 1, 5)}
            ${fieldNumber('limit', 'Max reviews', c.limit ?? 6, 1, 20)}
        `,
    },
    newsletter_signup: {
        label: 'Newsletter Signup', icon: 'ti-mail',
        preview: (c) => `
            <div class="prev-banner" style="background:#f3f4f6;color:#111827">
                <i class="ti ti-mail" style="font-size:18px"></i>
                <strong>${escapeHtml(c.headline || 'Get 10% off your first order')}</strong>
                <input type="email" placeholder="your@email.com" disabled style="margin-left:auto;padding:4px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:11px">
            </div>`,
        config: (c) => `
            ${fieldText('headline', 'Headline', c.headline)}
            ${fieldText('cta_label', 'CTA label', c.cta_label || 'Subscribe')}
        `,
    },
    app_download_banner: {
        label: 'App Download', icon: 'ti-device-mobile',
        preview: () => `
            <div class="prev-banner" style="background:#eef2ff;color:#3730a3">
                <i class="ti ti-device-mobile" style="font-size:18px"></i>
                <strong>Get the app</strong>
                <span style="margin-left:auto">App Store · Google Play</span>
            </div>`,
        config: (c) => `
            ${fieldText('ios_url', 'iOS App Store URL', c.ios_url)}
            ${fieldText('android_url', 'Google Play URL', c.android_url)}
        `,
    },
    instagram_feed: {
        label: 'Instagram Feed', icon: 'ti-brand-instagram',
        preview: () => `
            <div class="prev-adgrid col4">
                <div class="prev-ad">IG</div><div class="prev-ad">IG</div>
                <div class="prev-ad">IG</div><div class="prev-ad">IG</div>
            </div>`,
        config: (c) => `
            ${fieldText('handle', 'Instagram handle', c.handle)}
            ${fieldNumber('limit', 'Posts', c.limit ?? 8, 1, 24)}
        `,
    },
    text_block: {
        label: 'Text Block', icon: 'ti-align-left',
        preview: (c) => `<div class="prev-text">${escapeHtml(c.text_en || 'Add text content for this block…').slice(0, 200)}</div>`,
        config: (c) => `
            <div class="field"><label>Text (EN)</label>
                <textarea data-config-key="text_en" rows="4">${escapeHtml(c.text_en || '')}</textarea></div>
            <div class="field"><label>Text (AR)</label>
                <textarea data-config-key="text_ar" rows="4" dir="rtl">${escapeHtml(c.text_ar || '')}</textarea></div>
            ${fieldSelect('alignment', 'Alignment', c.alignment || 'left', [
            ['left', 'Left'], ['center', 'Center'], ['right', 'Right'],
        ])}
        `,
    },
    divider: {
        label: 'Divider', icon: 'ti-minus',
        preview: () => `<div class="prev-divider"></div>`,
        config: (c) => `
            ${fieldSelect('style', 'Style', c.style || 'solid', [
            ['solid', 'Solid'], ['dashed', 'Dashed'], ['dotted', 'Dotted'],
        ])}
            ${fieldColor('color', 'Color', c.color || '#e5e7eb')}
            ${fieldNumber('spacing', 'Spacing (px)', c.spacing ?? 16, 0, 100)}
        `,
    },
};

// Default fallback for any block code not explicitly registered above.
const DEFAULT_BLOCK = {
    label: 'Block', icon: 'ti-square',
    preview: (c) => `<div class="prev-text">${escapeHtml(c.title || c.headline || 'Block preview')}</div>`,
    config: (c) => `
        ${fieldText('title', 'Title', c.title)}
        ${fieldText('subtitle', 'Subtitle', c.subtitle)}
    `,
};

function getBlockDef(type) {
    return BLOCKS[type] || DEFAULT_BLOCK;
}

// ─────────────────────────────────────────────────────────────────────────────
// Tiny field-builder helpers
// ─────────────────────────────────────────────────────────────────────────────

function fieldText(key, label, value, type = 'text') {
    return `<div class="field"><label>${escapeHtml(label)}</label>
        <input type="${type}" data-config-key="${key}" value="${escapeHtml(value ?? '')}"></div>`;
}
function fieldNumber(key, label, value, min, max) {
    return `<div class="field"><label>${escapeHtml(label)}</label>
        <input type="number" data-config-key="${key}" value="${escapeHtml(value ?? '')}"
            ${min != null ? `min="${min}"` : ''} ${max != null ? `max="${max}"` : ''}></div>`;
}
function fieldColor(key, label, value) {
    return `<div class="field"><label>${escapeHtml(label)}</label>
        <input type="color" data-config-key="${key}" value="${escapeHtml(value || '#000000')}"></div>`;
}
function fieldSelect(key, label, value, options) {
    const opts = options.map(([v, l]) =>
        `<option value="${escapeHtml(v)}" ${v === value ? 'selected' : ''}>${escapeHtml(l)}</option>`
    ).join('');
    return `<div class="field"><label>${escapeHtml(label)}</label>
        <select data-config-key="${key}">${opts}</select></div>`;
}
function fieldToggle(key, label, on) {
    return `<div class="toggle-row">
        <span>${escapeHtml(label)}</span>
        <div class="tog ${on ? 'on' : ''}" data-config-key="${key}" data-type="bool" role="switch" aria-checked="${!!on}"></div>
    </div>`;
}
function manageButton(kind, label) {
    return `<button type="button" class="ct-btn" data-manage="${kind}" style="width:100%;margin-top:4px">
        <i class="ti ti-edit" style="margin-right:4px"></i>${escapeHtml(label)}
    </button>`;
}

function adGrid(cols, label) {
    return {
        label, icon: cols === 2 ? 'ti-layout-grid' : cols === 3 ? 'ti-layout-columns' : 'ti-grid-dots',
        preview: () => {
            let cells = '';
            for (let i = 0; i < cols; i++) cells += `<div class="prev-ad">AD</div>`;
            return `<div class="prev-adgrid col${cols}">${cells}</div>`;
        },
        config: (c) => `
            ${fieldText('title', 'Title', c.title)}
            <div class="prev-text" style="margin-bottom:8px">Configured for <b>${cols}</b> columns. Add images below.</div>
            ${manageButton('ad_images', 'Manage banner images')}
        `,
    };
}

function productSkeletons(n) {
    let out = '';
    for (let i = 0; i < n; i++) {
        out += `<div class="prev-product"><div class="prev-product-img"></div>
            <div class="prev-product-info">Product ${i + 1}<br><b style="color:#111827">$XX.XX</b></div></div>`;
    }
    return out;
}

function stepsToText(steps) {
    if (!Array.isArray(steps)) return '';
    return steps.map(s => `${s.title || ''}|${s.description || ''}`).join('\n');
}

// ─────────────────────────────────────────────────────────────────────────────
// Canvas state + render
// ─────────────────────────────────────────────────────────────────────────────

let blocks = [];               // current page blocks (in order)
let selectedBlockId = null;    // id of block currently shown in config panel
let dirty = false;             // unsaved canvas state

function $canvas() { return document.getElementById('canvas'); }
function $dropHint() { return document.getElementById('drop-hint'); }
function $panel() { return document.getElementById('config-panel'); }

function renderCanvas() {
    const canvas = $canvas();
    if (!canvas) return;
    // Wipe everything except the drop-hint
    canvas.querySelectorAll('.canvas-block').forEach(n => n.remove());

    const hint = $dropHint();
    if (blocks.length === 0) {
        hint.style.display = '';
        return;
    }
    hint.style.display = 'none';

    blocks.forEach((b, idx) => canvas.appendChild(buildBlockCard(b, idx)));
}

function buildBlockCard(b, idx) {
    const def = getBlockDef(b.block_type);
    const card = document.createElement('div');
    card.className = 'canvas-block' + (b.id === selectedBlockId ? ' selected' : '');
    card.dataset.blockId = b.id;
    card.dataset.blockType = b.block_type;
    card.draggable = true;

    const hiddenBadge = b.is_visible ? '' : '<span class="cb-status hidden">Hidden</span>';
    const deviceBadge = (b.device_target && b.device_target !== 'all')
        ? `<span class="cb-status">${escapeHtml(b.device_target)}</span>` : '';

    card.innerHTML = `
        <div class="cb-header">
            <i class="ti ti-grip-vertical cb-drag" aria-hidden="true"></i>
            <span class="cb-type">
                <i class="ti ${def.icon}" aria-hidden="true"></i>
                ${escapeHtml(def.label)}
            </span>
            ${hiddenBadge}${deviceBadge}
            <button type="button" class="cb-act" data-action="up"     ${idx === 0 ? 'disabled' : ''} title="Move up"><i class="ti ti-chevron-up"></i></button>
            <button type="button" class="cb-act" data-action="down"   ${idx === blocks.length - 1 ? 'disabled' : ''} title="Move down"><i class="ti ti-chevron-down"></i></button>
            <button type="button" class="cb-act danger" data-action="delete" title="Delete"><i class="ti ti-trash"></i></button>
        </div>
        <div class="cb-preview">${def.preview(b.config || {})}</div>
    `;
    return card;
}

// ─────────────────────────────────────────────────────────────────────────────
// Sidebar drag → drop on canvas
// ─────────────────────────────────────────────────────────────────────────────

let dragType = null;        // when dragging a sidebar pill
let dragBlockId = null;     // when reordering an existing canvas block

document.addEventListener('dragstart', (e) => {
    const pill = e.target.closest('.block-pill');
    if (pill) {
        dragType = pill.dataset.blockType;
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', dragType);
        return;
    }
    const card = e.target.closest('.canvas-block');
    if (card) {
        dragBlockId = card.dataset.blockId;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }
});

document.addEventListener('dragend', (e) => {
    const card = e.target.closest('.canvas-block');
    if (card) card.classList.remove('dragging');
    dragType = null;
    dragBlockId = null;
});

function initCanvasDnd() {
    const canvas = $canvas();
    if (!canvas) return;

    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = dragBlockId ? 'move' : 'copy';
        const hint = $dropHint();
        if (hint) hint.classList.add('over');
    });
    canvas.addEventListener('dragleave', (e) => {
        if (e.target === canvas) {
            const hint = $dropHint();
            if (hint) hint.classList.remove('over');
        }
    });
    canvas.addEventListener('drop', async (e) => {
        e.preventDefault();
        const hint = $dropHint();
        if (hint) hint.classList.remove('over');

        // Reorder existing block
        if (dragBlockId) {
            const targetCard = e.target.closest('.canvas-block');
            const fromIdx = blocks.findIndex(b => b.id === dragBlockId);
            if (fromIdx < 0) return;
            const moved = blocks.splice(fromIdx, 1)[0];
            const toIdx = targetCard
                ? blocks.findIndex(b => b.id === targetCard.dataset.blockId)
                : blocks.length;
            blocks.splice(toIdx < 0 ? blocks.length : toIdx, 0, moved);
            renderCanvas();
            await persistOrder();
            return;
        }

        // New block from sidebar
        if (dragType) {
            await addBlock(dragType);
        }
    });
}

async function addBlock(type) {
    try {
        const res = await ajaxPost(window.PAGE_URLS.blockStore, {
            block_type: type,
            position: blocks.length + 1,
            is_visible: true,
            device_target: 'all',
            config: {},
        });
        const block = res.block || res.data || res;
        blocks.push({
            id: block.id,
            block_type: block.block_type || type,
            position: block.position ?? blocks.length + 1,
            is_visible: block.is_visible ?? true,
            section_id: block.section_id ?? null,
            device_target: block.device_target ?? 'all',
            config: block.config || {},
            slides_count: 0,
            ad_images_count: 0,
            products_count: 0,
        });
        renderCanvas();
        selectBlock(block.id);
        Toast.success('Block added');
    } catch (err) {
        Toast.error(err?.responseJSON?.message || 'Failed to add block');
    }
}

async function persistOrder() {
    try {
        await ajaxPost(window.PAGE_URLS.blocksReorder, {
            ordered_ids: blocks.map(b => b.id),
        });
    } catch (err) {
        Toast.error('Failed to save block order');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Canvas actions (move up/down, delete, select)
// ─────────────────────────────────────────────────────────────────────────────

function initCanvasActions() {
    const canvas = $canvas();
    if (!canvas) return;

    canvas.addEventListener('click', async (e) => {
        const actBtn = e.target.closest('.cb-act');
        const card = e.target.closest('.canvas-block');
        if (!card) return;
        const id = card.dataset.blockId;

        if (actBtn) {
            e.stopPropagation();
            const action = actBtn.dataset.action;
            if (action === 'up') return moveBlock(id, -1);
            if (action === 'down') return moveBlock(id, 1);
            if (action === 'delete') return deleteBlock(id);
        }
        selectBlock(id);
    });
}

async function moveBlock(id, delta) {
    const idx = blocks.findIndex(b => b.id === id);
    if (idx < 0) return;
    const next = idx + delta;
    if (next < 0 || next >= blocks.length) return;
    [blocks[idx], blocks[next]] = [blocks[next], blocks[idx]];
    renderCanvas();
    await persistOrder();
}

async function deleteBlock(id) {
    if (!confirm('Delete this block?')) return;
    try {
        await ajaxDel(blockUrl(id));
        blocks = blocks.filter(b => b.id !== id);
        if (selectedBlockId === id) closePanel();
        renderCanvas();
        Toast.success('Block deleted');
    } catch (err) {
        Toast.error(err?.responseJSON?.message || 'Failed to delete');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Config panel
// ─────────────────────────────────────────────────────────────────────────────

function selectBlock(id) {
    selectedBlockId = id;
    document.querySelectorAll('.canvas-block').forEach(n => {
        n.classList.toggle('selected', n.dataset.blockId === id);
    });
    openPanel(id);
}

function openPanel(id) {
    const block = blocks.find(b => b.id === id);
    if (!block) return;
    const def = getBlockDef(block.block_type);
    const cfg = block.config || {};

    document.getElementById('panel-title').innerHTML =
        `<i class="ti ${def.icon}" aria-hidden="true" style="margin-right:6px"></i>${escapeHtml(def.label)}`;

    const visibilitySection = `
        <div class="section-label">Visibility</div>
        ${fieldToggle('__is_visible', 'Visible on page', block.is_visible)}
        <div class="field">
            <label>Device target</label>
            <select data-block-key="device_target">
                <option value="all"     ${block.device_target === 'all' ? 'selected' : ''}>All devices</option>
                <option value="mobile"  ${block.device_target === 'mobile' ? 'selected' : ''}>Mobile only</option>
                <option value="desktop" ${block.device_target === 'desktop' ? 'selected' : ''}>Desktop only</option>
            </select>
        </div>
        <div class="section-label">Settings</div>
    `;

    document.getElementById('panel-body').innerHTML = visibilitySection + def.config(cfg);
    $panel().classList.remove('closed');
}

function closePanel() {
    selectedBlockId = null;
    $panel().classList.add('closed');
    document.querySelectorAll('.canvas-block.selected').forEach(n => n.classList.remove('selected'));
}

function readPanelValues() {
    const out = { config: {}, is_visible: true, device_target: 'all' };
    const body = document.getElementById('panel-body');

    body.querySelectorAll('[data-config-key]').forEach(el => {
        const key = el.dataset.configKey;
        if (el.classList && el.classList.contains('tog')) {
            out.config[key] = el.classList.contains('on');
        } else if (el.tagName === 'INPUT' && el.type === 'number') {
            out.config[key] = el.value === '' ? null : Number(el.value);
        } else {
            out.config[key] = el.value;
        }
    });

    // Convert raw textareas
    if ('options_raw' in out.config) {
        out.config.options = String(out.config.options_raw || '')
            .split('\n').map(s => s.trim()).filter(Boolean);
        delete out.config.options_raw;
    }
    if ('steps_raw' in out.config) {
        out.config.steps = String(out.config.steps_raw || '')
            .split('\n').map(line => {
                const [t, d] = line.split('|');
                return { title: (t || '').trim(), description: (d || '').trim() };
            }).filter(s => s.title);
        delete out.config.steps_raw;
    }
    if ('category_ids' in out.config) {
        out.config.category_ids = String(out.config.category_ids || '')
            .split(',').map(s => s.trim()).filter(Boolean);
    }
    if ('brand_ids' in out.config) {
        out.config.brand_ids = String(out.config.brand_ids || '')
            .split(',').map(s => s.trim()).filter(Boolean);
    }

    // Toggles tagged with __is_visible
    body.querySelectorAll('.tog[data-config-key="__is_visible"]').forEach(el => {
        out.is_visible = el.classList.contains('on');
    });
    delete out.config.__is_visible;

    // Block-level fields
    body.querySelectorAll('[data-block-key]').forEach(el => {
        out[el.dataset.blockKey] = el.value;
    });

    return out;
}

async function savePanel() {
    if (!selectedBlockId) return;
    const block = blocks.find(b => b.id === selectedBlockId);
    if (!block) return;
    const data = readPanelValues();
    try {
        const res = await ajaxPut(blockUrl(selectedBlockId), data);
        const updated = res.block || res.data || res;
        Object.assign(block, {
            config: updated.config ?? data.config,
            is_visible: updated.is_visible ?? data.is_visible,
            device_target: updated.device_target ?? data.device_target,
        });
        renderCanvas();
        // Re-highlight selected block
        document.querySelectorAll('.canvas-block').forEach(n => {
            if (n.dataset.blockId === selectedBlockId) n.classList.add('selected');
        });
        Toast.success('Block saved');
    } catch (err) {
        Toast.error(err?.responseJSON?.message || 'Failed to save block');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// "Manage slides / ad images / products" hooks → existing modals
// ─────────────────────────────────────────────────────────────────────────────

function initManageButtons() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-manage]');
        if (!btn || !selectedBlockId) return;
        const kind = btn.dataset.manage;

        if (kind === 'slides') {
            return openSlideEditModal({ _block_id: selectedBlockId });
        }
        if (kind === 'ad_images') {
            return openAdImageEditModal({ _block_id: selectedBlockId });
        }
        if (kind === 'products') {
            // Lightweight prompt-based attach until a dedicated picker is built
            const variantId = prompt('Product variant ID to attach to this block:');
            if (!variantId) return;
            ajaxPost(blockUrl(selectedBlockId, '/products'), { product_variant_id: variantId })
                .done(() => Toast.success('Product attached'))
                .fail((err) => Toast.error(err?.responseJSON?.message || 'Failed to attach product'));
        }
    });
}

function openSlideEditModal(prefill = {}) {
    const form = document.getElementById('slide-edit-form');
    if (!form) return;
    form.reset();
    Object.entries(prefill).forEach(([k, v]) => {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v;
    });
    document.getElementById('slide-edit-modal').dispatchEvent(new Event('open'));
}

function openAdImageEditModal(prefill = {}) {
    const form = document.getElementById('ad-image-edit-form');
    if (!form) return;
    form.reset();
    Object.entries(prefill).forEach(([k, v]) => {
        const el = form.querySelector(`[name="${k}"]`);
        if (el) el.value = v;
    });
    document.getElementById('ad-image-edit-modal').dispatchEvent(new Event('open'));
}

// ─────────────────────────────────────────────────────────────────────────────
// Toolbar: clear / save layout
// ─────────────────────────────────────────────────────────────────────────────

function initToolbar() {
    document.getElementById('btn-canvas-clear')?.addEventListener('click', async () => {
        if (!blocks.length) return Toast.info('Canvas is already empty');
        if (!confirm(`Delete all ${blocks.length} block(s) from this page?`)) return;
        for (const b of [...blocks]) {
            try { await ajaxDel(blockUrl(b.id)); } catch (_) { }
        }
        blocks = [];
        closePanel();
        renderCanvas();
        Toast.success('Canvas cleared');
    });

    document.getElementById('btn-canvas-save')?.addEventListener('click', async () => {
        // Save the currently open panel (if any) and persist order
        try {
            if (selectedBlockId) await savePanel();
            await persistOrder();
            Toast.success('Layout saved');
        } catch (err) {
            Toast.error('Failed to save layout');
        }
    });

    document.getElementById('btn-panel-close')?.addEventListener('click', closePanel);
    document.getElementById('btn-panel-cancel')?.addEventListener('click', closePanel);
    document.getElementById('btn-panel-save')?.addEventListener('click', savePanel);

    // Toggle clicks (delegate)
    document.addEventListener('click', (e) => {
        const tog = e.target.closest('.tog');
        if (!tog) return;
        tog.classList.toggle('on');
        tog.setAttribute('aria-checked', tog.classList.contains('on'));
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Page-level actions: publish / unpublish / clone / archive / delete / meta
// ─────────────────────────────────────────────────────────────────────────────

function initTopBar() {
    const openModal = (id) => document.getElementById(id)?.dispatchEvent(new Event('open'));

    $('#btn-publish').on('click', () => {
        $('#publish-form [name=action]').val('publish');
        $('#schedule-date-wrap').addClass('hidden');
        openModal('publish-modal');
    });
    $('#btn-schedule').on('click', () => {
        $('#publish-form [name=action]').val('schedule');
        $('#schedule-date-wrap').removeClass('hidden');
        openModal('publish-modal');
    });
    $('#btn-edit-meta').on('click', () => openModal('edit-meta-modal'));

    $('#publish-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        ajaxPost(window.PAGE_URLS.publish, data)
            .done(() => { Toast.success('Page published'); setTimeout(() => location.reload(), 600); })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Publish failed'));
    });

    $('#edit-meta-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        ajaxPut(window.PAGE_URLS.update, data)
            .done(() => { Toast.success('Saved'); setTimeout(() => location.reload(), 400); })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Update failed'));
    });

    $('#slide-edit-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        const blockId = data._block_id; const slideId = data._slide_id;
        delete data._block_id; delete data._slide_id;
        const url = slideId
            ? `/page-builder/slides/${slideId}`
            : blockUrl(blockId, '/slides');
        ajax(slideId ? 'PUT' : 'POST', url, data)
            .done(() => {
                Toast.success('Slide saved');
                document.getElementById('slide-edit-modal').dispatchEvent(new Event('close'));
            })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Save failed'));
    });

    $('#ad-image-edit-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        const blockId = data._block_id; const itemId = data._item_id;
        delete data._block_id; delete data._item_id;
        const url = itemId
            ? `/page-builder/ad-images/${itemId}`
            : blockUrl(blockId, '/ad-images');
        ajax(itemId ? 'PUT' : 'POST', url, data)
            .done(() => {
                Toast.success('Image saved');
                document.getElementById('ad-image-edit-modal').dispatchEvent(new Event('close'));
            })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Save failed'));
    });

    $('#btn-unpublish').on('click', () => {
        if (!confirm('Unpublish this page?')) return;
        ajaxPost(window.PAGE_URLS.publish.replace('/publish', '/unpublish'), {})
            .done(() => location.reload())
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Failed'));
    });

    $('#btn-clone').on('click', () => {
        const name = prompt('Name for the cloned page:');
        if (!name) return;
        ajaxPost(window.PAGE_URLS.clone, { name })
            .done((res) => {
                Toast.success('Cloned');
                const id = res?.page?.id || res?.id;
                if (id) location.href = window.PAGE_URLS.indexUrl.replace(/\/?$/, '') + '/' + id;
            })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Clone failed'));
    });

    $('#btn-archive').on('click', () => {
        if (!confirm('Archive this page?')) return;
        ajaxPost(window.PAGE_URLS.publish.replace('/publish', '/archive'), {})
            .done(() => location.reload())
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Failed'));
    });

    $('#btn-delete').on('click', () => {
        if (!confirm('Permanently delete this page?')) return;
        ajaxDel(window.PAGE_URLS.destroy)
            .done(() => { location.href = window.PAGE_URLS.indexUrl; })
            .fail((err) => Toast.error(err?.responseJSON?.message || 'Delete failed'));
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Boot
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    if (!document.getElementById('pb')) return;

    blocks = (window.INITIAL_BLOCKS || [])
        .slice()
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0))
        .map(b => ({ ...b, config: b.config || {} }));

    initCanvasDnd();
    initCanvasActions();
    initManageButtons();
    initToolbar();
    initTopBar();
    renderCanvas();
});
