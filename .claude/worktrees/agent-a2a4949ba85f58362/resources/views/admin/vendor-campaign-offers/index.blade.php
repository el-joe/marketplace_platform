@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.vendor_campaign_offers.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.vendor_campaign_offers.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review and approve campaign offers submitted by vendors.</p>
        </div>
    </div>

    {{-- ─── Stats ─────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="Pending Review"
            :value="number_format($stats['pending'])"
            icon="clock"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card
            title="{{ __('common.active') }}"
            :value="number_format($stats['active'])"
            icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card
            title="Draft"
            :value="number_format($stats['draft'])"
            icon="pencil-square"
            iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card
            title="Ended"
            :value="number_format($stats['ended'])"
            icon="archive-box"
            iconBg="bg-gray-100 text-gray-600" />
    </div>

    {{-- ─── Filter bar ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.search') }}</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Offer name, vendor…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.status') }}</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending_admin">Pending Review</option>
                    <option value="active">{{ __('common.active') }}</option>
                    <option value="draft">Draft</option>
                    <option value="paused">Paused</option>
                    <option value="ended">Ended</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="w-52">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.vendor') }}</label>
                <select id="filter-vendor" class="form-input w-full text-sm">
                    <option value="">All vendors</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.valid_from') }}</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.vendor_campaign_offers.valid_until') }}</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="filter-reset" class="btn btn-secondary btn-sm self-end">{{ __('common.reset') }}</button>
        </form>
    </x-card>

    {{-- ─── DataTable ──────────────────────────────────────────────────────────────── --}}
    <x-card>
        <table id="offers-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.vendor') }}</th>
                    <th class="pb-3 pr-3">Offer Name</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.offer_type') }}</th>
                    <th class="pb-3 pr-3">{{ __('admin.vendor_campaign_offers.offer_value') }}</th>
                    <th class="pb-3 pr-3">Products</th>
                    <th class="pb-3 pr-3">Date Range</th>
                    <th class="pb-3 pr-3">Invited</th>
                    <th class="pb-3 pr-3">{{ __('admin.status') }}</th>
                    <th class="pb-3">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ─── Reject modal ───────────────────────────────────────────────────────────── --}}
    <div id="reject-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('admin.vendor_campaign_offers.reject') }} Campaign Offer</h3>
            <p class="text-sm text-gray-500 mb-4">Provide a reason. The vendor will see this and can fix and resubmit.</p>
            <textarea id="reject-reason" rows="4" class="form-input w-full text-sm mb-4" placeholder="Rejection reason…"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" id="reject-cancel" class="btn btn-secondary">{{ __('common.cancel') }}</button>
                <button type="button" id="reject-confirm" class="btn btn-danger">{{ __('admin.vendor_campaign_offers.reject') }} Offer</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const dtUrl    = '{{ route('admin.vendor-campaign-offers.datatable') }}';
    const headers  = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };

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
        if (!confirm(`Approve "${btn.dataset.name}"? Marketers will be notified.`)) return;
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
        if (!reason) { alert('Please enter a rejection reason.'); return; }
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
