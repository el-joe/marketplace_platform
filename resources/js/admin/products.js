/**
 * resources/js/admin/products.js
 *
 * Product create / edit form JS:
 *   - FilePond image upload (custom server config)
 *   - Category → variant attributes AJAX
 *   - GTIN duplicate check
 *   - Generate variant combinations
 *   - Variant row add / remove
 *   - Character counters
 *   - Countries enable / disable all
 *   - SEO preview live update
 *   - AJAX PUT submit for edit mode
 */

import $ from 'jquery';
import FilePond, { registerPlugin } from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

// Safe re-registration (file-upload.js may already have registered some plugins)
try { registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType, FilePondPluginFileValidateSize); } catch (_) { }

// ─── Helpers ──────────────────────────────────────────────────────────────────

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str ?? '')));
    return d.innerHTML;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function isEditMode() {
    return $('#form-mode').val() === 'edit';
}

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initCharCounters();
    initGtinCheck();
    initCategoryAttributes();
    initGenerateVariants();
    initVariantTableEvents();
    initCountryToggles();
    initSeoPreview();
    initFilePond();
    initFormSubmit();
});

// ─── Character counters ───────────────────────────────────────────────────────

function initCharCounters() {
    document.querySelectorAll('[data-char-counter]').forEach(function (counter) {
        const fieldId = counter.dataset.charCounter;
        const max = parseInt(counter.dataset.max, 10) || 0;
        const field = document.getElementById(fieldId);
        if (!field) return;

        function update() {
            const len = field.value.length;
            counter.textContent = len + ' / ' + max;
            counter.classList.toggle('text-red-600', max > 0 && len > max * 0.9);
        }

        field.addEventListener('input', update);
        update();
    });
}

// ─── GTIN duplicate check ─────────────────────────────────────────────────────

function initGtinCheck() {
    let gtinTimer;
    $(document).on('input', '#gtin-input', function () {
        clearTimeout(gtinTimer);
        const gtin = $(this).val().trim();
        $('#gtin-warning').addClass('hidden').empty();

        if (gtin.length !== 13) return;

        gtinTimer = setTimeout(function () {
            $.ajax({
                url: document.querySelector('meta[name="check-duplicate-url"]')?.content
                    || window.location.pathname.replace(/\/(create|[^/]+\/edit).*/, '') + '/check-duplicate',
                data: { gtin },
            }).done(function (res) {
                const data = res.data;
                if (!data.exists) return;

                const product = data.product;

                // Show inline warning
                const $warn = $('#gtin-warning');
                $warn.html(
                    '<strong>Duplicate barcode:</strong> ' + esc(product.name_en) +
                    ' <span class="text-xs opacity-70">(' + esc(product.status) + ')</span> — ' +
                    '<a href="' + esc(product.url) + '" target="_blank" class="underline font-medium">View product</a>'
                ).removeClass('hidden');

                // Also show Alpine modal
                const root = document.querySelector('[x-data]');
                if (root && root._x_dataStack) {
                    root._x_dataStack[0].duplicateProduct = product;
                    root._x_dataStack[0].showDuplicate = true;
                }
            });
        }, 600);
    });
}

// ─── Category → variant attributes ───────────────────────────────────────────

function initCategoryAttributes() {
    // Use Select2 change event bubbled to hidden input
    $(document).on('change', '[name="category_id"]', function () {
        const catId = $(this).val();
        if (!catId) {
            $('#variant-attributes-container').empty();
            return;
        }

        const url = '/admin/categories/' + encodeURIComponent(catId) + '/attributes';

        $.get(url).done(function (res) {
            const attrs = res.data ?? [];
            const $container = $('#variant-attributes-container');
            $container.empty();

            if (attrs.length === 0) {
                $container.html('<p class="text-sm text-gray-400 italic col-span-3">No variant attributes for this category.</p>');
                return;
            }

            attrs.forEach(function (attr) {
                $container.append(
                    '<label class="flex items-center gap-2 px-3 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-sm">' +
                    '<input type="checkbox" name="variant_attributes[]" value="' + esc(attr.id) + '" ' +
                    'class="rounded border-gray-300 text-primary-600 variant-attr-cb" />' +
                    esc(attr.name_en) +
                    '</label>'
                );
            });
        });
    });
}

