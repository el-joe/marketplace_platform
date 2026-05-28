/**
 * Admin – Banners Management JS
 *
 * Covers:
 *  - DataTable with server-side processing
 *  - Status-tab filtering
 *  - Filter form → DataTable params
 *  - Image file-input previews (desktop + mobile)
 *  - Form AJAX submit (create / edit)
 *  - Image removal (AJAX)
 *  - Delete & Duplicate confirmation modals
 *  - Date validation + duration preview
 */

import $ from 'jquery';
import DataTable from 'datatables.net-dt';

// ─── DataTable setup ──────────────────────────────────────────────────────────

let bannersTable;
let activeStatusFilter = '';

function initDataTable() {
    bannersTable = new DataTable('#banners-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/banners/datatable',
            type: 'POST',
            data(d) {
                d._token = window._token;
                d.status = activeStatusFilter;
                d.placement_code = $('#filter-placement').val();
                d.country_id = $('#filter-country').val();
                d.device_target = $('#filter-device').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
                d.search_value = $('#search-input').val();
            },
        },
        columns: [
            {
                data: 'image',
                orderable: false,
                searchable: false,
                render(data) {
                    if (!data) return '<div class="w-14 h-9 rounded bg-gray-100 flex items-center justify-center text-gray-300 text-xs">—</div>';
                    return `<img src="${data}" class="w-14 h-9 rounded object-cover border border-gray-200" alt="">`;
                },
            },
            { data: 'name', title: 'Name' },
            { data: 'placement_code', title: 'Placement', orderable: true },
            { data: 'country', title: 'Country', orderable: false },
            { data: 'status', title: 'Status', orderable: true },
            { data: 'device_target', title: 'Device', orderable: true },
            { data: 'audience', title: 'Audience', orderable: true },
            { data: 'date_range', title: 'Dates', orderable: false },
            { data: 'impressions', title: 'Impr.', orderable: true },
            { data: 'clicks', title: 'Clicks', orderable: true },
            { data: 'ctr', title: 'CTR', orderable: false },
            { data: 'priority', title: 'Priority', orderable: true },
            { data: 'actions', title: '', orderable: false, searchable: false },
        ],
        order: [[11, 'desc']], // priority desc
        pageLength: 25,
        language: { processing: '<div class="text-sm text-gray-500 py-4">Loading…</div>' },
    });
}

// ─── Status tabs ──────────────────────────────────────────────────────────────

function initStatusTabs() {
    $(document).on('click', '.status-tab', function () {
        $('.status-tab').removeClass('border-primary-500 text-primary-600')
            .addClass('border-transparent text-gray-500');
        $(this).removeClass('border-transparent text-gray-500')
            .addClass('border-primary-500 text-primary-600');
        activeStatusFilter = $(this).data('status');
        bannersTable.ajax.reload();
    });
}

// ─── Filter form ──────────────────────────────────────────────────────────────

function initFilters() {
    $('#search-input').on('input', debounce(() => bannersTable.ajax.reload(), 400));
    $('#filter-placement, #filter-country, #filter-device, #filter-date-from, #filter-date-to')
        .on('change', () => bannersTable.ajax.reload());

    $('#clear-filters').on('click', () => {
        $('#filter-placement, #filter-country, #filter-device, #filter-date-from, #filter-date-to').val('');
        $('#search-input').val('');
        bannersTable.ajax.reload();
    });
}

// ─── Delete modal ─────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

function initDeleteModal() {
    $(document).on('click', '.js-delete-btn', function () {
        pendingDeleteUrl = $(this).data('url');
        $('#delete-banner-name').text($(this).data('name'));
        $('#delete-modal').modal('open');
    });

    $('#confirm-delete-btn').on('click', function () {
        if (!pendingDeleteUrl) return;
        const btn = $(this);
        btn.prop('disabled', true).text('Deleting…');

        $.ajax({
            url: pendingDeleteUrl,
            method: 'DELETE',
            data: { _token: window._token },
        })
            .done((res) => {
                window.Toast.success(res.message || 'Banner deleted.');
                $('#delete-modal').modal('close');
                bannersTable.ajax.reload(null, false);
            })
            .fail(() => window.Toast.error('Failed to delete banner.'))
            .always(() => btn.prop('disabled', false).text('Delete'));
    });
}

// ─── Duplicate modal ──────────────────────────────────────────────────────────

let pendingDuplicateUrl = null;

