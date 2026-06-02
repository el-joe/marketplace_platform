@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'FBN Inbound Requests')

@section('content')

<x-breadcrumbs :items="$breadcrumbs" class="mb-4"/>

{{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
    @foreach([
        ['label' => 'Total',     'value' => $stats['total'],     'color' => 'text-gray-700'],
        ['label' => 'Draft',     'value' => $stats['draft'],     'color' => 'text-gray-400'],
        ['label' => 'Submitted', 'value' => $stats['submitted'], 'color' => 'text-yellow-600'],
        ['label' => 'Approved',  'value' => $stats['approved'],  'color' => 'text-blue-600'],
        ['label' => 'Shipped',   'value' => $stats['shipped'],   'color' => 'text-purple-600'],
        ['label' => 'Received',  'value' => $stats['received'],  'color' => 'text-green-600'],
        ['label' => 'Rejected',  'value' => $stats['rejected'],  'color' => 'text-red-500'],
    ] as $s)
    <div class="bg-white rounded-2xl border border-gray-100 p-3 text-center">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $s['label'] }}</p>
        <p class="text-xl font-extrabold {{ $s['color'] }}">{{ number_format($s['value']) }}</p>
    </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="label-sm">Status</label>
        <select id="filter-status" class="form-select text-sm py-1.5 pr-8">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="shipped">Shipped</option>
            <option value="received">Received</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
    <div>
        <label class="label-sm">Warehouse</label>
        <select id="filter-warehouse" class="form-select text-sm py-1.5 pr-8">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
            @endforeach
        </select>
    </div>
</div>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <table id="tbl-inbound" class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-4 py-3 text-left">Request #</th>
                <th class="px-4 py-3 text-left">Vendor</th>
                <th class="px-4 py-3 text-left">Product</th>
                <th class="px-4 py-3 text-left">Warehouse</th>
                <th class="px-4 py-3 text-left">Qty (Req / Rcvd)</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Expected</th>
                <th class="px-4 py-3 text-left">Tracking</th>
                <th class="px-4 py-3 text-left">Created</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

{{-- ─── Approve Modal ───────────────────────────────────────────────────────── --}}
<div id="approve-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg mb-4">Approve Inbound Request</h3>
        <input type="hidden" id="approve-id">
        <div>
            <label class="label-sm">Expected Arrival (optional)</label>
            <input type="date" id="approve-expected" class="form-input w-full text-sm"
                   min="{{ now()->toDateString() }}">
        </div>
        <div class="flex gap-3 justify-end mt-5 pt-4 border-t">
            <button type="button" id="approve-close" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="approve-confirm" class="btn btn-success btn-sm">Approve</button>
        </div>
    </div>
</div>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg mb-4">Reject Inbound Request</h3>
        <input type="hidden" id="reject-id">
        <div>
            <label class="label-sm">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea id="reject-reason" rows="3" class="form-input w-full text-sm"
                placeholder="Provide reason for rejection..."></textarea>
        </div>
        <div class="flex gap-3 justify-end mt-5 pt-4 border-t">
            <button type="button" id="reject-close" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="reject-confirm" class="btn btn-danger btn-sm">Reject</button>
        </div>
    </div>
</div>

{{-- ─── Tracking Modal ──────────────────────────────────────────────────────── --}}
<div id="tracking-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg mb-4">Update Tracking</h3>
        <input type="hidden" id="tracking-id">
        <div class="space-y-3">
            <div>
                <label class="label-sm">Tracking Number <span class="text-red-500">*</span></label>
                <input type="text" id="tracking-number" class="form-input w-full text-sm" placeholder="e.g. FEDEX-123456">
            </div>
            <div>
                <label class="label-sm">Expected Arrival</label>
                <input type="date" id="tracking-expected" class="form-input w-full text-sm">
            </div>
        </div>
        <div class="flex gap-3 justify-end mt-5 pt-4 border-t">
            <button type="button" id="tracking-close" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="tracking-confirm" class="btn btn-primary btn-sm">Save & Mark Shipped</button>
        </div>
    </div>
</div>

