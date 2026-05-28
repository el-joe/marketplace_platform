/**
 * coupons.js — admin coupon form & datatable JS
 */
document.addEventListener('DOMContentLoaded', () => {

    // ─── AJAX form submit ─────────────────────────────────────────────────────
    const form = document.getElementById('coupon-form');
    const modeInput = document.getElementById('form-mode');

    if (form) {
        const isEdit = modeInput && modeInput.value === 'edit';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('save-btn');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            try {
                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: fd,
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    window.Toast?.success(isEdit ? 'Coupon saved.' : 'Coupon created.');
                    if (!isEdit && data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    const errors = data.errors ? Object.values(data.errors).flat().join('\n') : null;
                    window.Toast?.error(errors || data.message || 'Save failed.');
                }
            } catch (err) {
                window.Toast?.error('Network error. Please try again.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        });
    }

});
