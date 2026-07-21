@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.marketers.marketer_campaigns_title'))

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.marketers.marketer_campaigns_title') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.marketers.marketer_campaigns_desc') }}</p>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        [__('admin.marketers.total'),          $stats['total'],         'gray'],
        [__('admin.marketers.active'),         $stats['active'],         'success'],
        [__('admin.marketers.pending_approval'), $stats['pending'],        'warning'],
        [__('admin.marketers.total_clicks'),   number_format($stats['total_clicks']), 'primary'],
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
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketers.search') }}</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.marketers.campaign_name') }}…">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.status') }}</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">{{ __('admin.marketers.all_statuses') }}</option>
                <option value="draft">{{ __('admin.marketers.draft') }}</option>
                <option value="pending_review">{{ \App\Enums\MarketerCampaignStatus::PendingReview->label() }}</option>
                <option value="active">{{ __('admin.marketers.active') }}</option>
                <option value="paused">{{ __('admin.marketers.paused') }}</option>
                <option value="rejected">{{ \App\Enums\MarketerCampaignStatus::Rejected->label() }}</option>
                <option value="ended">{{ __('admin.marketers.ended') }}</option>
                <option value="cancelled">{{ __('admin.marketers.cancelled') }}</option>
            </select>
        </div>
        <div class="w-40">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketers.type') }}</label>
            <select id="filter-type" class="form-input w-full text-sm">
                <option value="">{{ __('admin.marketers.all_types') }}</option>
                <option value="referral_link">{{ __('admin.marketers.referral_link') }}</option>
                <option value="discount_code">{{ __('admin.marketers.discount_code') }}</option>
                <option value="product_specific">{{ __('admin.marketers.product_specific') }}</option>
                <option value="brand_deal">{{ __('admin.marketers.brand_deal') }}</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketers.target') }}</label>
            <select id="filter-target-type" class="form-input w-full text-sm">
                <option value="">{{ __('admin.marketers.all_targets') }}</option>
                <option value="vendor">{{ __('admin.marketers.vendor') }}</option>
                <option value="classified">{{ __('admin.marketers.classified') }}</option>
                <option value="travel">{{ __('admin.marketers.travel') }}</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">{{ __('admin.marketers.reset') }}</button>
    </div>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
    <table id="campaigns-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('admin.marketers.campaign') }}</th>
                <th>{{ __('admin.marketers.marketer') }}</th>
                <th>{{ __('admin.marketers.type') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.marketers.clicks') }}</th>
                <th>{{ __('admin.marketers.conv') }}</th>
                <th>{{ __('admin.marketers.revenue') }}</th>
                <th>{{ __('admin.marketers.starts') }}</th>
                <th>{{ __('admin.marketers.actions') }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>
</x-card>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-campaign-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('admin.marketers.reject_campaign_title') }}</h3>
        <input type="hidden" id="reject-campaign-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.marketers.reason') }}</label>
            <textarea id="reject-campaign-reason" rows="3" class="form-input w-full text-sm"
                placeholder="{{ __('admin.marketers.reject_campaign_reason_placeholder') }}"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-campaign" class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
            <button type="button" id="confirm-reject-campaign" class="btn btn-danger btn-sm">{{ __('admin.marketers.reject') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        approveCampaignConfirm: @json(__('admin.marketers.approve_campaign_confirm')),
        approve: @json(__('admin.marketers.approve')),
        pleaseEnterReason: @json(__('admin.marketers.please_enter_reason')),
    });
</script>
<script type="module">
document.addEventListener('DOMContentLoaded', function () {
    const tok = '{{ csrf_token() }}';

    const table = $('#campaigns-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.campaigns.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_status      = $('#filter-status').val();
                d.filter_type        = $('#filter-type').val();
                d.filter_target_type = $('#filter-target-type').val();
                d.search             = { value: $('#search-input').val() };
            }
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {}, {}, { orderable: false }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
    });

    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status, #filter-type, #filter-target-type').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input').val('');
        $('#filter-status, #filter-type, #filter-target-type').val('');
        table.ajax.reload();
    });

    // Approve campaign
    $(document).on('click', '.btn-approve-campaign', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: window.TRANSLATIONS.approveCampaignConfirm, confirmButtonText: window.TRANSLATIONS.approve }).then(confirmed => {
            if (!confirmed) return;
            fetch('/marketer-campaigns/' + id + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                body: '{}'
            }).then(r => r.json()).then(data => {
                if (data.success) { window.Toast.success(data.message); table.ajax.reload(); }
                else { window.Toast.error(data.message); }
            });
        });
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
        if (!reason) { window.Toast.warning(window.TRANSLATIONS.pleaseEnterReason); return; }
        fetch('/marketer-campaigns/' + id + '/reject', {
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
}, { once: true });
</script>
@endpush