// ─── Generate variant combinations ───────────────────────────────────────────

function initGenerateVariants() {
    $(document).on('click', '#generate-variants-btn', function () {
        const attrIds = [];
        document.querySelectorAll('.variant-attr-cb:checked').forEach(function (cb) {
            attrIds.push(cb.value);
        });

        if (attrIds.length === 0) {
            window.Toast && window.Toast.warning('Select at least one variant attribute first.');
            return;
        }

        const $btn = $(this).prop('disabled', true).text('Generating…');

        $.ajax({
            url: window.location.pathname.replace(/\/(create|[^/]+\/edit).*/, '') + '/generate-variants',
            method: 'POST',
            data: { attribute_ids: attrIds },
        })
            .done(function (res) {
                renderVariantRows(res.data ?? []);
            })
            .fail(function () {
                window.Toast && window.Toast.error('Failed to generate variants.');
            })
            .always(function () {
                $btn.prop('disabled', false).text('Generate combinations');
            });
    });
}

function renderVariantRows(variants) {
    const $tbody = $('#variants-tbody');
    $tbody.empty();

    if (variants.length === 0) {
        $('#no-variants-msg').removeClass('hidden');
        return;
    }

    $('#no-variants-msg').addClass('hidden');

    variants.forEach(function (v) {
        const i = v.index;
        const row = `
<tr class="variant-row hover:bg-gray-50">
  <td class="px-4 py-3 font-medium text-gray-800">${esc(v.name)}</td>
  <td class="px-4 py-3"><input type="text" name="variants[${i}][sku]" value="${esc(v.sku)}" placeholder="Auto-generate" class="form-input text-sm py-1.5 w-full" /></td>
  <td class="px-4 py-3"><input type="text" name="variants[${i}][barcode]" value="${esc(v.barcode)}" class="form-input text-sm py-1.5 w-full" /></td>
  <td class="px-4 py-3"><input type="number" name="variants[${i}][weight_grams]" value="${esc(v.weight_grams)}" min="0" class="form-input text-sm py-1.5 w-full" /></td>
  <td class="px-4 py-3 text-center">
    <input type="radio" name="variants_default" value="${i}" class="text-primary-600 border-gray-300 variant-default-radio" ${v.is_default ? 'checked' : ''} />
    <input type="hidden" name="variants[${i}][is_default]" value="${v.is_default ? '1' : '0'}" class="default-flag" />
  </td>
  <td class="px-4 py-3 text-center">
    <input type="checkbox" name="variants[${i}][is_active]" value="1" class="rounded text-primary-600 border-gray-300" ${v.is_active ? 'checked' : ''} />
  </td>
  <td class="px-4 py-3">
    <button type="button" class="remove-variant-row text-gray-400 hover:text-red-600 transition-colors" title="Remove">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
    </button>
  </td>
</tr>`;
        $tbody.append(row);
    });
}

// ─── Variant table: row remove + default radio sync ──────────────────────────

function initVariantTableEvents() {
    // Remove row
    $(document).on('click', '.remove-variant-row', function () {
        $(this).closest('tr.variant-row').remove();
        reindexVariantRows();
        if ($('#variants-tbody tr.variant-row').length === 0) {
            $('#no-variants-msg').removeClass('hidden');
        }
    });

    // Sync default radio → hidden is_default flags
    $(document).on('change', '.variant-default-radio', function () {
        $('#variants-tbody .default-flag').val('0');
        $(this).closest('tr').find('.default-flag').val('1');
    });
}

function reindexVariantRows() {
    $('#variants-tbody tr.variant-row').each(function (i) {
        $(this).find('input[name^="variants["]').each(function () {
            this.name = this.name.replace(/variants\[\d+\]/, 'variants[' + i + ']');
        });
        $(this).find('[name="variants_default"]').val(i);
    });
}

