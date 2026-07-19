function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function submitReview(action) {
    const form = document.getElementById('change-request-review-form');
    if (!form) return;

    const requestId = form.dataset.requestId;
    const adminNote = form.querySelector('[name=admin_note]').value;

    if (action === 'reject' && !adminNote.trim()) {
        window.Toast?.error('A note is required to reject a request.');
        return;
    }

    if (!confirm(action === 'approve'
        ? 'Approve this change request? The changes will be applied immediately.'
        : 'Reject this change request?')) {
        return;
    }

    fetch(`/vendor-change-requests/${requestId}/${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ admin_note: adminNote }),
    })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) throw new Error(data.message || 'Failed.');
            window.Toast?.success(data.message);
            setTimeout(() => location.reload(), 700);
        })
        .catch(err => window.Toast?.error(err.message || 'Failed.'));
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btn-approve-request')?.addEventListener('click', () => submitReview('approve'));
    document.getElementById('btn-reject-request')?.addEventListener('click', () => submitReview('reject'));
});