function initDuplicateModal() {
    $(document).on('click', '.js-duplicate-btn', function () {
        pendingDuplicateUrl = $(this).data('url');
        $('#duplicate-banner-name').text($(this).data('name'));
        $('#duplicate-modal').modal('open');
    });

    $('#confirm-duplicate-btn').on('click', function () {
        if (!pendingDuplicateUrl) return;
        const btn = $(this);
        btn.prop('disabled', true).text('Duplicating…');

        $.ajax({ url: pendingDuplicateUrl, method: 'POST', data: { _token: window._token } })
            .done((res) => {
                window.Toast.success(res.message || 'Banner duplicated.');
                if (res.redirect) window.location.href = res.redirect;
                else {
                    $('#duplicate-modal').modal('close');
                    bannersTable.ajax.reload(null, false);
                }
            })
            .fail(() => window.Toast.error('Failed to duplicate banner.'))
            .always(() => btn.prop('disabled', false).text('Duplicate'));
    });
}

// ─── Image preview ────────────────────────────────────────────────────────────

function initImagePreviews() {
    // Desktop preview
    $('#desktop-image-input').on('change', function () {
        previewFile(this, '#desktop-preview-img', '#desktop-upload-placeholder');
    });

    // Mobile preview
    $('#mobile-image-input').on('change', function () {
        previewFile(this, '#mobile-preview-img', '#mobile-upload-placeholder');
    });
}

function previewFile(input, imgSelector, placeholderSelector) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        $(imgSelector).attr('src', e.target.result).removeClass('hidden');
        $(placeholderSelector).addClass('hidden');
    };
    reader.readAsDataURL(file);
}

// ─── Remove existing image (AJAX) ─────────────────────────────────────────────

function initImageRemoval() {
    $(document).on('click', '.js-remove-img-btn', function () {
        const btn = $(this);
        const fileId = btn.data('file-id');
        const deleteUrl = btn.data('delete-url');
        const slot = btn.data('slot');

        if (!confirm('Remove this image?')) return;

        $.ajax({
            url: deleteUrl,
            method: 'DELETE',
            data: { _token: window._token, file_id: fileId },
        })
            .done(() => {
                window.Toast.success('Image removed.');
                if (slot === 'desktop') {
                    $('#desktop-preview-img').attr('src', '').addClass('hidden');
                    $('#desktop-upload-placeholder').removeClass('hidden');
                } else {
                    $('#mobile-preview-img').attr('src', '').addClass('hidden');
                    $('#mobile-upload-placeholder').removeClass('hidden');
                }
                btn.remove();
            })
            .fail(() => window.Toast.error('Failed to remove image.'));
    });
}

// ─── Date validation + duration preview ───────────────────────────────────────

function initDates() {
    const $startsAt = $('[name="starts_at"]');
    const $endsAt = $('[name="ends_at"]');

    function validate() {
        const s = $startsAt.val();
        const e = $endsAt.val();
        if (s && e) {
            const valid = new Date(e) > new Date(s);
            $('#date-error').toggleClass('hidden', valid);
            $endsAt[0]?.setCustomValidity(valid ? '' : 'End must be after start');

            if (valid) {
                const diffMs = new Date(e) - new Date(s);
                const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
                $('#duration-preview').text(`Duration: ${diffDays} day${diffDays !== 1 ? 's' : ''}`);
            } else {
                $('#duration-preview').text('—');
            }
        }
    }

    $startsAt.add($endsAt).on('change input', validate);
    validate();
}

// ─── Form AJAX submit ─────────────────────────────────────────────────────────

function initFormSubmit() {
    $('#banner-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const btn = $('#save-btn');
        const origTxt = btn.text();

        // Check date validity
        const $endsAt = $('[name="ends_at"]');
        if ($endsAt[0] && $endsAt[0].validationMessage) {
            window.Toast.error('Please fix date errors before saving.');
            return;
        }

        btn.prop('disabled', true).text('Saving…');

        const fd = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        })
            .done((res) => {
                window.Toast.success(res.message || 'Saved.');
                if (res.redirect) window.location.href = res.redirect;
            })
            .fail((xhr) => {
                const body = xhr.responseJSON;
                if (body?.errors) {
                    const firstErr = Object.values(body.errors)[0];
                    window.Toast.error(Array.isArray(firstErr) ? firstErr[0] : firstErr);
                } else {
                    window.Toast.error(body?.message || 'Something went wrong.');
                }
            })
            .always(() => btn.prop('disabled', false).text(origTxt));
    });
}

// ─── Utilities ────────────────────────────────────────────────────────────────

function debounce(fn, delay) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

// ─── Bootstrap ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('banners-table')) {
        initDataTable();
        initStatusTabs();
        initFilters();
        initDeleteModal();
        initDuplicateModal();
    }

    if (document.getElementById('banner-form')) {
        initImagePreviews();
        initImageRemoval();
        initDates();
        initFormSubmit();
    }
});
