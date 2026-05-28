/**
 * resources/js/admin/categories.js
 *
 * Category module:
 *  - Tree expand/collapse
 *  - Category checkbox selection + bulk commission modal
 *  - Featured toggle (AJAX)
 *  - Delete (AJAX)
 *  - AJAX PUT submit for edit mode (same pattern as products.js)
 */

import $ from 'jquery';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function isEditMode() {
    return $('#form-mode').val() === 'edit';
}

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('categories-table')) {
        initTree();
        initBulkCommission();
        initFeaturedToggle();
        initDeleteCategory();
    }

    if (document.getElementById('category-form')) {
        initFormSubmit();
    }
});

// ─── Tree expand / collapse ───────────────────────────────────────────────────

const expanded = new Set();

window.catTree = {
    toggle(id) {
        if (expanded.has(id)) {
            collapseNode(id);
        } else {
            expandNode(id);
        }
    },
};

function expandNode(id) {
    expanded.add(id);

    // Show immediate children (rows whose data-parent === id)
    document.querySelectorAll(`tr[data-parent="${id}"]`).forEach(function (row) {
        row.style.display = '';
    });

    // Rotate chevron
    const btn = document.querySelector(`.expand-btn[data-id="${id}"] svg`);
    if (btn) btn.style.transform = 'rotate(90deg)';
}

function collapseNode(id) {
    expanded.delete(id);

    // Find all descendants recursively and hide them
    collapseDescendants(id);

    // Rotate chevron back
    const btn = document.querySelector(`.expand-btn[data-id="${id}"] svg`);
    if (btn) btn.style.transform = '';
}

function collapseDescendants(id) {
    document.querySelectorAll(`tr[data-parent="${id}"]`).forEach(function (row) {
        row.style.display = 'none';
        const childId = row.dataset.id;
        if (childId && expanded.has(childId)) {
            expanded.delete(childId);
            collapseDescendants(childId);
        }
        // Reset child chevrons
        const btn = row.querySelector(`.expand-btn svg`);
        if (btn) btn.style.transform = '';
    });
}

function initTree() {
    document.querySelector('#categories-table')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.expand-btn[data-id]');
        if (!btn) return;
        window.catTree.toggle(btn.dataset.id);
    });
}

// ─── Checkbox selection + Bulk commission ─────────────────────────────────────

let selectedIds = [];

function initBulkCommission() {
    const table = document.getElementById('categories-table');
    const bulkBtn = document.getElementById('bulk-commission-btn');
    const modal = document.getElementById('bulk-commission-modal');
    const backdrop = document.getElementById('bulk-commission-backdrop');
    const cancelBtn = document.getElementById('bulk-commission-cancel');
    const applyBtn = document.getElementById('bulk-commission-apply');
    const rateInput = document.getElementById('bulk-rate-input');
    const countEl = document.getElementById('bulk-count');

    if (!table || !bulkBtn) return;

    table.addEventListener('change', function (e) {
        if (!e.target.matches('.category-cb')) return;
        const id = e.target.value;
        if (e.target.checked) {
            if (!selectedIds.includes(id)) selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(function (i) { return i !== id; });
        }
        bulkBtn.classList.toggle('hidden', selectedIds.length === 0);
    });

    bulkBtn?.addEventListener('click', function () {
        if (countEl) countEl.textContent = selectedIds.length;
        modal?.classList.remove('hidden');
    });

    backdrop?.addEventListener('click', function () { modal?.classList.add('hidden'); });
    cancelBtn?.addEventListener('click', function () { modal?.classList.add('hidden'); });

    applyBtn?.addEventListener('click', function () {
        const rate = rateInput?.value;
        if (rate === '' || rate === null) return;

        $.ajax({
            url: window.ROUTES_CAT?.bulkCommission,
            method: 'POST',
            data: JSON.stringify({ ids: selectedIds, commission_rate: rate }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        }).done(function (res) {
            window.Toast?.success(res.message || 'Commission updated.');
            modal?.classList.add('hidden');
            setTimeout(function () { window.location.reload(); }, 800);
        }).fail(function (xhr) {
            window.Toast?.error(xhr.responseJSON?.message || 'Failed to update commission.');
        });
    });
}

// ─── Featured toggle ──────────────────────────────────────────────────────────

function initFeaturedToggle() {
    document.getElementById('categories-table')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.featured-btn[data-id]');
        if (!btn) return;

        const id = btn.dataset.id;
        const url = btn.dataset.url;

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        }).done(function (res) {
            const isFeatured = res.is_featured;
            btn.dataset.featured = isFeatured ? '1' : '0';

            if (isFeatured) {
                btn.classList.remove('bg-gray-100', 'text-gray-500');
                btn.classList.add('bg-amber-100', 'text-amber-700');
                btn.querySelector('span').textContent = 'Featured';
            } else {
                btn.classList.remove('bg-amber-100', 'text-amber-700');
                btn.classList.add('bg-gray-100', 'text-gray-500');
                btn.querySelector('span').textContent = 'Add';
            }

            window.Toast?.success(isFeatured ? 'Marked as featured.' : 'Removed from featured.');
        }).fail(function (xhr) {
            window.Toast?.error(xhr.responseJSON?.message || 'Failed to toggle featured.');
        });
    });
}

// ─── Delete category ──────────────────────────────────────────────────────────

function initDeleteCategory() {
    document.getElementById('categories-table')?.addEventListener('click', async function (e) {
        const btn = e.target.closest('.delete-cat-btn[data-id]');
        if (!btn) return;

        const name = btn.dataset.name;
        const url = btn.dataset.url;

        const confirmed = window.confirmDelete
            ? await window.confirmDelete('Delete "' + name + '"? This cannot be undone.', { title: 'Delete category?' })
            : window.confirm('Delete "' + name + '"? This cannot be undone.');
        if (!confirmed) return;

        $.ajax({
            url: url,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        }).done(function (res) {
            window.Toast?.success(res.message || 'Category deleted.');
            const row = btn.closest('tr');
            row?.remove();
        }).fail(function (xhr) {
            window.Toast?.error(xhr.responseJSON?.message || 'Failed to delete category.');
        });
    });
}

// ─── Form submit (AJAX PUT for edit — same pattern as products.js) ────────────

function initFormSubmit() {
    if (!isEditMode()) return; // Create uses standard HTML POST

    $('#category-form').on('submit', function (e) {
        e.preventDefault();

        const $btn = $('#submit-btn').prop('disabled', true).text('Saving…');
        const formData = new FormData(this);
        formData.set('_method', 'PUT');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        })
            .done(function (res) {
                window.Toast?.success(res.message || 'Category saved.');
            })
            .fail(function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    const msgs = Object.values(errors).flat();
                    window.Toast?.error(msgs[0] || 'Validation error.');
                } else {
                    window.Toast?.error(xhr.responseJSON?.message || 'Save failed. Please try again.');
                }
            })
            .always(function () {
                $btn.prop('disabled', false).text('Save changes');
            });
    });
}
