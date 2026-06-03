@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Marketer Conversions')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Marketer Conversions</h1>
        <p class="text-sm text-gray-500 mt-0.5">Review and bulk-approve marketer commission conversions.</p>
    </div>
    <button type="button" id="btn-bulk-approve"
            class="btn btn-success btn-sm" style="display:none;">
        Approve Selected
    </button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total',    $stats['total'],    'gray'],
        ['Pending',  $stats['pending'],  'warning'],
        ['Approved', $stats['approved'], 'success'],
        ['Paid',     $stats['paid'],     'primary'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $color }}-600">{{ $value }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Marketer name…">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Date From</label>
            <input type="date" id="filter-date-from" class="form-input w-full text-sm">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Date To</label>
            <input type="date" id="filter-date-to" class="form-input w-full text-sm">
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </div>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <table id="conversions-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all"></th>
                <th>Marketer</th>
                <th>Campaign</th>
                <th>Order Value</th>
                <th>Commission</th>
                <th>Status</th>
                <th>Date</th>
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
    let selectedIds = [];

    const table = $('#conversions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.conversions.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_status    = $('#filter-status').val();
                d.filter_date_from = $('#filter-date-from').val();
                d.filter_date_to   = $('#filter-date-to').val();
                d.search           = { value: $('#search-input').val() };
            }
        },
        columns: [
            { orderable: false, className: 'select-checkbox', width: '30px' },
            {},
            {},
            {},
            {},
            {},
            {},
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        select: { style: 'multi', selector: 'td:first-child' },
    });

    // Track selection
    table.on('select deselect', function () {
        selectedIds = table.rows({ selected: true }).data().toArray().map(r => {
            const el = $(r[0]);
            return el.data ? el.data('id') : null;
        }).filter(Boolean);

        const anyPending = table.rows({ selected: true }).nodes().to$().find('td:first-child').toArray().some(td => {
            return $(td).data('status') === 'pending';
        });

        // Simpler: show button whenever anything selected
        selectedIds.length > 0 ? $('#btn-bulk-approve').show() : $('#btn-bulk-approve').hide();
    });

    // Select all
    $('#select-all').on('change', function () {
        if ($(this).is(':checked')) {
            table.rows().select();
        } else {
            table.rows().deselect();
        }
    });

    // Bulk approve
    $('#btn-bulk-approve').on('click', function () {
        const ids = table.rows({ selected: true }).nodes().to$()
            .find('input[type="checkbox"]').map((i, el) => $(el).val()).toArray().filter(Boolean);

        if (!ids.length) { window.Toast.warning('No rows selected.'); return; }

        window.confirmDialog({ title: 'Approve ' + ids.length + ' conversion(s)?', confirmText: 'Approve All', onConfirm: () => {
            fetch('{{ route('admin.marketers.conversions.approve') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids }),
            }).then(r => r.json()).then(data => {
                if (data.success) { window.Toast.success(data.message); table.ajax.reload(); $('#btn-bulk-approve').hide(); }
                else { window.Toast.error(data.message); }
            });
        }});
    });

    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status, #filter-date-from, #filter-date-to').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input').val('');
        $('#filter-status').val('');
        $('#filter-date-from, #filter-date-to').val('');
        table.ajax.reload();
    });

    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }
});
</script>
@endpush
