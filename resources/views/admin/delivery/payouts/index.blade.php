@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', 'Delivery Payouts')

@section('content')

{{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Delivery Payouts</h1>
        <p class="text-sm text-gray-500 mt-0.5">Generate and process agent payout cycles.</p>
    </div>
    <button type="button" id="generate-btn" class="btn btn-primary btn-sm">+ Generate Payouts</button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Payouts',  'value' => number_format($stats['total'])],
        ['label' => 'Pending',        'value' => number_format($stats['pending'])],
        ['label' => 'Approved',       'value' => number_format($stats['approved'])],
        ['label' => 'Paid',           'value' => number_format($stats['paid'])],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="w-40">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Agent</label>
            <select id="filter-agent" class="form-input w-full text-sm">
                <option value="">All agents</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Period From</label>
            <input type="date" id="filter-from" class="form-input text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Period To</label>
            <input type="date" id="filter-to" class="form-input text-sm">
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
                <th>Agent</th>
                <th>Period</th>
                <th>Deliveries</th>
                <th>Gross</th>
                <th>Deductions</th>
                <th>Net</th>
                <th>Status</th>
                <th>Approved By</th>
                <th>Processed At</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</x-card>

{{-- ─── Generate Payouts Modal ──────────────────────────────────────────────── --}}
<div id="generate-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Generate Payouts</h3>
            <button type="button" onclick="document.getElementById('generate-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="generate-form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period Start <span class="text-red-500">*</span></label>
                    <input type="date" name="period_start" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period End <span class="text-red-500">*</span></label>
                    <input type="date" name="period_end" class="form-input w-full" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                <select name="agent_id" class="form-input w-full">
                    <option value="">All eligible agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Leave blank to generate for all agents with pending earnings.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency <span class="text-red-500">*</span></label>
                <select name="currency" class="form-input w-full" required>
                    <option value="USD">USD</option>
                    <option value="EGP">EGP</option>
                    <option value="SAR">SAR</option>
                    <option value="AED">AED</option>
                </select>
            </div>
            <div class="pt-4 border-t flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('generate-modal').classList.add('hidden')"
                    class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Generate</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Process Payout Modal ────────────────────────────────────────────────── --}}
<div id="process-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Process Payout</h3>
            <button type="button" onclick="document.getElementById('process-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="process-form" class="space-y-4">
            @csrf
            <input type="hidden" id="process-payout-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_method" class="form-input w-full" required>
                    <option value="">Select method</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="mobile_wallet">Mobile Wallet</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Reference</label>
                <input type="text" name="payment_reference" class="form-input w-full" placeholder="Transaction ID, cheque #…">
            </div>
            <div class="pt-4 border-t flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('process-modal').classList.add('hidden')"
                    class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Mark as Paid</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const DATATABLE_URL = @json(route('admin.delivery.payouts.datatable'));
    const GENERATE_URL  = @json(route('admin.delivery.payouts.generate'));
    const BASE_URL      = @json(url('admin/delivery/payouts'));
    const token         = () => $('meta[name=csrf-token]').attr('content');

    // ── DataTable ─────────────────────────────────────────────────────────────
    const table = $('#payouts-table').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url  : DATATABLE_URL,
            type : 'POST',
            data : d => Object.assign(d, {
                _token   : token(),
                status   : $('#filter-status').val(),
                agent_id : $('#filter-agent').val(),
                from     : $('#filter-from').val(),
                to       : $('#filter-to').val(),
            }),
        },
        columns: [
            { data: 'payout_number', render: v => `<code class="text-xs font-mono">${v}</code>` },
            { data: 'agent_name' },
            { data: 'period', render: (v, t, r) => `${r.period_start} – ${r.period_end}` },
            { data: 'total_deliveries' },
            { data: 'gross_earnings_cents',  render: v => (v / 100).toFixed(2) },
            { data: 'deductions_cents',      render: v => (v / 100).toFixed(2) },
            { data: 'net_amount_cents',      render: v => `<strong>${(v / 100).toFixed(2)}</strong>` },
            {
                data: 'status',
                render: v => {
                    const c = { pending:'warning', approved:'primary', paid:'success', failed:'danger' }[v] ?? 'gray';
                    return `<span class="badge badge-${c} text-xs capitalize">${v}</span>`;
                }
            },
            { data: 'approved_by', defaultContent: '—' },
            { data: 'processed_at', defaultContent: '—' },
            {
                data: null, orderable: false,
                render: r => {
                    const btns = [];
                    if (r.status === 'pending') {
                        btns.push(`<button type="button" class="approve-btn btn btn-xs btn-success" data-id="${r.id}">Approve</button>`);
                    }
                    if (r.status === 'approved') {
                        btns.push(`<button type="button" class="process-btn btn btn-xs btn-primary" data-id="${r.id}">Process</button>`);
                    }
                    return btns.join(' ') || '—';
                }
            },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
    });

    // Filters
    ['#filter-status','#filter-agent','#filter-from','#filter-to'].forEach(sel =>
        $(sel).on('change', () => table.ajax.reload())
    );
    $('#clear-filters').on('click', () => {
        $('#filter-status, #filter-agent').val('');
        $('#filter-from, #filter-to').val('');
        table.ajax.reload();
    });

    // ── Generate Payouts ──────────────────────────────────────────────────────
    $('#generate-btn').on('click', () =>
        document.getElementById('generate-modal').classList.remove('hidden')
    );
    $('#generate-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url    : GENERATE_URL,
            method : 'POST',
            data   : $(this).serialize() + '&_token=' + token(),
            success: res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    document.getElementById('generate-modal').classList.add('hidden');
                    this.reset();
                    table.ajax.reload();
                }
            },
            error: xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Generation failed.'),
        });
    });

    // ── Approve ───────────────────────────────────────────────────────────────
    $(document).on('click', '.approve-btn', function () {
        const id = $(this).data('id');
        if (!confirm('Approve this payout?')) return;
        $.ajax({
            url    : `${BASE_URL}/${id}/approve`,
            method : 'POST',
            data   : { _token: token() },
            success: res => { if (res.success) { window.Toast?.success(res.message); table.ajax.reload(); } },
            error  : xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'),
        });
    });

    // ── Process ───────────────────────────────────────────────────────────────
    $(document).on('click', '.process-btn', function () {
        $('#process-payout-id').val($(this).data('id'));
        document.getElementById('process-modal').classList.remove('hidden');
    });
    $('#process-form').on('submit', function (e) {
        e.preventDefault();
        const id = $('#process-payout-id').val();
        $.ajax({
            url    : `${BASE_URL}/${id}/process`,
            method : 'POST',
            data   : $(this).serialize() + '&_token=' + token(),
            success: res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    document.getElementById('process-modal').classList.add('hidden');
                    this.reset();
                    table.ajax.reload();
                }
            },
            error: xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Processing failed.'),
        });
    });
})();
</script>
@endpush
