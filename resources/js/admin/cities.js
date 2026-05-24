/**
 * resources/js/admin/cities.js
 *
 * Handles:
 *  - Delete city confirmation
 *  - Bulk CSV import modal + progress feedback
 *  - Shipping zone warning highlight
 */

$(function () {
    // ─────────────────────────────────────────────────────────────────────────
    // Delete city
    // ─────────────────────────────────────────────────────────────────────────

    $(document).on('click', '#btn-delete-city', function () {
        const name = $(this).data('city-name');
        const url = $(this).data('url');
        if (!confirm(`Delete "${name}"?\n\nThis cannot be undone.`)) return;

        $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
            .done(function (res) {
                window.Toast && window.Toast.success(res.message);
                setTimeout(() => window.location.href = '/cities/', 900);
            })
            .fail(function (xhr) {
                window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
            });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Bulk Import Modal
    // ─────────────────────────────────────────────────────────────────────────

    function openModal(id) { window.openModal ? window.openModal(id) : $(`#${id}`).removeClass('hidden').show(); }
    function closeModal(id) { window.closeModal ? window.closeModal(id) : $(`#${id}`).addClass('hidden').hide(); }

    $(document).on('click', '#btn-bulk-import', function () {
        // Reset modal state
        $('#import-progress').addClass('hidden');
        $('#import-result').addClass('hidden').text('');
        $('#bulk-import-form')[0].reset();
        openModal('bulk-import-modal');
    });

    $('#bulk-import-form').on('submit', function (e) {
        e.preventDefault();

        const fileInput = $('input[name="file"]')[0];
        if (!fileInput || !fileInput.files[0]) {
            window.Toast && window.Toast.error('Please select a CSV file.');
            return;
        }

        const formData = new FormData(this);

        $('#btn-start-import').prop('disabled', true).text('Importing…');
        $('#import-progress').removeClass('hidden');
        $('#import-result').addClass('hidden');

        $.ajax({
            url: window.citiesBulkImportUrl || '/cities/bulk-import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (res) {
                let html = `<div class="text-success-700 font-medium">${res.message}</div>`;
                if (res.inserted > 0) {
                    html += `<p class="mt-1">✓ ${res.inserted} cities inserted.</p>`;
                }
                if (res.errors && res.errors.length > 0) {
                    html += `<div class="mt-2 text-warning-700 font-medium">Row errors (${res.errors.length}):</div>`;
                    html += `<ul class="mt-1 space-y-0.5 text-xs text-warning-600">`;
                    res.errors.slice(0, 20).forEach(err => {
                        html += `<li>• ${err}</li>`;
                    });
                    if (res.errors.length > 20) {
                        html += `<li class="text-gray-400">…and ${res.errors.length - 20} more</li>`;
                    }
                    html += '</ul>';
                }
                $('#import-result').html(html).removeClass('hidden');
                window.reloadDataTable && window.reloadDataTable('cities-table');
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message || 'Import failed.';
                $('#import-result')
                    .html(`<div class="text-danger-700">${msg}</div>`)
                    .removeClass('hidden');
            })
            .always(function () {
                $('#import-progress').addClass('hidden');
                $('#btn-start-import').prop('disabled', false).text('Import');
            });
    });
});
