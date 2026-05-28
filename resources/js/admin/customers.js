/**
 * customers.js — Customer Management JS
 *
 * Handles:
 *  - DataTable initialisation (index page)
 *  - Suspend / Ban / Reactivate actions (index + show pages)
 *  - Adjust loyalty points modal with live preview
 *  - Send notification modal
 *  - Ban confirmation ("type CONFIRM") gate
 *  - Copyable fields (.js-copy)
 */
document.addEventListener('DOMContentLoaded', () => {

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ─────────────────────────────────────────────────────────────────────────
     * HELPERS
     * ─────────────────────────────────────────────────────────────────────── */

    async function postJson(url, body = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw data;
        return data;
    }

    function setLoading(btn, loading, label = null) {
        if (!btn) return;
        btn.disabled = loading;
        if (label) btn.textContent = loading ? 'Please wait…' : label;
    }

    /* ─────────────────────────────────────────────────────────────────────────
     * INDEX PAGE — DataTable
     * ─────────────────────────────────────────────────────────────────────── */

    const customersTable = document.getElementById('customers-table');
    if (customersTable && window.customersTableUrl) {
        const dt = $(customersTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: window.customersTableUrl,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data(d) {
                    d.status        = document.getElementById('filter-status')?.value || '';
                    d.country_id    = document.getElementById('filter-country')?.value || '';
                    d.date_from     = document.getElementById('filter-date-from')?.value || '';
                    d.date_to       = document.getElementById('filter-date-to')?.value || '';
                    d.min_orders    = document.getElementById('filter-min-orders')?.value || '';
                    d.verified_only = document.getElementById('filter-verified')?.checked ? '1' : '';
                },
            },
            columns: [
                { data: null, orderable: false, searchable: false,
                    render(data, type, row) {
                        return `<input type="checkbox" class="row-select rounded border-gray-300" value="${row.DT_RowData?.id || ''}">`;
                    }
                },
                { data: 'name' },
                { data: 'email' },
                { data: 'phone' },
                { data: 'country' },
                { data: 'status' },
                { data: 'total_orders' },
                { data: 'total_spent' },
                { data: 'loyalty_points' },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-right' },
            ],
            order: [[9, 'desc']],
            pageLength: 25,
            language: { search: '', searchPlaceholder: 'Search…' },
        });

        // Global search input
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let searchTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    dt.search(searchInput.value).draw();
                }, 400);
            });
        }

        // Filter controls
        ['filter-status', 'filter-country'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => dt.draw());
        });
        ['filter-date-from', 'filter-date-to', 'filter-min-orders'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => dt.draw());
        });
        document.getElementById('filter-verified')?.addEventListener('change', () => dt.draw());

        // Reset filters
        document.getElementById('clear-filters')?.addEventListener('click', () => {
            ['filter-status', 'filter-country'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            ['filter-date-from', 'filter-date-to', 'filter-min-orders'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const verifiedEl = document.getElementById('filter-verified');
            if (verifiedEl) verifiedEl.checked = false;
            if (searchInput) { searchInput.value = ''; dt.search('').draw(); }
            else dt.draw();
        });

        // ── Bulk selection ──────────────────────────────────────────────────
        const bulkBar = document.getElementById('bulk-bar');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-select:checked');
            const count = checked.length;
            if (bulkBar) bulkBar.classList.toggle('hidden', count === 0);
            if (selectedCount) selectedCount.textContent = count;
        }

        document.getElementById('select-all')?.addEventListener('change', (e) => {
            document.querySelectorAll('.row-select').forEach(cb => { cb.checked = e.target.checked; });
            updateBulkBar();
        });

        $(customersTable).on('change', '.row-select', updateBulkBar);

        document.getElementById('clear-selection')?.addEventListener('click', () => {
            document.querySelectorAll('.row-select, #select-all').forEach(cb => { cb.checked = false; });
            updateBulkBar();
        });

        // ── Bulk suspend ───────────────────────────────────────────────────
        document.querySelector('[data-bulk="suspend"]')?.addEventListener('click', () => {
            const ids = [...document.querySelectorAll('.row-select:checked')].map(cb => cb.value);
            if (!ids.length) return;

            const reason = prompt(`Suspend ${ids.length} customer(s) — enter reason:`);
            if (!reason) return;

            Promise.all(ids.map(id => {
                const url = window.customersTableUrl.replace('/datatable', `/${id}/suspend`);
                return postJson(url, { reason }).catch(() => null);
            })).then(() => {
                window.Toast?.success(`${ids.length} customer(s) suspended.`);
                dt.draw(false);
                document.querySelectorAll('.row-select, #select-all').forEach(cb => { cb.checked = false; });
                updateBulkBar();
            }).catch(() => window.Toast?.error('Some actions failed.'));
        });

        // ── Export selected / all ──────────────────────────────────────────
        document.querySelector('[data-bulk="export"]')?.addEventListener('click', () => {
            const ids = [...document.querySelectorAll('.row-select:checked')].map(cb => cb.value);
            ids.forEach(id => {
                const url = window.customersTableUrl.replace('/datatable', `/${id}/export`);
                window.open(url, '_blank');
            });
        });

        document.getElementById('export-all-btn')?.addEventListener('click', () => {
            window.Toast?.success('Use the table filters and "Export Selected" for bulk export.');
        });

        // ── Row actions (suspend/ban/reactivate) delegated ─────────────────
        $(customersTable).on('click', '.js-suspend-btn', function () {
            openSuspendModal(this.dataset.url, this.dataset.name);
        });
        $(customersTable).on('click', '.js-ban-btn', function () {
            openBanModal(this.dataset.url, this.dataset.name);
        });
        $(customersTable).on('click', '.js-reactivate-btn', function () {
            handleReactivate(this.dataset.url, this.dataset.name);
        });
    }

    /* ─────────────────────────────────────────────────────────────────────────
     * SHOW PAGE — static row action buttons in sidebar
     * ─────────────────────────────────────────────────────────────────────── */

    document.querySelectorAll('.js-suspend-btn[data-modal="suspend-modal"]').forEach(btn => {
        btn.addEventListener('click', () => openSuspendModal(btn.dataset.url, btn.dataset.name));
    });

    document.querySelectorAll('.js-ban-btn[data-modal="ban-modal"]').forEach(btn => {
        btn.addEventListener('click', () => openBanModal(btn.dataset.url, btn.dataset.name));
    });

    document.querySelectorAll('.js-reactivate-btn:not([data-modal])').forEach(btn => {
        btn.addEventListener('click', () => handleReactivate(btn.dataset.url, btn.dataset.name));
    });

    /* ─────────────────────────────────────────────────────────────────────────
     * SUSPEND MODAL
     * ─────────────────────────────────────────────────────────────────────── */

    let activeSuspendUrl = null;

    function openSuspendModal(url, name) {
        activeSuspendUrl = url;
        const nameEl = document.getElementById('suspend-customer-name');
        if (nameEl) nameEl.textContent = name || '';
        const reasonEl = document.getElementById('suspend-reason');
        if (reasonEl) reasonEl.value = '';
        $('#suspend-modal').modal('open');
    }

    document.getElementById('suspend-confirm-btn')?.addEventListener('click', async () => {
        const url = activeSuspendUrl || document.getElementById('suspend-confirm-btn')?.dataset.url;
        if (!url) return;

        const reason = document.getElementById('suspend-reason')?.value?.trim();
        if (!reason) {
            window.Toast?.error('Please enter a reason.');
            return;
        }

        const btn = document.getElementById('suspend-confirm-btn');
        setLoading(btn, true, 'Suspend');

        try {
            await postJson(url, { reason });
            $('#suspend-modal').modal('close');
            window.Toast?.success('Customer suspended.');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            const msg = err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Action failed.';
            window.Toast?.error(msg);
        } finally {
            setLoading(btn, false, 'Suspend');
        }
    });

    /* ─────────────────────────────────────────────────────────────────────────
     * BAN MODAL — requires typed "CONFIRM"
     * ─────────────────────────────────────────────────────────────────────── */

    let activeBanUrl = null;

    function openBanModal(url, name) {
        activeBanUrl = url;
        const nameEl = document.getElementById('ban-customer-name');
        if (nameEl) nameEl.textContent = name || '';
        const reasonEl = document.getElementById('ban-reason');
        if (reasonEl) reasonEl.value = '';
        const confirmInput = document.getElementById('ban-confirm-input');
        if (confirmInput) confirmInput.value = '';
        const confirmBtn = document.getElementById('ban-confirm-btn');
        if (confirmBtn) confirmBtn.disabled = true;
        $('#ban-modal').modal('open');
    }

    // Live enable/disable of ban button based on typed confirmation
    document.getElementById('ban-confirm-input')?.addEventListener('input', (e) => {
        const confirmBtn = document.getElementById('ban-confirm-btn');
        if (confirmBtn) {
            confirmBtn.disabled = e.target.value.trim() !== 'CONFIRM';
        }
    });

    document.getElementById('ban-confirm-btn')?.addEventListener('click', async () => {
        const url = activeBanUrl || document.getElementById('ban-confirm-btn')?.dataset.url;
        if (!url) return;

        const reason = document.getElementById('ban-reason')?.value?.trim();
        const confirmText = document.getElementById('ban-confirm-input')?.value?.trim();

        if (!reason) {
            window.Toast?.error('Please enter a reason.');
            return;
        }
        if (confirmText !== 'CONFIRM') {
            window.Toast?.error('Please type CONFIRM to proceed.');
            return;
        }

        const btn = document.getElementById('ban-confirm-btn');
        setLoading(btn, true, 'Ban Customer');

        try {
            await postJson(url, { reason });
            $('#ban-modal').modal('close');
            window.Toast?.success('Customer banned.');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            const msg = err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Action failed.';
            window.Toast?.error(msg);
        } finally {
            setLoading(btn, false, 'Ban Customer');
        }
    });

    /* ─────────────────────────────────────────────────────────────────────────
     * REACTIVATE
     * ─────────────────────────────────────────────────────────────────────── */

    async function handleReactivate(url, name) {
        if (!confirm(`Reactivate ${name || 'this customer'}?`)) return;
        try {
            await postJson(url, {});
            window.Toast?.success('Customer reactivated.');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            window.Toast?.error(err?.message || 'Action failed.');
        }
    }

    /* ─────────────────────────────────────────────────────────────────────────
     * ADJUST LOYALTY POINTS — live preview + modal
     * ─────────────────────────────────────────────────────────────────────── */

    document.getElementById('open-loyalty-modal')?.addEventListener('click', () => {
        const adjustmentInput = document.getElementById('adjustment-input');
        if (adjustmentInput) adjustmentInput.value = '';
        const previewEl = document.getElementById('preview-loyalty');
        const currentEl = document.getElementById('current-loyalty');
        if (previewEl && currentEl) previewEl.textContent = currentEl.textContent;
        $('#adjust-loyalty-modal').modal('open');
    });

    document.getElementById('adjustment-input')?.addEventListener('input', (e) => {
        const currentBalance = parseFloat(e.target.dataset.current || '0');
        const adjustment = parseFloat(e.target.value) || 0;
        const newBalance = currentBalance + adjustment;
        const previewEl = document.getElementById('preview-loyalty');
        if (previewEl) {
            previewEl.textContent = newBalance.toFixed(2);
            previewEl.className = newBalance < 0
                ? 'font-bold text-danger-700'
                : 'font-bold text-primary-900';
        }
    });

    document.getElementById('loyalty-confirm-btn')?.addEventListener('click', async () => {
        const url = document.getElementById('loyalty-confirm-btn')?.dataset.url;
        if (!url) return;

        const adjustment = parseInt(document.getElementById('adjustment-input')?.value, 10);
        const reason = document.getElementById('loyalty-reason')?.value?.trim();

        if (!adjustment || adjustment === 0) {
            window.Toast?.error('Please enter a non-zero adjustment.');
            return;
        }
        if (!reason) {
            window.Toast?.error('Please enter a reason.');
            return;
        }

        const btn = document.getElementById('loyalty-confirm-btn');
        setLoading(btn, true, 'Apply');

        try {
            const data = await postJson(url, { adjustment, reason });
            $('#adjust-loyalty-modal').modal('close');
            window.Toast?.success('Loyalty points adjusted.');

            // Update displayed balance
            const display = document.getElementById('loyalty-balance-display');
            if (display && data.new_balance) display.textContent = data.new_balance;
            const currentInput = document.getElementById('adjustment-input');
            if (currentInput && data.new_balance) currentInput.dataset.current = data.new_balance.replace(/,/g, '');
            const currentEl = document.getElementById('current-loyalty');
            if (currentEl && data.new_balance) currentEl.textContent = data.new_balance;
        } catch (err) {
            const msg = err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Action failed.';
            window.Toast?.error(msg);
        } finally {
            setLoading(btn, false, 'Apply');
        }
    });

    /* ─────────────────────────────────────────────────────────────────────────
     * SEND NOTIFICATION MODAL
     * ─────────────────────────────────────────────────────────────────────── */

    document.getElementById('open-notification-modal')?.addEventListener('click', () => {
        document.getElementById('notif-title').value = '';
        document.getElementById('notif-message').value = '';
        document.getElementById('notif-channel').value = 'database';
        $('#send-notification-modal').modal('open');
    });

    document.getElementById('notif-send-btn')?.addEventListener('click', async () => {
        const url = document.getElementById('notif-send-btn')?.dataset.url;
        if (!url) return;

        const channel = document.getElementById('notif-channel')?.value;
        const title   = document.getElementById('notif-title')?.value?.trim();
        const message = document.getElementById('notif-message')?.value?.trim();

        if (!title || !message) {
            window.Toast?.error('Please fill in all fields.');
            return;
        }

        const btn = document.getElementById('notif-send-btn');
        setLoading(btn, true, 'Send');

        try {
            await postJson(url, { channel, title, message });
            $('#send-notification-modal').modal('close');
            window.Toast?.success('Notification sent.');
        } catch (err) {
            const msg = err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Failed to send.';
            window.Toast?.error(msg);
        } finally {
            setLoading(btn, false, 'Send');
        }
    });

    /* ─────────────────────────────────────────────────────────────────────────
     * COPYABLE FIELDS
     * ─────────────────────────────────────────────────────────────────────── */

    document.querySelectorAll('.js-copy').forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.value;
            if (!value) return;
            navigator.clipboard.writeText(value).then(() => {
                const original = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.textContent = original; }, 1500);
            }).catch(() => window.Toast?.error('Copy failed.'));
        });
    });

});
