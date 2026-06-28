@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Sample Requests')

@section('content')


{{-- Stats bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total',      $stats['total'],      'gray'],
        ['Requested',  $stats['requested'],  'warning'],
        ['Approved',   $stats['approved'],   'success'],
        ['Dispatched', $stats['dispatched'], 'primary'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
            <p class="text-2xl font-bold mt-1 text-gray-800">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex flex-wrap gap-4 items-end">
    <div>
        <label class="form-label text-xs">Status</label>
        <select id="filter-status" class="form-input text-sm py-1.5">
            <option value="">All</option>
            <option value="requested">Requested</option>
            <option value="approved">Approved</option>
            <option value="dispatched">Dispatched</option>
            <option value="received">Received</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
</div>

{{-- DataTable --}}
<x-card>
    <table id="samples-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>Marketer</th>
                <th>Vendor</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- Details modal --}}
<div id="details-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800 text-lg">Sample Request Details</h3>
            <button id="details-close" type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="details-body" class="text-sm text-gray-700 space-y-3">
            <div class="flex items-center justify-center py-8 text-gray-400">Loading…</div>
        </div>
    </div>
</div>

{{-- Reject modal --}}
<div id="reject-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-semibold text-gray-800 text-lg mb-4">Reject Sample Request</h3>
        <input type="hidden" id="reject-sample-id">
        <label class="form-label">Rejection Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason" rows="3" class="form-input w-full mt-1" placeholder="Explain why this request is being rejected…"></textarea>
        <div class="flex justify-end gap-3 mt-5">
            <button id="reject-cancel" type="button" class="btn btn-secondary">Cancel</button>
            <button id="reject-confirm" type="button" class="btn btn-danger">Reject</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
$(function () {
    const tok = '{{ csrf_token() }}';

    const dt = $('#samples-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.samples.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: d => {
                d.filter_status = $('#filter-status').val();
            },
        },
        columns: [{}, {}, {}, {}, { orderable: false }],
        order: [[3, 'desc']],
        pageLength: 20,
    });

    $('#filter-status').on('change', () => dt.ajax.reload());

    $(document).on('click', '.btn-approve-sample', async function () {
        const id = $(this).data('id');
        if (!await window.confirmDialog({ title: 'Approve sample request?' })) return;
        $.post('/marketer-samples/' + id + '/approve', { _token: tok })
            .done(r => { window.Toast.success(r.message); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });

    $(document).on('click', '.btn-dispatch-sample', async function () {
        const id = $(this).data('id');
        if (!await window.confirmDialog({ title: 'Mark as dispatched?' })) return;
        $.post('/marketer-samples/' + id + '/dispatch', { _token: tok })
            .done(r => { window.Toast.success(r.message); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });

    $(document).on('click', '.btn-reject-sample', function () {
        const id = $(this).data('id');
        $('#reject-sample-id').val(id);
        $('#reject-reason').val('');
        $('#reject-modal').css('display', 'flex');
    });

    // Details modal
    $(document).on('click', '.btn-view-sample', function () {
        const id = $(this).data('id');
        $('#details-body').html('<div class="flex items-center justify-center py-8 text-gray-400">Loading…</div>');
        $('#details-modal').css('display', 'flex');
        $.get('/marketer-samples/' + id)
            .done(r => {
                let itemRows = '';
                if (r.items && r.items.length) {
                    itemRows = r.items.map(i => `
                        <tr class="border-t border-gray-100">
                            <td class="py-2 pr-3">${i.product_name}${i.variant_name ? ' <span class="text-gray-400">(' + i.variant_name + ')</span>' : ''}</td>
                            <td class="py-2 pr-3 text-center">${i.quantity}</td>
                            <td class="py-2 pr-3 text-center">${i.is_mandatory ? '<span class="badge badge-warning">Yes</span>' : '<span class="text-gray-400">No</span>'}</td>
                            <td class="py-2 text-right">${i.cost ? i.cost : '—'}</td>
                        </tr>`).join('');
                } else {
                    itemRows = '<tr><td colspan="4" class="py-4 text-center text-gray-400">No items</td></tr>';
                }
                $('#details-body').html(`
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 mb-4">
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">Marketer</span><p class="font-medium mt-0.5">${r.marketer}</p></div>
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">Vendor</span><p class="font-medium mt-0.5">${r.vendor}</p></div>
                        ${r.campaign ? `<div><span class="text-gray-400 text-xs uppercase tracking-wide">Campaign</span><p class="font-medium mt-0.5">${r.campaign}</p></div>` : ''}
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">Status</span><p class="font-medium mt-0.5 capitalize">${r.status}</p></div>
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">Date</span><p class="font-medium mt-0.5">${r.created_at}</p></div>
                    </div>
                    ${r.notes ? `<div class="mb-4"><span class="text-gray-400 text-xs uppercase tracking-wide">Notes</span><p class="mt-0.5">${r.notes}</p></div>` : ''}
                    ${r.rejection_reason ? `<div class="mb-4 p-3 bg-red-50 rounded-lg"><span class="text-red-600 text-xs font-semibold uppercase tracking-wide">Rejection Reason</span><p class="mt-0.5 text-red-700">${r.rejection_reason}</p></div>` : ''}
                    <div><span class="text-gray-400 text-xs uppercase tracking-wide block mb-2">Products Requested</span>
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-gray-500 text-xs">
                                <th class="pb-1 pr-3">Product</th>
                                <th class="pb-1 pr-3 text-center">Qty</th>
                                <th class="pb-1 pr-3 text-center">Mandatory</th>
                                <th class="pb-1 text-right">Cost</th>
                            </tr></thead>
                            <tbody>${itemRows}</tbody>
                        </table>
                    </div>`);
            })
            .fail(() => $('#details-body').html('<p class="text-red-500 text-center py-4">Failed to load details.</p>'));
    });

    $('#details-close').on('click', () => $('#details-modal').css('display', 'none'));

    $('#reject-cancel').on('click', () => $('#reject-modal').css('display', 'none'));

    $('#reject-confirm').on('click', function () {
        const id = $('#reject-sample-id').val();
        const reason = $('#reject-reason').val().trim();
        if (!reason) { window.Toast.error('Rejection reason is required.'); return; }
        $.post('/marketer-samples/' + id + '/reject', { _token: tok, rejection_reason: reason })
            .done(r => { window.Toast.success(r.message); $('#reject-modal').css('display', 'none'); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });
});
</script>
@endpush
