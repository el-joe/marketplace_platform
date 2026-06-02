@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Marketer Payouts')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Marketer Payouts</h1>
        <p class="text-sm text-gray-500 mt-0.5">Generate and process marketer commission payouts.</p>
    </div>
    <button type="button" id="btn-generate-payout" class="btn btn-primary btn-sm">
        Generate Payout
    </button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Payouts',   $stats['total'],                                           'gray'],
        ['Pending',         $stats['pending_count'],                                   'warning'],
        ['Approved (SAR)',  number_format($stats['approved_amount'] / 100, 2),         'primary'],
        ['Paid Out (SAR)',  number_format($stats['paid_amount'] / 100, 2),             'success'],
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
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Marketer</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Name, email…">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </div>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <table id="payouts-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>Payout #</th>
                <th>Marketer</th>
                <th>Period</th>
                <th>Conv.</th>
                <th>Gross (SAR)</th>
                <th>Net (SAR)</th>
                <th>Status</th>
                <th>Processed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- ─── Generate Payout Modal ───────────────────────────────────────────────── --}}
<div id="generate-payout-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4">Generate Payout</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marketer ID</label>
                <input type="text" id="payout-marketer-id" class="form-input w-full text-sm"
                    placeholder="Marketer UUID…">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period Start</label>
                    <input type="date" id="payout-period-start" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period End</label>
                    <input type="date" id="payout-period-end" class="form-input w-full text-sm">
                </div>
            </div>
        </div>
        <div class="flex gap-3 justify-end mt-5">
            <button type="button" id="cancel-generate-payout" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-generate-payout" class="btn btn-primary btn-sm">Generate</button>
        </div>
    </div>
</div>

{{-- ─── Process Payout Modal ────────────────────────────────────────────────── --}}
<div id="process-payout-modal" class="modal" style="display:none;">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4">Process Payout</h3>
        <input type="hidden" id="process-payout-id">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Reference</label>
            <input type="text" id="process-payment-ref" class="form-input w-full text-sm"
                placeholder="Bank transfer ID, wire ref…">
        </div>
        <div class="flex gap-3 justify-end mt-5">
            <button type="button" id="cancel-process-payout" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-process-payout" class="btn btn-success btn-sm">Mark as Paid</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const tok = '{{ csrf_token() }}';

    const table = $('#payouts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.payouts.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_status = $('#filter-status').val();
                d.search        = { value: $('#search-input').val() };
            }
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {}, {}, { orderable: false }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
    });

    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input, #filter-status').val('');
        table.ajax.reload();
    });

    // ── Generate Payout Modal ─────────────────────────────────────────────────
    $('#btn-generate-payout').on('click', () => {
        $('#payout-marketer-id, #payout-period-start, #payout-period-end').val('');
        $('#generate-payout-modal').show();
    });
    $('#cancel-generate-payout').on('click', () => $('#generate-payout-modal').hide());
    $('#confirm-generate-payout').on('click', function () {
        const marketerId   = $('#payout-marketer-id').val().trim();
        const periodStart  = $('#payout-period-start').val();
        const periodEnd    = $('#payout-period-end').val();
        if (!marketerId || !periodStart || !periodEnd) { window.Toast.warning('Please fill all fields.'); return; }

        fetch('{{ route('admin.marketers.payouts.generate') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ marketer_id: marketerId, period_start: periodStart, period_end: periodEnd }),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); $('#generate-payout-modal').hide(); table.ajax.reload(); }
            else { window.Toast.error(data.message); }
        });
    });

    // ── Approve Payout ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-approve-payout', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Approve payout?', confirmText: 'Approve', onConfirm: () => {
            fetch('/admin/marketers/payouts/' + id + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                body: '{}'
            }).then(r => r.json()).then(data => {
                if (data.success) { window.Toast.success(data.message); table.ajax.reload(); }
                else { window.Toast.error(data.message); }
            });
        }});
    });

    // ── Process Payout ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-process-payout', function () {
        $('#process-payout-id').val($(this).data('id'));
        $('#process-payment-ref').val('');
        $('#process-payout-modal').show();
    });
    $('#cancel-process-payout').on('click', () => $('#process-payout-modal').hide());
    $('#confirm-process-payout').on('click', function () {
        const id  = $('#process-payout-id').val();
        const ref = $('#process-payment-ref').val().trim();
        if (!ref) { window.Toast.warning('Payment reference is required.'); return; }

        fetch('/admin/marketers/payouts/' + id + '/process', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_reference: ref }),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); $('#process-payout-modal').hide(); table.ajax.reload(); }
            else { window.Toast.error(data.message); }
        });
    });

    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }
});
</script>
@endpush
