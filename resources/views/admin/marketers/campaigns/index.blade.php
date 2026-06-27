@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Marketer Campaigns')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Marketer Campaigns</h1>
    <p class="text-sm text-gray-500 mt-0.5">Review and approve campaigns submitted by marketers.</p>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total',          $stats['total'],         'gray'],
        ['Active',         $stats['active'],         'success'],
        ['Pending Review', $stats['pending'],        'warning'],
        ['Total Clicks',   number_format($stats['total_clicks']), 'primary'],
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
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Campaign name…">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="ended">Ended</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="w-40">
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select id="filter-type" class="form-input w-full text-sm">
                <option value="">All types</option>
                <option value="referral_link">Referral Link</option>
                <option value="discount_code">Discount Code</option>
                <option value="product_specific">Product Specific</option>
                <option value="brand_deal">Brand Deal</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </div>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <table id="campaigns-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>Campaign</th>
                <th>Marketer</th>
                <th>Type</th>
                <th>Status</th>
                <th>Clicks</th>
                <th>Conv.</th>
                <th>Revenue (SAR)</th>
                <th>Starts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-campaign-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Reject Campaign</h3>
        <input type="hidden" id="reject-campaign-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <textarea id="reject-campaign-reason" rows="3" class="form-input w-full text-sm"
                placeholder="Explain why this campaign is rejected…"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-campaign" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-reject-campaign" class="btn btn-danger btn-sm">Reject</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
$(function () {
    const tok = '{{ csrf_token() }}';

    const table = $('#campaigns-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.campaigns.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_status = $('#filter-status').val();
                d.filter_type   = $('#filter-type').val();
                d.search        = { value: $('#search-input').val() };
            }
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {}, {}, { orderable: false }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
    });

    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status, #filter-type').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input').val('');
        $('#filter-status, #filter-type').val('');
        table.ajax.reload();
    });

    // Approve campaign
    $(document).on('click', '.btn-approve-campaign', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Approve campaign?', confirmText: 'Approve', onConfirm: () => {
            fetch('/marketers/campaigns/' + id + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                body: '{}'
            }).then(r => r.json()).then(data => {
                if (data.success) { window.Toast.success(data.message); table.ajax.reload(); }
                else { window.Toast.error(data.message); }
            });
        }});
    });

    // Reject campaign
    $(document).on('click', '.btn-reject-campaign', function () {
        $('#reject-campaign-id').val($(this).data('id'));
        $('#reject-campaign-reason').val('');
        $('#reject-campaign-modal').show();
    });
    $('#cancel-reject-campaign').on('click', () => $('#reject-campaign-modal').hide());
    $('#confirm-reject-campaign').on('click', function () {
        const id     = $('#reject-campaign-id').val();
        const reason = $('#reject-campaign-reason').val().trim();
        if (!reason) { window.Toast.warning('Please enter a reason.'); return; }
        fetch('/marketers/campaigns/' + id + '/reject', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason }),
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); $('#reject-campaign-modal').hide(); table.ajax.reload(); }
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
