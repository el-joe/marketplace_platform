@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.influencer_deals_section.title'))

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.influencer_deals_section.title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.influencer_deals_section.subtitle') }}</p>
    </div>
    <button type="button" id="propose-deal-btn" class="btn btn-primary btn-sm">{{ __('admin.influencer_deals_section.propose_new_deal') }}</button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => __('admin.influencer_deals_section.stat_total_deals'),       'value' => $stats['total'],            'color' => 'gray'],
        ['label' => __('admin.influencer_deals_section.stat_proposed'),          'value' => $stats['proposed'],         'color' => 'warning'],
        ['label' => __('admin.influencer_deals_section.stat_pending_approval'),  'value' => $stats['pending_approval'], 'color' => 'primary'],
        ['label' => __('admin.influencer_deals_section.stat_paid'),              'value' => $stats['paid'],             'color' => 'success'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_deals_section.status_label') }}</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">{{ __('admin.influencer_deals_section.all_statuses') }}</option>
                <option value="proposed">{{ __('admin.influencer_deals_section.status_proposed') }}</option>
                <option value="negotiating">{{ __('admin.influencer_deals_section.status_negotiating') }}</option>
                <option value="accepted">{{ __('admin.influencer_deals_section.status_accepted') }}</option>
                <option value="in_progress">{{ __('admin.influencer_deals_section.status_in_progress') }}</option>
                <option value="content_submitted">{{ __('admin.influencer_deals_section.status_content_submitted') }}</option>
                <option value="approved">{{ __('admin.influencer_deals_section.status_approved') }}</option>
                <option value="paid">{{ __('admin.influencer_deals_section.status_paid') }}</option>
                <option value="cancelled">{{ __('admin.influencer_deals_section.status_cancelled') }}</option>
                <option value="rejected">{{ __('admin.influencer_deals_section.status_rejected') }}</option>
            </select>
        </div>
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_deals_section.marketer_label') }}</label>
            <select id="filter-marketer" class="form-input w-full text-sm">
                <option value="">{{ __('admin.influencer_deals_section.all_marketers') }}</option>
                @foreach($marketers as $marketer)
                    <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_deals_section.vendor_label') }}</label>
            <select id="filter-vendor" class="form-input w-full text-sm">
                <option value="">{{ __('admin.influencer_deals_section.all_vendors') }}</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">{{ __('admin.influencer_deals_section.reset') }}</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
        <table id="influencer-deals-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('admin.influencer_deals_section.deal_name_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.marketer_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.vendor_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.type_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.flat_fee_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.status_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.deliverables_col') }}</th>
                    <th>{{ __('admin.influencer_deals_section.actions_col') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-card>

{{-- ─── Propose Deal Modal ─────────────────────────────────────────────────── --}}
<div id="propose-deal-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.influencer_deals_section.propose_new_deal') }}</h3>
            <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
        </div>
        <form id="propose-deal-form">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.marketer_required') }} <span class="text-red-500">*</span></label>
                    <select name="marketer_id" class="form-input w-full" required>
                        <option value="">{{ __('admin.influencer_deals_section.select_marketer') }}</option>
                        @foreach($marketers as $marketer)
                            <option value="{{ $marketer->id }}">{{ $marketer->name }} ({{ __('admin.marketer_types.' . ($marketer->type?->value ?? $marketer->type)) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.vendor_optional') }}</label>
                    <select name="vendor_id" class="form-input w-full">
                        <option value="">{{ __('admin.influencer_deals_section.none_option') }}</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.influencer_deals_section.deal_name_required') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="deal_name" class="form-input w-full" required>
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.influencer_deals_section.description_label') }}</label>
                    <textarea name="description" rows="2" class="form-input w-full"></textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.deal_type_required') }} <span class="text-red-500">*</span></label>
                    <select name="deal_type" class="form-input w-full" required>
                        <option value="flat_fee">{{ __('admin.influencer_deals_section.deal_type_flat_fee') }}</option>
                        <option value="hybrid">{{ __('admin.influencer_deals_section.deal_type_hybrid') }}</option>
                        <option value="gifting">{{ __('admin.influencer_deals_section.deal_type_gifting') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.hybrid_commission_rate') }}</label>
                    <input type="number" name="hybrid_commission_rate" class="form-input w-full" step="0.01" min="0" max="100">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.flat_fee_amount_required') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="flat_fee_amount" class="form-input w-full" min="0" step="1" required>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.currency_required') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="currency" class="form-input w-full" maxlength="3" placeholder="KWD" required>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.content_due') }}</label>
                    <input type="date" name="content_due_at" class="form-input w-full">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.influencer_deals_section.payment_due') }}</label>
                    <input type="date" name="payment_due_at" class="form-input w-full">
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.influencer_deals_section.negotiation_notes') }}</label>
                    <textarea name="negotiation_notes" rows="2" class="form-input w-full"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.influencer_deals_section.cancel') }}</button>
                <button type="submit" id="propose-deal-submit" class="btn btn-primary btn-sm">{{ __('admin.influencer_deals_section.propose_deal') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    proposingEllipsis: @json(__('admin.influencer_deals_section.proposing_ellipsis')),
    proposeDeal: @json(__('admin.influencer_deals_section.propose_deal')),
    failedToProposeDeal: @json(__('admin.influencer_deals_section.failed_to_propose_deal')),
});

document.addEventListener('DOMContentLoaded', function () {
    const table = $('#influencer-deals-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.influencer-deals.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: function (d) {
                d.filter_status   = $('#filter-status').val();
                d.filter_marketer = $('#filter-marketer').val();
                d.filter_vendor   = $('#filter-vendor').val();
            }
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {},
            { orderable: false, width: '100px' },
        ],
        pageLength: 25,
        order: [[0, 'asc']],
    });

    $('#filter-status, #filter-marketer, #filter-vendor').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#filter-status, #filter-marketer, #filter-vendor').val('');
        table.ajax.reload();
    });

    $('#propose-deal-btn').on('click', function () {
        $('#propose-deal-form')[0].reset();
        $('#propose-deal-modal').modal('open');
    });

    $('#propose-deal-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#propose-deal-submit');
        $btn.prop('disabled', true).text(window.TRANSLATIONS.proposingEllipsis);

        $.ajax({
            url: '{{ route('admin.influencer-deals.propose') }}',
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
        })
        .done(function (res) {
            window.Toast.success(res.message);
            $('#propose-deal-modal').modal('close');
            table.ajax.reload();
        })
        .fail(function (xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.values(errors).flat().forEach(m => window.Toast.error(m));
            } else {
                window.Toast.error(xhr.responseJSON?.message ?? window.TRANSLATIONS.failedToProposeDeal);
            }
        })
        .always(() => $btn.prop('disabled', false).text(window.TRANSLATIONS.proposeDeal));
    });
}, { once: true });
</script>
@endpush
