@extends('layouts.admin')

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.affiliate_promo_codes_section.title'))

@section('content')

<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.affiliate_promo_codes_section.title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.affiliate_promo_codes_section.subtitle') }}</p>
    </div>
    <button type="button" id="create-promo-btn" class="btn btn-primary btn-sm">{{ __('admin.affiliate_promo_codes_section.create_promo_code') }}</button>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => __('admin.affiliate_promo_codes_section.stat_total_codes'), 'value' => number_format($stats['total']), 'color' => 'gray'],
        ['label' => __('admin.affiliate_promo_codes_section.stat_active'), 'value' => number_format($stats['active']), 'color' => 'success'],
        ['label' => __('admin.affiliate_promo_codes_section.stat_total_uses'), 'value' => number_format($stats['total_uses']), 'color' => 'primary'],
        ['label' => __('admin.affiliate_promo_codes_section.stat_total_revenue'), 'value' => number_format($stats['total_revenue'] / 100, 2), 'color' => 'primary'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.affiliate_promo_codes_section.affiliate_label') }}</label>
            <select id="filter-marketer" class="form-input w-full text-sm">
                <option value="">{{ __('admin.affiliate_promo_codes_section.all_affiliates') }}</option>
                @foreach($affiliates as $affiliate)
                    <option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.affiliate_promo_codes_section.status_label') }}</label>
            <select id="filter-active" class="form-input w-full text-sm">
                <option value="">{{ __('admin.affiliate_promo_codes_section.all_option') }}</option>
                <option value="1">{{ __('admin.affiliate_promo_codes_section.active_option') }}</option>
                <option value="0">{{ __('admin.affiliate_promo_codes_section.inactive_option') }}</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">{{ __('admin.affiliate_promo_codes_section.reset') }}</button>
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
        <table id="promo-codes-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('admin.affiliate_promo_codes_section.code_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.marketer_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.discount_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.uses_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.revenue_generated_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.commission_earned_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.status_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.valid_until_col') }}</th>
                    <th>{{ __('admin.affiliate_promo_codes_section.actions_col') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-card>

{{-- ─── Create Promo Code Modal ─────────────────────────────────────────────── --}}
<div id="create-promo-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.affiliate_promo_codes_section.create_promo_code') }}</h3>
            <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
        </div>
        <form id="create-promo-form">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.affiliate_required') }} <span class="text-red-500">*</span></label>
                    <select name="marketer_id" class="form-input w-full" required>
                        <option value="">{{ __('admin.affiliate_promo_codes_section.select_affiliate_placeholder') }}</option>
                        @foreach($affiliates as $affiliate)
                            <option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.code_label') }}</label>
                    <input type="text" name="code" class="form-input w-full" placeholder="{{ __('admin.affiliate_promo_codes_section.code_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.discount_type_required') }} <span class="text-red-500">*</span></label>
                    <select name="discount_type" class="form-input w-full" required>
                        <option value="percentage">{{ __('admin.affiliate_promo_codes_section.discount_type_percentage') }}</option>
                        <option value="fixed_amount">{{ __('admin.affiliate_promo_codes_section.discount_type_fixed_amount') }}</option>
                        <option value="free_shipping">{{ __('admin.affiliate_promo_codes_section.discount_type_free_shipping') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.discount_value_required') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="discount_value" class="form-input w-full" step="0.01" min="0" required>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.currency_label') }}</label>
                    <input type="text" name="currency" class="form-input w-full" maxlength="3" placeholder="{{ __('admin.affiliate_promo_codes_section.currency_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.max_uses') }}</label>
                    <input type="number" name="max_uses" class="form-input w-full" min="1" placeholder="{{ __('admin.affiliate_promo_codes_section.max_uses_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.min_order_amount') }}</label>
                    <input type="number" name="min_order_amount" class="form-input w-full" min="0" placeholder="{{ __('admin.affiliate_promo_codes_section.min_order_amount_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.valid_from_required') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="valid_from" class="form-input w-full" required>
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.affiliate_promo_codes_section.valid_until_required') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="valid_until" class="form-input w-full" required>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.affiliate_promo_codes_section.cancel') }}</button>
                <button type="submit" id="create-promo-submit" class="btn btn-primary btn-sm">{{ __('admin.affiliate_promo_codes_section.create_promo_code') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    somethingWentWrong: @json(__('admin.affiliate_promo_codes_section.something_went_wrong')),
    disablePromoConfirm: @json(__('admin.affiliate_promo_codes_section.disable_promo_confirm')),
});

$(function () {
    const tok = '{{ csrf_token() }}';

    const table = $('#promo-codes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.affiliate-promo-codes.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_marketer = $('#filter-marketer').val();
                d.filter_active = $('#filter-active').val();
            },
        },
        columns: [{}, {}, {}, {}, {}, {}, {}, {}, { orderable: false, searchable: false }],
        order: [[7, 'desc']],
        pageLength: 25,
    });

    $('#filter-marketer, #filter-active').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', () => {
        $('#filter-marketer').val('');
        $('#filter-active').val('');
        table.ajax.reload();
    });

    // ── Create modal ────────────────────────────────────────────────────────
    $('#create-promo-btn').on('click', () => $('#create-promo-modal').removeClass('hidden'));
    $('#create-promo-modal [data-modal-close]').on('click', () => $('#create-promo-modal').addClass('hidden'));

    $('#create-promo-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());

        $.ajax({
            url: '{{ route('admin.affiliate-promo-codes.store') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data,
        })
            .done(r => {
                window.Toast.success(r.message);
                $('#create-promo-modal').addClass('hidden');
                this.reset();
                table.ajax.reload();
            })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
    });

    // ── Row actions (delegated) ─────────────────────────────────────────────
    $(document).on('click', '.btn-toggle-promo', function () {
        const id = $(this).data('id');
        $.post('{{ url('affiliate-promo-codes') }}/' + id + '/toggle', { _token: tok })
            .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
    });

    $(document).on('click', '.btn-disable-promo', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: window.TRANSLATIONS.disablePromoConfirm, onConfirm: () => {
            $.ajax({
                url: '{{ url('affiliate-promo-codes') }}/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': tok },
            })
                .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
        }});
    });
});
</script>
@endpush
