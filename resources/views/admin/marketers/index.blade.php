@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Marketers')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Marketers & Influencers</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage marketer accounts, approvals and commissions.</p>
    </div>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Marketers',    'value' => $stats['total'],             'color' => 'gray'],
        ['label' => 'Active',             'value' => $stats['active'],            'color' => 'success'],
        ['label' => 'Pending Approval',   'value' => $stats['pending'],           'color' => 'warning'],
        ['label' => 'Commissions (MTD)',  'value' => number_format($stats['commissions_month'] / 100, 2) , 'color' => 'primary'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Type Tabs ───────────────────────────────────────────────────────────── --}}
<div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 w-fit" id="type-tabs">
    @foreach(['', 'influencer', 'celebrity', 'affiliate', 'brand_ambassador'] as $type)
        <button type="button" data-type="{{ $type }}"
                class="type-tab px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors {{ $type === '' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            {{ $type === '' ? 'All' : ucfirst(str_replace('_', ' ', $type)) }}
        </button>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Name, email…">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
            <select id="filter-country" class="form-input w-full text-sm">
                <option value="">All countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <table id="marketers-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name / Email</th>
                <th>Type</th>
                <th>Country</th>
                <th>Followers</th>
                <th>Clicks</th>
                <th>Conv.</th>
                <th>Earnings (SAR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-marketer-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Reject Marketer</h3>
        <input type="hidden" id="reject-marketer-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <textarea id="reject-reason" rows="3" class="form-input w-full text-sm"
                placeholder="Explain why this marketer is rejected…"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-marketer" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-reject-marketer" class="btn btn-danger btn-sm">Reject</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')

<script type="module">
$(function () {
    let currentType = '';

    const table = $('#marketers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.all.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: function (d) {
                d.filter_type    = currentType;
                d.filter_status  = $('#filter-status').val();
                d.filter_country = $('#filter-country').val();
                d.search         = { value: $('#search-input').val() };
            }
        },
        columns: [
            { orderable: false, width: '40px' },
            {},
            {},
            {},
            {},
            {},
            {},
            {},
            {},
            { orderable: false, width: '140px' },
        ],
        pageLength: 25,
        order: [[1, 'asc']],
    });

    // Type tab switching
    $(document).on('click', '.type-tab', function () {
        $('.type-tab').removeClass('bg-white text-gray-800 shadow-sm').addClass('text-gray-500');
        $(this).addClass('bg-white text-gray-800 shadow-sm').removeClass('text-gray-500');
        currentType = $(this).data('type');
        table.ajax.reload();
    });

    // Filters
    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status, #filter-country').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input').val('');
        $('#filter-status, #filter-country').val('');
        currentType = '';
        $('.type-tab[data-type=""]').click();
        table.ajax.reload();
    });

    // Approve
    $(document).on('click', '.btn-approve-marketer', function () {
        const id = $(this).data('id');
        window.confirmDialog({
            title: 'Approve Marketer?',
            text: 'The marketer will be notified and gain access to the portal.',
            confirmText: 'Approve',
            onConfirm: () => {
                $.post('{{ url('admin/marketers') }}/' + id + '/approve', { _token: '{{ csrf_token() }}' })
                    .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
            }
        });
    });

    // Reject
    $(document).on('click', '.btn-reject-marketer', function () {
        $('#reject-marketer-id').val($(this).data('id'));
        $('#reject-reason').val('');
        $('#reject-marketer-modal').show();
    });
    $('#cancel-reject-marketer').on('click', () => $('#reject-marketer-modal').hide());
    $('#confirm-reject-marketer').on('click', function () {
        const id = $('#reject-marketer-id').val();
        const reason = $('#reject-reason').val().trim();
        if (!reason) { window.Toast.warning('Please enter a reason.'); return; }
        $.post('{{ url('admin/marketers') }}/' + id + '/reject', { _token: '{{ csrf_token() }}', reason })
            .done(r => { window.Toast.success(r.message); $('#reject-marketer-modal').hide(); table.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });

    // Suspend
    $(document).on('click', '.btn-suspend-marketer', function () {
        const id = $(this).data('id');
        window.confirmDialog({
            title: 'Suspend Marketer?',
            confirmText: 'Suspend',
            onConfirm: () => {
                $.post('{{ url('admin/marketers') }}/' + id + '/suspend', { _token: '{{ csrf_token() }}' })
                    .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
            }
        });
    });

    // Activate
    $(document).on('click', '.btn-activate-marketer', function () {
        const id = $(this).data('id');
        $.post('{{ url('admin/marketers') }}/' + id + '/activate', { _token: '{{ csrf_token() }}' })
            .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });

    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }
});
</script>
@endpush
