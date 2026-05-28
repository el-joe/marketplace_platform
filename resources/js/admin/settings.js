import DataTable from 'datatables.net';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url, body = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

// ─── Settings Forms ───────────────────────────────────────────────────────────

function initSettingsForms() {
    document.querySelectorAll('.js-settings-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();

            const saveBtn = form.querySelector('.js-save-btn');
            const url = form.dataset.saveUrl;
            if (!url) return;

            const data = {};
            const formData = new FormData(form);

            formData.forEach((value, key) => {
                // Convert settings[key] → { key: value }
                const match = key.match(/^settings\[(.+)\]$/);
                if (match) data[match[1]] = value;
            });

            // For checkboxes / toggles that are not checked (unchecked = omitted by FormData)
            // Read all visible inputs and include unchecked booleans as '0'
            form.querySelectorAll('input[type="checkbox"], input[type="hidden"][name^="settings"]').forEach(el => {
                const match = el.name.match(/^settings\[(.+)\]$/);
                if (match && !(match[1] in data)) {
                    data[match[1]] = '0';
                }
            });

            const originalText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            try {
                const result = await postJson(url, { settings: data });
                window.Toast?.success(result.message ?? 'Settings saved.');
            } catch (err) {
                const msg = err?.message ?? err?.error ?? 'Failed to save settings.';
                window.Toast?.error(msg);
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
        });
    });
}

// ─── Activity Log DataTable ───────────────────────────────────────────────────

function initActivityLogTable() {
    const tableEl = document.getElementById('activity-log-table');
    if (!tableEl) return;

    const dt = new DataTable('#activity-log-table', {
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: tableEl.dataset.url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data(d) {
                d.log_name = document.getElementById('filter-log-name')?.value ?? '';
                d.event = document.getElementById('filter-event')?.value ?? '';
                d.causer_type = document.getElementById('filter-causer-type')?.value ?? '';
                d.date_from = document.getElementById('filter-date-from')?.value ?? '';
                d.date_to = document.getElementById('filter-date-to')?.value ?? '';
            },
        },
        columns: [
            { data: 'created_at', title: 'Time' },
            { data: 'causer', title: 'By', orderable: false },
            { data: 'event', title: 'Event' },
            { data: 'subject', title: 'Subject', orderable: false },
            { data: 'description', title: 'Description', orderable: false },
            { data: 'log_name', title: 'Log' },
            { data: 'ip_address', title: 'IP', orderable: false },
            { data: 'actions', title: '', orderable: false },
        ],
        columnDefs: [{ targets: [1, 2, 3, 5, 7], searchable: false }],
        pageLength: 25,
    });

    // Bind filter controls
    ['filter-log-name', 'filter-event', 'filter-causer-type'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dt.draw());
    });
    ['filter-date-from', 'filter-date-to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dt.draw());
    });

    document.getElementById('btn-reset-filters')?.addEventListener('click', () => {
        ['filter-log-name', 'filter-event', 'filter-causer-type', 'filter-date-from', 'filter-date-to']
            .forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        dt.draw();
    });
}

// ─── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initSettingsForms();
    initActivityLogTable();
});
