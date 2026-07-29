function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function postAction(url) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
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
    const container = document.getElementById('promotion-actions');
    if (!container) return;

    const requestId = container.dataset.requestId;

    document.getElementById('btn-cancel-request')?.addEventListener('click', () => {
        if (!confirm('Cancel this influencer promotion request?')) return;
        postAction(`/influencer-promotions/${requestId}/cancel`);
    });

    document.getElementById('btn-confirm-warehouse')?.addEventListener('click', () => {
        if (!confirm('Confirm that the warehouse has received the stock for this promotion?')) return;
        postAction(`/influencer-promotions/${requestId}/confirm-warehouse-receipt`);
    });
});
