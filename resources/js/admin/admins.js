/**
 * admins.js — Admin management form & datatable JS
 *
 * Handles:
 *  - Admin form AJAX submit (create/edit) with generated-password reveal
 *  - Role form AJAX submit
 *  - Reset password button (edit mode)
 */
document.addEventListener('DOMContentLoaded', () => {

    // ─── Admin Form ───────────────────────────────────────────────────────────
    const adminForm = document.getElementById('admin-form');
    if (adminForm) {
        const isEdit = adminForm.querySelector('input[name="_method"]') !== null;

        adminForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('save-btn');
            const origText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            try {
                const fd = new FormData(adminForm);
                const res = await fetch(adminForm.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: fd,
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    if (!isEdit && data.generated_password) {
                        // Show password in Alpine component before redirecting
                        const alpine = adminForm.querySelector('[x-data]')?._x_dataStack?.[0];
                        if (alpine) {
                            alpine.generatedPassword = data.generated_password;
                            alpine.showGeneratedPassword = true;
                        }
                        window.Toast?.success('Administrator created.');
                        // Small delay to let user read the password before redirect
                        setTimeout(() => {
                            if (data.redirect) window.location.href = data.redirect;
                        }, 3000);
                    } else {
                        window.Toast?.success('Administrator saved.');
                    }
                } else {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join('\n')
                        : null;
                    window.Toast?.error(errors || data.message || 'Save failed.');
                }
            } catch (err) {
                window.Toast?.error('Network error. Please try again.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            }
        });
    }

    // ─── Role Form ────────────────────────────────────────────────────────────
    const roleForm = document.getElementById('role-form');
    if (roleForm) {
        const isEdit = roleForm.querySelector('input[name="_method"]') !== null;

        roleForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('save-btn');
            const origText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            try {
                const fd = new FormData(roleForm);
                const res = await fetch(roleForm.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: fd,
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    window.Toast?.success(isEdit ? 'Role saved.' : 'Role created.');
                    if (!isEdit && data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join('\n')
                        : null;
                    window.Toast?.error(errors || data.message || 'Save failed.');
                }
            } catch (err) {
                window.Toast?.error('Network error. Please try again.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            }
        });
    }

    // ─── Reset Password Button (edit mode) ────────────────────────────────────
    const resetBtn = document.getElementById('reset-password-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', async function () {
            const url = resetBtn.dataset.url;
            if (!url) return;

            const confirmed = window.confirmBulkAction
                ? await window.confirmBulkAction(
                    'Reset this administrator\'s password? They will receive a new password by email.',
                    { title: 'Reset Password?' }
                )
                : confirm('Reset this administrator\'s password?');

            if (!confirmed) return;

            resetBtn.disabled = true;
            resetBtn.textContent = 'Resetting…';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    window.Toast?.success(data.message || 'Password reset successfully.');
                } else {
                    window.Toast?.error(data.message || 'Reset failed.');
                }
            } catch (err) {
                window.Toast?.error('Network error. Please try again.');
            } finally {
                resetBtn.disabled = false;
                resetBtn.textContent = 'Reset Password';
            }
        });
    }

});
