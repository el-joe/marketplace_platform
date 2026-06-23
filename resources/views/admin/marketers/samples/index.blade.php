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

    $(document).on('click', '.btn-approve-sample', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Approve sample request?', onConfirm: () => {
            $.post('/admin/marketer-samples/' + id + '/approve', { _token: tok })
                .done(r => { window.Toast.success(r.message); dt.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
        }});
    });

    $(document).on('click', '.btn-dispatch-sample', function () {
        const id = $(this).data('id');
        $.post('/admin/marketer-samples/' + id + '/dispatch', { _token: tok })
            .done(r => { window.Toast.success(r.message); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });
});
</script>
@endpush
