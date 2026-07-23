@extends('layouts.admin')

@section('title', 'Marketer Products')

@section('content')

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total', $stats['total'], 'gray'],
        ['Pending Review', $stats['pending_review'], 'warning'],
        ['Active', $stats['active'], 'success'],
        ['Rejected', $stats['rejected'], 'danger'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
            <p class="text-2xl font-bold mt-1 text-gray-800">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex flex-wrap gap-4 items-end">
    <div>
        <label class="form-label text-xs">Status</label>
        <select id="filter-status" class="form-input text-sm py-1.5">
            <option value="">All</option>
            <option value="pending_review">Pending Review</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="paused">Paused</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
</div>

<x-card>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th class="text-start p-2">Product</th>
                    <th class="text-start p-2">Marketer</th>
                    <th class="text-start p-2">Price</th>
                    <th class="text-start p-2">Commission</th>
                    <th class="text-start p-2">Status</th>
                    <th class="text-start p-2">Created</th>
                    <th class="text-start p-2">Actions</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                <tr><td colspan="7" class="text-center text-gray-400 p-6">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</x-card>

<div id="reject-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-semibold text-gray-800 text-lg mb-4">Reject Product</h3>
        <input type="hidden" id="reject-product-id">
        <label class="form-label">Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason" rows="3" class="form-input w-full mt-1"></textarea>
        <div class="flex justify-end gap-3 mt-5">
            <button id="reject-cancel" type="button" class="btn btn-secondary">Cancel</button>
            <button id="reject-confirm" type="button" class="btn btn-danger">Reject</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const tbody = document.getElementById('products-tbody');
    const statusFilter = document.getElementById('filter-status');

    function badge(status) {
        const colors = { pending_review: 'warning', active: 'success', draft: 'gray', paused: 'gray', rejected: 'danger' };
        return `<span class="badge badge-${colors[status] || 'gray'}">${status}</span>`;
    }

    function load() {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 p-6">Loading…</td></tr>';
        const params = new URLSearchParams({ draw: 1, 'start': 0, 'length': 100, status: statusFilter.value || '' });
        fetch(`{{ route('admin.marketer-products.datatable') }}?${params}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params,
        })
            .then(r => r.json())
            .then(json => {
                const rows = json.data || [];
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 p-6">No products found.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="p-2">${row.name}</td>
                        <td class="p-2">${row.marketer_name}</td>
                        <td class="p-2">${row.price} ${row.currency}</td>
                        <td class="p-2">${(row.platform_commission_rate / 100).toFixed(2)}%</td>
                        <td class="p-2">${badge(row.status)}</td>
                        <td class="p-2">${row.created_at}</td>
                        <td class="p-2 flex gap-2">
                            <a href="/admin/marketer-products/${row.id}" class="btn btn-secondary btn-xs">View</a>
                            ${row.status === 'pending_review' ? `
                                <button class="btn btn-success btn-xs" onclick="approveProduct('${row.id}')">Approve</button>
                                <button class="btn btn-danger btn-xs" onclick="openReject('${row.id}')">Reject</button>
                            ` : ''}
                        </td>
                    </tr>
                `).join('');
            });
    }

    window.approveProduct = function (id) {
        fetch(`/admin/marketer-products/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        }).then(() => load());
    };

    window.openReject = function (id) {
        document.getElementById('reject-product-id').value = id;
        document.getElementById('reject-reason').value = '';
        document.getElementById('reject-modal').classList.remove('hidden');
        document.getElementById('reject-modal').classList.add('flex');
    };

    document.getElementById('reject-cancel').addEventListener('click', () => {
        document.getElementById('reject-modal').classList.add('hidden');
    });

    document.getElementById('reject-confirm').addEventListener('click', () => {
        const id = document.getElementById('reject-product-id').value;
        const reason = document.getElementById('reject-reason').value;
        fetch(`/admin/marketer-products/${id}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ reason }),
        }).then(() => {
            document.getElementById('reject-modal').classList.add('hidden');
            load();
        });
    });

    statusFilter.addEventListener('change', load);
    load();
})();
</script>
@endpush
