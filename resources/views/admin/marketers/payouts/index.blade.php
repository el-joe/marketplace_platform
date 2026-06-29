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
        ['Pending',         $stats['pending'],                                   'warning'],
        ['Approved',  number_format($stats['approved'] / 100, 2),         'primary'],
        ['Paid Out',  number_format($stats['paid'] / 100, 2),             'success'],
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
                <th>Gross</th>
                <th>Net</th>
                <th>Status</th>
                <th>Processed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- ─── Generate Payout Modal ───────────────────────────────────────────────── --}}
<div id="generate-payout-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
    <!-- Modal Container -->
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Generate Payout</h3>
                <p class="text-sm text-gray-500 mt-1">Specify the marketer and period to calculate earnings.</p>
            </div>
            <button type="button" id="cancel-generate-payout" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-200" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5">
            <!-- Marketer Select -->
            <div>
                <label for="payout-marketer-id" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Marketer <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select id="payout-marketer-id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm">
                    <option value="">— Select a marketer —</option>
                    @foreach($marketers as $marketer)
                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range Inputs -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="payout-period-start" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Period Start <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input type="date" id="payout-period-start" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm">
                </div>
                <div>
                    <label for="payout-period-end" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Period End <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input type="date" id="payout-period-end" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm">
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 rounded-b-xl">
            <button type="button" id="cancel-generate-payout-footer" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors">
                Cancel
            </button>
            <button type="button" id="confirm-generate-payout" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Generate Payout
            </button>
        </div>

    </div>
</div>

{{-- ─── Process Payout Modal ────────────────────────────────────────────────── --}}
<div id="process-payout-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Process Payout</h3>
            <button type="button" id="cancel-process-payout"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <input type="hidden" id="process-payout-id">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Reference <span class="text-red-500">*</span></label>
            <input type="text" id="process-payment-ref" class="form-input w-full text-sm"
                placeholder="Bank transfer ID, wire ref…">
        </div>
        <div class="pt-4 border-t flex gap-3 justify-end mt-5">
            <button type="button" id="cancel-process-payout-footer" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-process-payout" class="btn btn-success btn-sm">Mark as Paid</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
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
        $('#payout-marketer-id').val('');
        $('#payout-period-start, #payout-period-end').val('');
        $('#generate-payout-modal').modal('open');
    });
    $('#cancel-generate-payout, #cancel-generate-payout-footer').on('click', () => $('#generate-payout-modal').modal('close'));
    $('#confirm-generate-payout').on('click', function () {
        const marketerId   = $('#payout-marketer-id').val().trim();
        const periodStart  = $('#payout-period-start').val();
        const periodEnd    = $('#payout-period-end').val();
        if (!marketerId || !periodStart || !periodEnd) { window.Toast.warning('Please select a marketer and period.'); return; }

        fetch('{{ route('admin.marketers.payouts.generate') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ marketer_id: marketerId, period_start: periodStart, period_end: periodEnd }),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); $('#generate-payout-modal').modal('close'); table.ajax.reload(); }
            else { window.Toast.error(data.message); }
        });
    });

    // ── Approve Payout ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-approve-payout', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Approve payout?', confirmText: 'Approve', onConfirm: () => {
            fetch('/marketers/payouts/' + id + '/approve', {
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
        $('#process-payout-modal').modal('open');
    });
    $('#cancel-process-payout, #cancel-process-payout-footer').on('click', () => $('#process-payout-modal').modal('close'));
    $('#confirm-process-payout').on('click', function () {
        const id  = $('#process-payout-id').val();
        const ref = $('#process-payment-ref').val().trim();
        if (!ref) { window.Toast.warning('Payment reference is required.'); return; }

        fetch('/marketers/payouts/' + id + '/process', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_reference: ref }),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); $('#process-payout-modal').modal('close'); table.ajax.reload(); }
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
