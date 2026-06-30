import './app.js';

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

const cfg = () => window.PROFILE;

function csrfHeaders(isJson = true) {
    const h = { 'X-CSRF-TOKEN': cfg().csrf };
    if (isJson) h['Accept'] = 'application/json';
    return h;
}

function toast(message, ok = true) {
    window.Toastify?.({
        text: message,
        backgroundColor: ok ? '#22c55e' : '#ef4444',
        duration: 3500,
        gravity: 'top',
        position: 'center',
    }).showToast();
}

function setLoading(btn, loading) {
    btn.disabled = loading;
    btn.dataset.origText = btn.dataset.origText ?? btn.textContent;
    btn.textContent = loading ? 'جارٍ الحفظ...' : btn.dataset.origText;
}

// ─────────────────────────────────────────────────────────────────────────────
// Store info form
// ─────────────────────────────────────────────────────────────────────────────

function initStoreForm() {
    const form = document.getElementById('form-store');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-store');
        setLoading(btn, true);

        const body = new URLSearchParams(new FormData(form));

        try {
            const res = await fetch(cfg().updateStoreUrl, {
                method: 'POST',
                headers: { ...csrfHeaders(), 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? 'فشل الحفظ');
            toast(data.message);
        } catch (err) {
            toast(err.message, false);
        } finally {
            setLoading(btn, false);
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Password form
// ─────────────────────────────────────────────────────────────────────────────

function initPasswordForm() {
    const form = document.getElementById('form-password');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-password');
        setLoading(btn, true);

        const body = new URLSearchParams(new FormData(form));

        try {
            const res = await fetch(cfg().updatePasswordUrl, {
                method: 'POST',
                headers: { ...csrfHeaders(), 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? 'فشل التغيير');
            toast(data.message);
            form.reset();
        } catch (err) {
            toast(err.message, false);
        } finally {
            setLoading(btn, false);
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Document uploads
// ─────────────────────────────────────────────────────────────────────────────

function initDocumentUploads() {
    document.querySelectorAll('.doc-upload-input').forEach(input => {
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) return;

            const typeId   = input.dataset.typeId;
            const statusEl = input.closest('.flex')?.querySelector('.doc-upload-status');

            if (statusEl) statusEl.classList.remove('hidden');
            input.disabled = true;

            const formData = new FormData();
            formData.append('document_type_id', typeId);
            formData.append('file', file);

            try {
                const res = await fetch(cfg().uploadDocumentUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': cfg().csrf, 'Accept': 'application/json' },
                    body: formData,
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message ?? 'فشل الرفع');

                toast(data.message);
                setTimeout(() => location.reload(), 1500);
            } catch (err) {
                toast(err.message, false);
                if (statusEl) statusEl.classList.add('hidden');
                input.disabled = false;
            }
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Bootstrap
// ─────────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initStoreForm();
    initPasswordForm();
    initDocumentUploads();
});
