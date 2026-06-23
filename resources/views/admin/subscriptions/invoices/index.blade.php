@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Subscription Invoices')

@section('content')


    {{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total</p>
            <p class="text-2xl font-extrabold text-gray-800">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-yellow-500 uppercase tracking-wide mb-1">Open</p>
            <p class="text-2xl font-extrabold text-yellow-600">{{ number_format($stats['open']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-green-500 uppercase tracking-wide mb-1">Paid</p>
            <p class="text-2xl font-extrabold text-green-600">{{ number_format($stats['paid']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-yellow-50 bg-yellow-50 p-4 text-center">
            <p class="text-xs text-yellow-600 uppercase tracking-wide mb-1">Open Revenue</p>
            <p class="text-2xl font-extrabold text-yellow-700">{{ number_format($stats['open_sum_cents'] / 100) }} EGP</p>
        </div>
        <div class="bg-white rounded-2xl border border-green-50 bg-green-50 p-4 text-center">
            <p class="text-xs text-green-600 uppercase tracking-wide mb-1">Paid Revenue</p>
            <p class="text-2xl font-extrabold text-green-700">{{ number_format($stats['paid_sum_cents'] / 100) }} EGP</p>
        </div>
    </div>

    {{-- ─── Filter ───────────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="label-sm">Status</label>
                <select id="filter-status" class="form-select text-sm py-1.5 pr-8">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="paid">Paid</option>
                    <option value="void">Void</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <table id="tbl-invoices" class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Invoice #</th>
                    <th class="px-4 py-3 text-left">Vendor</th>
                    <th class="px-4 py-3 text-left">Plan</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-left">Paid At</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- ─── Mark Paid Modal ─────────────────────────────────────────────────────── --}}
    <div id="mark-paid-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-4">Mark Invoice Paid</h3>
            <input type="hidden" id="mp-invoice-id">
            <div>
                <label class="label-sm">Payment Transaction ID (optional)</label>
                <input type="text" id="mp-txid" class="form-input w-full text-sm" placeholder="e.g. TXN-123456">
            </div>
            <div class="flex gap-3 justify-end mt-5 pt-4 border-t border-gray-100">
                <button type="button" id="mp-close" class="btn btn-ghost btn-sm">Close</button>
                <button type="button" id="mp-confirm" class="btn btn-success btn-sm">Mark Paid</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            const tok = '{{ csrf_token() }}';

            const tbl = $('#tbl-invoices').DataTable({
                serverSide: true,
                processing: true,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route('admin.subscriptions.invoices.datatable') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok },
                    data: d => { d.status = $('#filter-status').val(); },
                },
                columns: [
                    { data: 'invoice_number', orderable: false },
                    { data: 'vendor', orderable: false },
                    { data: 'plan', orderable: false },
                    { data: 'amount', orderable: false },
                    { data: 'status', orderable: false },
                    { data: 'period', orderable: false },
                    { data: 'paid_at', orderable: false },
                    { data: 'actions', orderable: false },
                ],
                language: { processing: 'Loading…' },
            });

            $('#filter-status').on('change', () => tbl.ajax.reload());

            // ── Mark paid ──────────────────────────────────────────────────────────────
            $(document).on('click', '.btn-mark-paid', function () {
                $('#mp-invoice-id').val($(this).data('id'));
                $('#mp-txid').val('');
                $('#mark-paid-modal').show();
            });
            $('#mp-close').on('click', () => $('#mark-paid-modal').hide());
            $('#mp-confirm').on('click', function () {
                const id = $('#mp-invoice-id').val();
                fetch('/admin/subscriptions/invoices/' + id + '/mark-paid', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_transaction_id: $('#mp-txid').val() }),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); $('#mark-paid-modal').hide(); tbl.ajax.reload(); }
                    else { window.Toast.error(data.message); }
                });
            });
        });
    </script>
@endpush