{{-- ─── Receive Modal ───────────────────────────────────────────────────────── --}}
<div id="receive-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg mb-4">Mark as Received</h3>
        <input type="hidden" id="receive-id">
        <div>
            <label class="label-sm">Quantity Received <span class="text-red-500">*</span></label>
            <input type="number" id="receive-qty" class="form-input w-full text-sm" min="1">
            <p class="text-xs text-gray-400 mt-1">Max: <span id="receive-max"></span> units requested</p>
        </div>
        <div class="flex gap-3 justify-end mt-5 pt-4 border-t">
            <button type="button" id="receive-close" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="receive-confirm" class="btn btn-success btn-sm">Confirm Receipt</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const tok = '{{ csrf_token() }}';

    const tbl = $('#tbl-inbound').DataTable({
        serverSide: true,
        processing: true,
        pageLength: 25,
        order: [[8, 'desc']],
        ajax: {
            url: '{{ route('admin.fbn.inbound.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: d => {
                d.status       = $('#filter-status').val();
                d.warehouse_id = $('#filter-warehouse').val();
            },
        },
        columns: [
            { data: 'request_number', orderable: false },
            { data: 'vendor',         orderable: false },
            { data: 'product',        orderable: false },
            { data: 'warehouse',      orderable: false },
            { data: 'qty',            orderable: false },
            { data: 'status',         orderable: false },
            { data: 'expected',       orderable: false },
            { data: 'tracking',       orderable: false },
            { data: 'created_at',     orderable: true  },
            { data: 'actions',        orderable: false },
        ],
        language: { processing: 'Loading…' },
    });

    $('#filter-status, #filter-warehouse').on('change', () => tbl.ajax.reload());

    function jsonPost(url, body, onSuccess) {
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); onSuccess?.(); tbl.ajax.reload(); }
            else { window.Toast.error(data.message ?? 'Error'); }
        });
    }

    // ── Approve ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-approve-inbound', function () {
        $('#approve-id').val($(this).data('id'));
        $('#approve-expected').val('');
        $('#approve-modal').show();
    });
    $('#approve-close').on('click', () => $('#approve-modal').hide());
    $('#approve-confirm').on('click', () => {
        const id = $('#approve-id').val();
        jsonPost(`/admin/fbn/inbound/${id}/approve`, { expected_arrival: $('#approve-expected').val() || null },
            () => $('#approve-modal').hide());
    });

    // ── Reject ─────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-reject-inbound', function () {
        $('#reject-id').val($(this).data('id'));
        $('#reject-reason').val('');
        $('#reject-modal').show();
    });
    $('#reject-close').on('click', () => $('#reject-modal').hide());
    $('#reject-confirm').on('click', () => {
        const id = $('#reject-id').val();
        const reason = $('#reject-reason').val().trim();
        if (!reason) { window.Toast.error('Please enter a rejection reason.'); return; }
        jsonPost(`/admin/fbn/inbound/${id}/reject`, { rejection_reason: reason },
            () => $('#reject-modal').hide());
    });

    // ── Tracking ───────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-update-tracking', function () {
        $('#tracking-id').val($(this).data('id'));
        $('#tracking-number').val('');
        $('#tracking-expected').val('');
        $('#tracking-modal').show();
    });
    $('#tracking-close').on('click', () => $('#tracking-modal').hide());
    $('#tracking-confirm').on('click', () => {
        const id = $('#tracking-id').val();
        const tn = $('#tracking-number').val().trim();
        if (!tn) { window.Toast.error('Please enter a tracking number.'); return; }
        jsonPost(`/admin/fbn/inbound/${id}/tracking`, { tracking_number: tn, expected_arrival: $('#tracking-expected').val() || null },
            () => $('#tracking-modal').hide());
    });

    // ── Receive ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-mark-received', function () {
        $('#receive-id').val($(this).data('id'));
        const max = $(this).data('max');
        $('#receive-qty').val(max).attr('max', max);
        $('#receive-max').text(max);
        $('#receive-modal').show();
    });
    $('#receive-close').on('click', () => $('#receive-modal').hide());
    $('#receive-confirm').on('click', () => {
        const id = $('#receive-id').val();
        const qty = parseInt($('#receive-qty').val());
        if (!qty || qty < 1) { window.Toast.error('Please enter quantity received.'); return; }
        jsonPost(`/admin/fbn/inbound/${id}/receive`, { quantity_received: qty },
            () => $('#receive-modal').hide());
    });
});
</script>
@endpush
