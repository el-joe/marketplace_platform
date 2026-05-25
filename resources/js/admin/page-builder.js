// ─────────────────────────────────────────────────────────────────────────────
// Page Builder JS
// jQuery 3.7 + Alpine.js 3 + SortableJS (loaded via CDN in layout if needed)
// ─────────────────────────────────────────────────────────────────────────────

// ── Helpers ──────────────────────────────────────────────────────────────────

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

function ajaxPost(url, data) {
    return $.ajax({
        url,
        method: 'POST',
        data,
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function ajaxPut(url, data) {
    return $.ajax({
        url,
        method: 'POST',
        data: Object.assign({}, data, { _method: 'PUT' }),
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function ajaxDelete(url) {
    return $.ajax({
        url,
        method: 'POST',
        data: { _method: 'DELETE' },
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
}

function openModal(id) {
    document.getElementById(id)?.dispatchEvent(new Event('open'));
}

function closeModal(id) {
    document.getElementById(id)?.dispatchEvent(new Event('close'));
}

function blockUrl(path) {
    return `/admin/page-builder/blocks/${path}`;
}

function slideUrl(path) {
    return `/admin/page-builder/slides/${path}`;
}

function adImageUrl(path) {
    return `/admin/page-builder/ad-images/${path}`;
}

function blockProductUrl(path) {
    return `/admin/page-builder/products/${path}`;
}

// ─────────────────────────────────────────────────────────────────────────────
// Create Page Form (create.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    const $createForm = $('#create-page-form');
    if (!$createForm.length) return;

    $createForm.on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Creating…');

        ajaxPost(window.location.pathname.replace(/\/$/, '') + '/store', new FormData(this))
            .done(function (res) {
                window.Toast?.success(res.message ?? 'Page created.');
                if (res.redirect) setTimeout(() => { window.location.href = res.redirect; }, 400);
            })
            .fail(function (xhr) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.values(errors).flat().forEach(e => window.Toast?.error(e));
                if (!Object.keys(errors).length) window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.');
            })
            .always(() => $btn.prop('disabled', false).text('Create & Open Editor'));
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Page Editor (show.blade.php)
// ─────────────────────────────────────────────────────────────────────────────

$(function () {
    if (typeof window.PAGE_ID === 'undefined') return;

    const URLS = window.PAGE_URLS;
    let activeBlockId = null;  // block being configured
    let activeSlideId = null;  // slide being edited
    let activeAdImageId = null;  // ad image being edited

    // ── State ─────────────────────────────────────────────────────────────────
    let blocks = window.INITIAL_BLOCKS || [];

    // ── DOM helpers ───────────────────────────────────────────────────────────

    function blockTypeToConfigTemplate(type) {
        if (['ad_images_2col', 'ad_images_3col', 'ad_images_4col', 'split_banner'].includes(type)) {
            return 'ad_images';
        }
        if (['product_grid', 'product_row'].includes(type)) {
            return 'product_list';
        }
        return type;
    }

    function renderBlockCard(block) {
        const visIcon = block.is_visible ? '👁' : '🚫';
        const deviceLabel = block.device_target !== 'all' ? `<span class="text-[10px] bg-gray-100 rounded px-1.5">${block.device_target}</span>` : '';

        let summary = '';
        if (block.config?.title_en) summary = `<span class="text-xs font-medium text-gray-800 truncate">${block.config.title_en}</span> `;
        if (block.slides_count) summary += `<span class="text-xs text-gray-400">${block.slides_count} slide(s)</span>`;
        if (block.ad_images_count) summary += `<span class="text-xs text-gray-400">${block.ad_images_count} image(s)</span>`;
        if (block.products_count) summary += `<span class="text-xs text-gray-400">${block.products_count} product(s)</span>`;

        return `
            <div class="block-card group relative bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow"
                 data-block-id="${block.id}" data-block-type="${block.block_type}">
                <div class="drag-handle absolute left-2 top-1/2 -translate-y-1/2 cursor-grab text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity">
                    ⠿
                </div>
                <div class="pl-8 pr-4 py-3 flex items-center gap-3">
                    <span class="badge badge-gray text-[10px] flex-shrink-0">${block.block_type}</span>
                    <div class="flex-1 min-w-0">${summary}</div>
                    <span class="text-sm" title="${block.is_visible ? 'Visible' : 'Hidden'}">${visIcon}</span>
                    ${deviceLabel}
                    <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                        <button type="button" class="btn-block-toggle btn btn-ghost btn-xs" data-block-id="${block.id}">Toggle</button>
                        <button type="button" class="btn-block-configure btn btn-secondary btn-xs" data-block-id="${block.id}" data-block-type="${block.block_type}">Configure</button>
                        <button type="button" class="btn-block-revisions btn btn-ghost btn-xs" data-block-id="${block.id}" title="History">🕒</button>
                        <button type="button" class="btn-block-delete btn btn-danger btn-xs" data-block-id="${block.id}">✕</button>
                    </div>
                </div>
            </div>`;
    }

    function appendBlockToCanvas(block) {
        $('#empty-canvas').hide();
        const html = renderBlockCard(block);
        if (block.section_id) {
            $(`.section-blocks-list[data-section-id="${block.section_id}"]`).append(html);
        } else {
            $('#canvas-blocks').append(html);
        }
    }

    // ── Add block (left panel buttons) ───────────────────────────────────────

    $(document).on('click', '.add-block-btn', function () {
        const blockType = $(this).data('block-type');
        const $btn = $(this).prop('disabled', true);

        ajaxPost(URLS.blockStore, { block_type: blockType })
            .done(function (res) {
                blocks.push(res.block);
                appendBlockToCanvas(res.block);
                window.Toast?.success(`${blockType} block added.`);
            })
            .fail(function () { window.Toast?.error('Failed to add block.'); })
            .always(() => $btn.prop('disabled', false));
    });

    // ── Toggle block visibility ───────────────────────────────────────────────

    $(document).on('click', '.btn-block-toggle', function () {
        const blockId = $(this).data('block-id');
        ajaxPost(blockUrl(`${blockId}/toggle-visibility`), {})
            .done(function (res) {
                const block = blocks.find(b => b.id === blockId);
                if (block) block.is_visible = res.is_visible;
                // Reload the card
                $(`.block-card[data-block-id="${blockId}"]`).replaceWith(
                    renderBlockCard(block)
                );
            })
            .fail(() => window.Toast?.error('Failed to toggle visibility.'));
    });

    // ── Delete block ─────────────────────────────────────────────────────────

    $(document).on('click', '.btn-block-delete', function () {
        const blockId = $(this).data('block-id');
        if (!window.confirm('Delete this block?')) return;

        ajaxDelete(blockUrl(blockId))
            .done(function () {
                blocks = blocks.filter(b => b.id !== blockId);
                $(`.block-card[data-block-id="${blockId}"]`).remove();
                window.Toast?.success('Block deleted.');
            })
            .fail(() => window.Toast?.error('Delete failed.'));
    });

    // ── Block revision history ────────────────────────────────────────────────

    $(document).on('click', '.btn-block-revisions', function () {
        const blockId = $(this).data('block-id');
        openModal('revisions-modal');
        $('#revisions-body').html('<p class="text-center text-gray-400">Loading…</p>');

        $.get(blockUrl(`${blockId}/revisions`))
            .done(function (res) {
                if (!res.revisions?.length) {
                    $('#revisions-body').html('<p class="text-gray-400 italic">No revisions yet.</p>');
                    return;
                }
                let html = '<div class="space-y-2">';
                res.revisions.forEach(r => {
                    html += `<div class="border border-gray-100 rounded p-2 text-xs">
                        <div class="flex justify-between">
                            <span class="font-medium">#${r.revision_number} — ${r.change_type}</span>
                            <span class="text-gray-400">${r.changed_at?.slice(0, 10) ?? ''}</span>
                        </div>
                        <div class="text-gray-500">by ${r.changed_by}${r.change_reason ? ` · ${r.change_reason}` : ''}</div>
                    </div>`;
                });
                html += '</div>';
                $('#revisions-body').html(html);
            })
            .fail(() => $('#revisions-body').html('<p class="text-danger-600">Failed to load revisions.</p>'));
    });

    // ── Configure block (open config modal) ───────────────────────────────────

    $(document).on('click', '.btn-block-configure', function () {
        const blockId = $(this).data('block-id');
        const blockType = $(this).data('block-type');
        activeBlockId = blockId;

        openConfigModal(blockId, blockType);
    });

    function openConfigModal(blockId, blockType) {
        const templateKey = blockTypeToConfigTemplate(blockType);
        const $template = $(`template[data-type="${templateKey}"]`).first();
        const $metaFields = $('#block-meta-fields').clone().removeAttr('id');

        if (!$template.length) {
            window.Toast?.warning('No config available for this block type.');
            return;
        }

        // Clone template content
        const $content = $($template[0].content.cloneNode(true));
        const $body = $('#block-config-body').empty().append($content).append($metaFields);

        // Init flatpickr on any datetime inputs in the modal
        $body.find('.flatpickr-datetime').each(function () {
            if (window.flatpickr) flatpickr(this, { enableTime: true, dateFormat: 'Y-m-d H:i' });
        });

        // Pre-fill with current block config
        const block = blocks.find(b => b.id === blockId);
        if (block) {
            fillConfigForm($body, block.config, 'config');
            fillConfigForm($body, {
                visible_from: block.visible_from,
                visible_until: block.visible_until,
                device_target: block.device_target,
                cache_ttl_seconds: block.cache_ttl_seconds,
            }, 'meta');
        }

        // For slider blocks: load slides
        if (blockType === 'hero_slider') {
            loadSlides(blockId);
        }

        // For ad_images blocks: load items
        if (['ad_images_2col', 'ad_images_3col', 'ad_images_4col', 'split_banner'].includes(blockType)) {
            loadAdImages(blockId);
        }

        // For product blocks: load products
        if (['product_grid', 'product_row'].includes(blockType)) {
            loadBlockProducts(blockId);
        }

        openModal('block-config-modal');
    }

    function fillConfigForm($container, data, prefix) {
        if (!data) return;
        Object.entries(data).forEach(([key, val]) => {
            if (val === null || val === undefined) return;
            const $field = $container.find(`[name="${prefix}[${key}]"]`);
            if ($field.is('[type=checkbox]')) {
                $field.prop('checked', !!val);
            } else if ($field.length) {
                $field.val(val);
            }
        });
    }

    // ── Save block config ─────────────────────────────────────────────────────

    $('#btn-save-block-config').on('click', function () {
        if (!activeBlockId) return;
        const $body = $('#block-config-body');
        const config = {};
        const meta = {};

        $body.find('[name^="config["]').each(function () {
            const key = $(this).attr('name').match(/config\[(.+)\]/)?.[1];
            if (key) config[key] = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();
        });

        $body.find('[name^="meta["]').each(function () {
            const key = $(this).attr('name').match(/meta\[(.+)\]/)?.[1];
            if (key) meta[key] = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();
        });

        const $btn = $(this).prop('disabled', true).text('Saving…');

        ajaxPut(blockUrl(activeBlockId), { config, ...meta })
            .done(function (res) {
                const idx = blocks.findIndex(b => b.id === activeBlockId);
                if (idx !== -1) blocks[idx] = { ...blocks[idx], ...res.block };
                $(`.block-card[data-block-id="${activeBlockId}"]`).replaceWith(renderBlockCard(blocks[idx]));
                closeModal('block-config-modal');
                window.Toast?.success('Block updated.');
            })
            .fail(() => window.Toast?.error('Save failed.'))
            .always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    // ── Drag-to-reorder blocks (simple HTML5 drag) ────────────────────────────

    let dragSrc = null;

    $(document).on('dragstart', '.block-card', function (e) {
        dragSrc = this;
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        $(this).addClass('opacity-50');
    });

    $(document).on('dragend', '.block-card', function () {
        $(this).removeClass('opacity-50');
        dragSrc = null;
        saveBlockOrder();
    });

    $(document).on('dragover', '.block-card', function (e) {
        e.preventDefault();
        if (!dragSrc || dragSrc === this) return;
        const rect = this.getBoundingClientRect();
        const mid = rect.top + rect.height / 2;
        if (e.originalEvent.clientY < mid) {
            $(this).before(dragSrc);
        } else {
            $(this).after(dragSrc);
        }
    });

    // Make cards draggable
    $(document).on('mouseenter', '.block-card', function () {
        $(this).attr('draggable', true);
    });

    function saveBlockOrder() {
        const orderedIds = [];
        $('#canvas-blocks .block-card, .section-blocks-list .block-card').each(function () {
            orderedIds.push($(this).data('block-id'));
        });

        ajaxPost(URLS.blocksReorder, { ordered_ids: orderedIds })
            .fail(() => window.Toast?.warning('Could not save block order.'));
    }

    // ── Sections ─────────────────────────────────────────────────────────────

    $('#btn-add-section').on('click', function () {
        $('[name="_section_id"]').val('');
        $('[name="name"]', '#add-section-form').val('');
        openModal('add-section-modal');
    });

    $(document).on('click', '.btn-section-edit', function () {
        const id = $(this).data('section-id');
        const name = $(this).data('section-name');
        $('[name="_section_id"]').val(id);
        $('[name="name"]', '#add-section-form').val(name);
        openModal('add-section-modal');
    });

    $(document).on('click', '.btn-section-delete', function () {
        const id = $(this).data('section-id');
        if (!window.confirm('Delete this section? Blocks will be un-sectioned.')) return;

        ajaxDelete(`/admin/page-builder/sections/${id}`)
            .done(function () {
                const $section = $(`.page-section[data-section-id="${id}"]`);
                // Move orphaned blocks to the main canvas
                $section.find('.block-card').appendTo('#canvas-blocks');
                $section.remove();
                window.Toast?.success('Section deleted.');
            })
            .fail(() => window.Toast?.error('Delete failed.'));
    });

    $('#add-section-form').on('submit', function (e) {
        e.preventDefault();
        const sectionId = $('[name="_section_id"]').val();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');

        const data = $(this).serialize();

        if (sectionId) {
            ajaxPut(`/admin/page-builder/sections/${sectionId}`, data)
                .done(function () {
                    closeModal('add-section-modal');
                    location.reload();
                })
                .fail(() => window.Toast?.error('Update failed.'));
        } else {
            ajaxPost(URLS.sectionStore, data)
                .done(function () {
                    closeModal('add-section-modal');
                    location.reload();
                })
                .fail(() => window.Toast?.error('Failed to add section.'));
        }
        $btn.prop('disabled', false).text('Save Section');
    });

    // ── Publish / lifecycle ───────────────────────────────────────────────────

    $('#btn-publish').on('click', () => openModal('publish-modal'));

    $('#btn-unpublish').on('click', function () {
        if (!window.confirm('Unpublish this page and return it to draft?')) return;
        ajaxPost(URLS.publish, { action: 'unpublish' })
            .done(() => { window.Toast?.success('Page unpublished.'); setTimeout(() => location.reload(), 500); })
            .fail((xhr) => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'));
    });

    $('#btn-archive').on('click', function () {
        if (!window.confirm('Archive this page?')) return;
        ajaxPost(URLS.publish, { action: 'archive' })
            .done(() => { window.Toast?.success('Page archived.'); setTimeout(() => location.reload(), 500); })
            .fail((xhr) => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'));
    });

    $('#publish-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Publishing…');

        ajaxPost(URLS.publish, $(this).serialize())
            .done(function () {
                window.Toast?.success('Page published!');
                closeModal('publish-modal');
                setTimeout(() => location.reload(), 500);
            })
            .fail((xhr) => window.Toast?.error(xhr.responseJSON?.message ?? 'Publish failed.'))
            .always(() => $btn.prop('disabled', false).text('Publish'));
    });

    // ── Clone ─────────────────────────────────────────────────────────────────

    $('#btn-clone').on('click', function () {
        if (!window.confirm('Clone this page as a draft copy?')) return;
        ajaxPost(URLS.clone, {})
            .done(function (res) {
                window.Toast?.success(res.message ?? 'Page cloned.');
                if (res.redirect) setTimeout(() => { window.location.href = res.redirect; }, 600);
            })
            .fail(() => window.Toast?.error('Clone failed.'));
    });

    // ── Delete ────────────────────────────────────────────────────────────────

    $('#btn-delete').on('click', function () {
        if (!window.confirm('Permanently delete this page? This cannot be undone.')) return;
        ajaxDelete(URLS.destroy)
            .done(function (res) {
                window.Toast?.success('Page deleted.');
                if (res.redirect) setTimeout(() => { window.location.href = res.redirect; }, 500);
            })
            .fail(() => window.Toast?.error('Delete failed.'));
    });

    // ── Edit meta ──────────────────────────────────────────────────────────────

    $('#btn-edit-meta').on('click', () => openModal('edit-meta-modal'));

    $('#edit-meta-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');

        ajaxPut(URLS.update, $(this).serialize())
            .done(() => {
                window.Toast?.success('Page meta updated.');
                closeModal('edit-meta-modal');
            })
            .fail(() => window.Toast?.error('Update failed.'))
            .always(() => $btn.prop('disabled', false).text('Save'));
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Slide management
    // ─────────────────────────────────────────────────────────────────────────

    function loadSlides(blockId) {
        const $list = $('#slides-list').html('<p class="text-xs text-gray-400">Loading…</p>');

        $.get(blockUrl(`${blockId}/slides`))
            .done(function (res) {
                $list.empty();
                if (!res.slides?.length) {
                    $list.html('<p class="text-xs text-gray-400 italic">No slides yet.</p>');
                    return;
                }
                res.slides.forEach(slide => $list.append(renderSlideRow(slide)));
            })
            .fail(() => $list.html('<p class="text-xs text-danger-600">Failed to load slides.</p>'));
    }

    function renderSlideRow(slide) {
        const img = slide.desktop_image
            ? `<img src="${slide.desktop_image}" class="w-10 h-7 object-cover rounded flex-shrink-0">`
            : `<div class="w-10 h-7 bg-gray-200 rounded flex-shrink-0"></div>`;

        return `
            <div class="slide-row flex items-center gap-2 bg-gray-50 rounded-lg px-2 py-1.5" data-slide-id="${slide.id}">
                ${img}
                <span class="flex-1 text-xs truncate">${slide.title_en || 'Untitled'}</span>
                <span class="text-[10px] ${slide.is_active ? 'text-success-600' : 'text-gray-400'}">${slide.is_active ? 'On' : 'Off'}</span>
                <button type="button" class="btn-slide-edit btn btn-ghost btn-xs" data-slide-id="${slide.id}">Edit</button>
                <button type="button" class="btn-slide-delete btn btn-danger btn-xs" data-slide-id="${slide.id}">✕</button>
            </div>`;
    }

    // Add slide
    $(document).on('click', '.btn-add-slide', function () {
        openSlideModal(null, activeBlockId);
    });

    // Edit slide
    $(document).on('click', '.btn-slide-edit', function () {
        const slideId = $(this).data('slide-id');
        openSlideModal(slideId, activeBlockId);
    });

    function openSlideModal(slideId, blockId) {
        activeSlideId = slideId;
        $('#slide-edit-form [name="_block_id"]').val(blockId);
        $('#slide-edit-form [name="_slide_id"]').val(slideId || '');
        // Reset form
        $('#slide-edit-form')[0].reset();

        if (slideId) {
            $.get(slideUrl(slideId))
                .done(function (res) {
                    const s = res.slide;
                    Object.entries(s).forEach(([k, v]) => {
                        const $f = $(`#slide-edit-form [name="${k}"]`);
                        if ($f.is(':checkbox')) $f.prop('checked', !!v);
                        else if ($f.length) $f.val(v);
                    });
                    openModal('slide-edit-modal');
                });
        } else {
            openModal('slide-edit-modal');
        }
    }

    $('#slide-edit-form').on('submit', function (e) {
        e.preventDefault();
        const slideId = $('[name="_slide_id"]').val();
        const blockId = $('[name="_block_id"]').val();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');

        const data = $(this).serialize();

        const req = slideId
            ? ajaxPut(slideUrl(slideId), data)
            : ajaxPost(blockUrl(`${blockId}/slides`), data);

        req.done(function () {
            window.Toast?.success(slideId ? 'Slide updated.' : 'Slide added.');
            closeModal('slide-edit-modal');
            loadSlides(blockId);
            // Update block card count
            const block = blocks.find(b => b.id === blockId);
            if (block && !slideId) block.slides_count = (block.slides_count || 0) + 1;
        })
            .fail(() => window.Toast?.error('Save failed.'))
            .always(() => $btn.prop('disabled', false).text('Save Slide'));
    });

    // Delete slide
    $(document).on('click', '.btn-slide-delete', function () {
        const slideId = $(this).data('slide-id');
        if (!window.confirm('Delete this slide?')) return;
        ajaxDelete(slideUrl(slideId))
            .done(function () {
                $(`.slide-row[data-slide-id="${slideId}"]`).remove();
                window.Toast?.success('Slide deleted.');
                const block = blocks.find(b => b.id === activeBlockId);
                if (block) block.slides_count = Math.max(0, (block.slides_count || 1) - 1);
            })
            .fail(() => window.Toast?.error('Delete failed.'));
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Ad Image management
    // ─────────────────────────────────────────────────────────────────────────

    function loadAdImages(blockId) {
        const $list = $('#ad-images-list').html('<p class="text-xs text-gray-400">Loading…</p>');

        $.get(blockUrl(`${blockId}/ad-images`))
            .done(function (res) {
                $list.empty();
                if (!res.items?.length) {
                    $list.html('<p class="text-xs text-gray-400 italic">No images yet.</p>');
                    return;
                }
                res.items.forEach(item => $list.append(renderAdImageRow(item)));
            })
            .fail(() => $list.html('<p class="text-xs text-danger-600">Failed to load images.</p>'));
    }

    function renderAdImageRow(item) {
        const img = item.image_url
            ? `<img src="${item.image_url}" class="w-10 h-7 object-cover rounded flex-shrink-0">`
            : `<div class="w-10 h-7 bg-gray-200 rounded flex-shrink-0"></div>`;

        return `
            <div class="ad-image-row flex items-center gap-2 bg-gray-50 rounded-lg px-2 py-1.5" data-item-id="${item.id}">
                ${img}
                <span class="flex-1 text-xs truncate">${item.title_en || item.alt_text_en || 'Untitled'}</span>
                <span class="text-[10px] text-gray-400">${item.aspect_ratio || ''}</span>
                <button type="button" class="btn-ad-image-edit btn btn-ghost btn-xs" data-item-id="${item.id}">Edit</button>
                <button type="button" class="btn-ad-image-delete btn btn-danger btn-xs" data-item-id="${item.id}">✕</button>
            </div>`;
    }

    $(document).on('click', '.btn-add-ad-image', function () {
        openAdImageModal(null, activeBlockId);
    });

    $(document).on('click', '.btn-ad-image-edit', function () {
        openAdImageModal($(this).data('item-id'), activeBlockId);
    });

    function openAdImageModal(itemId, blockId) {
        activeAdImageId = itemId;
        $('#ad-image-edit-form [name="_item_id"]').val(itemId || '');
        $('#ad-image-edit-form [name="_block_id"]').val(blockId);
        $('#ad-image-edit-form')[0].reset();

        if (itemId) {
            $.get(adImageUrl(itemId))
                .done(function (res) {
                    const item = res.item;
                    Object.entries(item).forEach(([k, v]) => {
                        const $f = $(`#ad-image-edit-form [name="${k}"]`);
                        if ($f.is(':checkbox')) $f.prop('checked', !!v);
                        else if ($f.length) $f.val(v);
                    });
                    openModal('ad-image-edit-modal');
                });
        } else {
            openModal('ad-image-edit-modal');
        }
    }

    $('#ad-image-edit-form').on('submit', function (e) {
        e.preventDefault();
        const itemId = $('[name="_item_id"]').val();
        const blockId = $('[name="_block_id"]').val();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).text('Saving…');

        const data = $(this).serialize();

        const req = itemId
            ? ajaxPut(adImageUrl(itemId), data)
            : ajaxPost(blockUrl(`${blockId}/ad-images`), data);

        req.done(function () {
            window.Toast?.success(itemId ? 'Image updated.' : 'Image added.');
            closeModal('ad-image-edit-modal');
            loadAdImages(blockId);
            const block = blocks.find(b => b.id === blockId);
            if (block && !itemId) block.ad_images_count = (block.ad_images_count || 0) + 1;
        })
            .fail(() => window.Toast?.error('Save failed.'))
            .always(() => $btn.prop('disabled', false).text('Save Image'));
    });

    $(document).on('click', '.btn-ad-image-delete', function () {
        const itemId = $(this).data('item-id');
        if (!window.confirm('Delete this ad image?')) return;
        ajaxDelete(adImageUrl(itemId))
            .done(function () {
                $(`.ad-image-row[data-item-id="${itemId}"]`).remove();
                window.Toast?.success('Image deleted.');
            })
            .fail(() => window.Toast?.error('Delete failed.'));
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Block Products (product_grid / product_row curated)
    // ─────────────────────────────────────────────────────────────────────────

    function loadBlockProducts(blockId) {
        const $list = $('#block-products-list').html('<p class="text-xs text-gray-400">Loading…</p>');

        $.get(blockUrl(`${blockId}/products`))
            .done(function (res) {
                $list.empty();
                if (!res.items?.length) {
                    $list.html('<p class="text-xs text-gray-400 italic">No products yet. Search above to add.</p>');
                    return;
                }
                res.items.forEach(item => $list.append(renderProductRow(item)));
            })
            .fail(() => $list.html('<p class="text-xs text-danger-600">Failed to load products.</p>'));
    }

    function renderProductRow(item) {
        return `
            <div class="product-row flex items-center gap-2 bg-gray-50 rounded-lg px-2 py-1.5" data-item-id="${item.id}">
                <span class="flex-1 text-xs truncate">${item.name}</span>
                <button type="button" class="btn-block-product-remove btn btn-danger btn-xs" data-item-id="${item.id}">✕</button>
            </div>`;
    }

    // Product search
    $('#btn-product-search').on('click', function () {
        const q = $('#product-search-input').val().trim();
        if (!q) return;

        const $results = $('#product-search-results').html('<p class="text-xs text-gray-400">Searching…</p>').removeClass('hidden');

        $.get('/admin/products/search', { q })
            .done(function (res) {
                $results.empty();
                if (!res.data?.length) {
                    $results.html('<p class="text-xs text-gray-400 italic">No results.</p>');
                    return;
                }
                res.data.forEach(variant => {
                    $results.append(`
                        <div class="flex items-center gap-2 bg-white rounded px-2 py-1 border border-gray-100">
                            <span class="flex-1 text-xs truncate">${variant.name}</span>
                            <button type="button" class="btn-add-product btn btn-secondary btn-xs"
                                data-variant-id="${variant.id}" data-name="${variant.name}">+ Add</button>
                        </div>`);
                });
            })
            .fail(() => $results.html('<p class="text-xs text-danger-600">Search failed.</p>'));
    });

    $(document).on('click', '.btn-add-product', function () {
        const variantId = $(this).data('variant-id');
        const $btn = $(this).prop('disabled', true);

        ajaxPost(blockUrl(`${activeBlockId}/products`), { product_variant_id: variantId })
            .done(function (res) {
                $('#block-products-list .text-gray-400').remove();
                $('#block-products-list').append(renderProductRow(res.item));
                window.Toast?.success('Product added.');
                const block = blocks.find(b => b.id === activeBlockId);
                if (block) block.products_count = (block.products_count || 0) + 1;
            })
            .fail(() => window.Toast?.error('Failed to add product.'))
            .always(() => $btn.prop('disabled', false));
    });

    $(document).on('click', '.btn-block-product-remove', function () {
        const itemId = $(this).data('item-id');
        if (!window.confirm('Remove this product?')) return;
        ajaxDelete(blockProductUrl(itemId))
            .done(function () {
                $(`.product-row[data-item-id="${itemId}"]`).remove();
                window.Toast?.success('Product removed.');
            })
            .fail(() => window.Toast?.error('Remove failed.'));
    });

});
