@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.vendor_campaign_offers.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.vendor_campaign_offers.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.vendor_campaign_offers.page_subtitle') }}</p>
        </div>
    </div>

    {{-- ─── Stats ─────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="{{ __('admin.vendor_campaign_offers.pending_review_stat') }}"
            :value="number_format($stats['pending'])"
            icon="clock"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card
            title="{{ __('admin.vendor_campaign_offers.active_stat') }}"
            :value="number_format($stats['active'])"
            icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card
            title="{{ __('admin.vendor_campaign_offers.draft_stat') }}"
            :value="number_format($stats['draft'])"
            icon="pencil-square"
            iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card
            title="{{ __('admin.vendor_campaign_offers.ended_stat') }}"
            :value="number_format($stats['ended'])"
            icon="archive-box"
            iconBg="bg-gray-100 text-gray-600" />
    </div>

    {{-- ─── Filter bar ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.search') }}</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.vendor_campaign_offers.search_placeholder') }}">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.status_column') }}</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.vendor_campaign_offers.all_statuses') }}</option>
                    <option value="pending_admin">{{ __('admin.vendor_campaign_offers.pending_review_option') }}</option>
                    <option value="active">{{ __('admin.vendor_campaign_offers.active_option') }}</option>
                    <option value="draft">{{ __('admin.vendor_campaign_offers.draft_option') }}</option>
                    <option value="paused">{{ __('admin.vendor_campaign_offers.paused_option') }}</option>
                    <option value="ended">{{ __('admin.vendor_campaign_offers.ended_option') }}</option>
                    <option value="cancelled">{{ __('admin.vendor_campaign_offers.cancelled_option') }}</option>
                </select>
            </div>
            <div class="w-52">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.vendor') }}</label>
                <select id="filter-vendor" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.vendor_campaign_offers.all_vendors') }}</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.starts_from') }}</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.ends_before') }}</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="filter-reset" class="btn btn-secondary btn-sm self-end">{{ __('admin.vendor_campaign_offers.reset') }}</button>
        </form>
    </x-card>

    {{-- ─── DataTable ──────────────────────────────────────────────────────────────── --}}
    <x-card>
        <table id="offers-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.vendor_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.offer_name_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.type_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.commission_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.products_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.date_range_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.invited_column') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.status_column') }}</th>
                    <th class="pb-3">{{ __('admin.vendor_campaign_offers.actions_column') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ─── Reject modal ───────────────────────────────────────────────────────────── --}}
    <div id="reject-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('admin.vendor_campaign_offers.reject_offer_title') }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('admin.vendor_campaign_offers.reject_offer_subtitle') }}</p>
            <textarea id="reject-reason" rows="4" class="form-input w-full text-sm mb-4" placeholder="{{ __('admin.vendor_campaign_offers.rejection_reason_placeholder') }}"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" id="reject-cancel" class="btn btn-secondary">{{ __('admin.vendor_campaign_offers.cancel') }}</button>
                <button type="button" id="reject-confirm" class="btn btn-danger">{{ __('admin.vendor_campaign_offers.reject_offer_btn') }}</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const dtUrl    = '{{ route('admin.vendor-campaign-offers.datatable') }}';
    const headers  = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };
    const T = {
        approveConfirm: @json(__('admin.vendor_campaign_offers.approve_confirm')),
        rejectionReasonRequired: @json(__('admin.vendor_campaign_offers.rejection_reason_required')),
    };

    const table = window.initDataTable('#offers-table', {
        ajax: {
            url: dtUrl,
            type: 'POST',
            headers,
            data: d => {
                d.status    = document.getElementById('filter-status').value;
                d.vendor_id = document.getElementById('filter-vendor').value;
                d.date_from = document.getElementById('filter-date-from').value;
                d.date_to   = document.getElementById('filter-date-to').value;
            },
        },
        columns: [
            { data: 'vendor' },
            { data: 'name' },
            { data: 'type' },
            { data: 'commission' },
            { data: 'products' },
            { data: 'date_range' },
            { data: 'invited' },
            { data: 'status' },
            { data: 'actions', orderable: false },
        ],
        order: [[7, 'desc']],
        pageLength: 25,
    });

    // Filters
    document.getElementById('search-input').addEventListener('input', () => table.search(document.getElementById('search-input').value).draw());
    ['filter-status', 'filter-vendor', 'filter-date-from', 'filter-date-to'].forEach(id =>
        document.getElementById(id).addEventListener('change', () => table.draw())
    );
    document.getElementById('filter-reset').addEventListener('click', () => {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-vendor').value = '';
        document.getElementById('filter-date-from').value = '';
        document.getElementById('filter-date-to').value = '';
        document.getElementById('search-input').value = '';
        table.search('').draw();
    });

    // Approve
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-approve-btn');
        if (!btn) return;
        if (!confirm(T.approveConfirm.replace(':name', btn.dataset.name))) return;
        fetch(btn.dataset.url, { method: 'POST', headers })
            .then(r => r.json())
            .then(d => { alert(d.message); table.draw(); });
    });

    // Reject modal
    let rejectUrl = '';
    const modal      = document.getElementById('reject-modal');
    const reasonEl   = document.getElementById('reject-reason');

    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-reject-btn');
        if (!btn) return;
        rejectUrl = btn.dataset.url;
        reasonEl.value = '';
        modal.classList.remove('hidden');
    });

    document.getElementById('reject-cancel').addEventListener('click', () => modal.classList.add('hidden'));

    document.getElementById('reject-confirm').addEventListener('click', () => {
        const reason = reasonEl.value.trim();
        if (!reason) { alert(T.rejectionReasonRequired); return; }
        fetch(rejectUrl, {
            method: 'POST',
            headers: { ...headers, 'Content-Type': 'application/json' },
            body: JSON.stringify({ rejection_reason: reason }),
        })
            .then(r => r.json())
            .then(d => { alert(d.message); modal.classList.add('hidden'); table.draw(); });
    });
})();
</script>
@endpush