// ─── Country enable / disable all ────────────────────────────────────────────

function initCountryToggles() {
    $('#enable-all-countries').on('click', function () {
        setAllCountries(true);
    });

    $('#disable-all-countries').on('click', function () {
        setAllCountries(false);
    });
}

function setAllCountries(enable) {
    document.querySelectorAll('.country-avail-cb').forEach(function (cb) {
        cb.checked = enable;

        // Sync Alpine x-model
        cb.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

// ─── SEO preview live update ──────────────────────────────────────────────────

function initSeoPreview() {
    $(document).on('input', '#seo_title', function () {
        const val = $(this).val().trim();
        $('#seo-preview-title').text(val || $('[name="name_en"]').val() || 'Product title');
    });

    $(document).on('input', '#seo_description', function () {
        const val = $(this).val().trim();
        $('#seo-preview-desc').text(val || 'Add a meta description to improve search engine visibility.');
    });

    $(document).on('input', '[name="name_en"]', function () {
        const title = $('#seo_title').val().trim();
        if (!title) {
            $('#seo-preview-title').text($(this).val() || 'Product title');
        }
    });

    // Update slug preview from the slug-input component's hidden field
    $(document).on('input', '[name="slug"]', function () {
        $('#seo-preview-slug').text($(this).val() || 'product-slug');
    });
}

// ─── FilePond image upload ────────────────────────────────────────────────────

function initFilePond() {
    const inputEl = document.getElementById('product-images-filepond');
    if (!inputEl) return;

    const uploadUrl = inputEl.dataset.uploadUrl || buildAdminUrl('upload-image');
    const revertBase = inputEl.dataset.revertBase || buildAdminUrl('delete-image');

    const pond = FilePond.create(inputEl, {
        allowMultiple: true,
        allowReorder: true,
        maxFiles: 20,
        acceptedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
        maxFileSize: '5MB',
        labelIdle: 'Drag &amp; drop images or <span class="filepond--label-action">Browse</span>',

        server: {
            process: {
                url: uploadUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                onload: function (response) {
                    try {
                        return JSON.parse(response).id;
                    } catch (_) {
                        return response;
                    }
                },
            },
            revert: function (uniqueFileId, load, error) {
                $.ajax({
                    url: revertBase + '/' + encodeURIComponent(uniqueFileId),
                    method: 'DELETE',
                }).done(load).fail(function () { error('Failed to revert upload.'); });
            },
        },
    });

    // Load existing images in edit mode
    const existing = window.existingProductImages ?? [];
    if (existing.length > 0) {
        pond.addFiles(existing.map(function (img) {
            return {
                source: img.id,
                options: {
                    type: 'local',
                    file: { name: img.name, size: 0, type: 'image/jpeg' },
                    metadata: { url: img.url },
                },
            };
        }));
    }

    // Expose pond instance for debugging / external access
    window.productImagePond = pond;
}

/** Build the admin products URL base from current pathname. */
function buildAdminUrl(segment) {
    return window.location.pathname.replace(/\/(create|[^/]+\/edit).*/, '') + '/' + segment;
}

// ─── Form submit (AJAX PUT for edit) ─────────────────────────────────────────

function initFormSubmit() {
    if (!isEditMode()) return; // Create uses standard HTML POST

    $('#product-form').on('submit', function (e) {
        e.preventDefault();

        const $btn = $('#submit-btn').prop('disabled', true).text('Saving…');
        const formData = new FormData(this);
        formData.set('_method', 'PUT');

        const action = $(this).attr('action');

        $.ajax({
            url: action,
            method: 'POST', // tunnelled via _method=PUT
            data: formData,
            processData: false,
            contentType: false,
        })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message || 'Product saved.');
            })
            .fail(function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    const msgs = Object.values(errors).flat();
                    window.Toast && window.Toast.error(msgs[0] || 'Validation error.');
                } else {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Save failed. Please try again.');
                }
            })
            .always(function () {
                $btn.prop('disabled', false).text('Save changes');
            });
    });
}
