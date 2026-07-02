@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.marketers.sample_requests_title'))

@section('content')


{{-- Stats bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        [__('admin.marketers.total'),      $stats['total'],      'gray'],
        [__('admin.marketers.requested'),  $stats['requested'],  'warning'],
        [__('admin.marketers.approved'),   $stats['approved'],   'success'],
        [__('admin.marketers.dispatched'), $stats['dispatched'], 'primary'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
            <p class="text-2xl font-bold mt-1 text-gray-800">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex flex-wrap gap-4 items-end">
    <div>
        <label class="form-label text-xs">{{ __('admin.status') }}</label>
        <select id="filter-status" class="form-input text-sm py-1.5">
            <option value="">{{ __('admin.marketers.all') }}</option>
            <option value="requested">{{ __('admin.marketers.requested') }}</option>
            <option value="approved">{{ __('admin.marketers.approved') }}</option>
            <option value="dispatched">{{ __('admin.marketers.dispatched') }}</option>
            <option value="received">{{ __('admin.marketers.received') }}</option>
            <option value="rejected">{{ __('admin.marketers.rejected') }}</option>
        </select>
    </div>
</div>

{{-- DataTable --}}
<x-card>
    <table id="samples-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('admin.marketers.marketer') }}</th>
                <th>{{ __('admin.marketers.sample_vendor') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.marketers.sample_date') }}</th>
                <th>{{ __('admin.marketers.sample_actions') }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</x-card>

{{-- Details modal --}}
<div id="details-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800 text-lg">{{ __('admin.marketers.sample_details_title') }}</h3>
            <button id="details-close" type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="details-body" class="text-sm text-gray-700 space-y-3">
            <div class="flex items-center justify-center py-8 text-gray-400">{{ __('admin.marketers.loading') }}</div>
        </div>
    </div>
</div>

{{-- Approve modal (qty entry) --}}
<div id="approve-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800 text-lg">{{ __('admin.marketers.approve_sample_title') }}</h3>
            <button id="approve-modal-close" type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <p class="text-xs text-gray-400 mb-4">
            {{ __('admin.marketers.approve_sample_desc') }}
        </p>
        <input type="hidden" id="approve-sample-id">
        <div id="approve-items-body">
            <div class="flex items-center justify-center py-8 text-gray-400">{{ __('admin.marketers.loading') }}</div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
            <button id="approve-modal-cancel" type="button" class="btn btn-secondary">{{ __('common.cancel') }}</button>
            <button id="approve-confirm" type="button" class="btn btn-success">{{ __('admin.marketers.approve_set_quantities') }}</button>
        </div>
    </div>
</div>

{{-- Reject modal --}}
<div id="reject-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-semibold text-gray-800 text-lg mb-4">{{ __('admin.marketers.reject_sample_title') }}</h3>
        <input type="hidden" id="reject-sample-id">
        <label class="form-label">{{ __('admin.marketers.rejection_reason') }} <span class="text-red-500">*</span></label>
        <textarea id="reject-reason" rows="3" class="form-input w-full mt-1" placeholder="{{ __('admin.marketers.reject_sample_reason_placeholder') }}"></textarea>
        <div class="flex justify-end gap-3 mt-5">
            <button id="reject-cancel" type="button" class="btn btn-secondary">{{ __('common.cancel') }}</button>
            <button id="reject-confirm" type="button" class="btn btn-danger">{{ __('admin.marketers.reject') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        loading: @json(__('admin.marketers.loading')),
        product: @json(__('admin.marketers.product')),
        qty: @json(__('admin.marketers.qty')),
        cost: @json(__('admin.marketers.cost')),
        marketerLabel: @json(__('admin.marketers.marketer')),
        vendor: @json(__('admin.marketers.vendor')),
        campaign: @json(__('admin.marketers.campaign_label')),
        status: @json(__('admin.status')),
        date: @json(__('admin.marketers.date')),
        notes: @json(__('admin.marketers.notes')),
        rejectionReason: @json(__('admin.marketers.rejection_reason')),
        marketerRequestedSamples: @json(__('admin.marketers.marketer_requested_samples')),
        units: @json(__('admin.marketers.units')),
        noMarketerRequestedItems: @json(__('admin.marketers.no_marketer_requested_items')),
        adminMandatedSamples: @json(__('admin.marketers.admin_mandated_samples')),
        autoAddedQuotaNote: @json(__('admin.marketers.auto_added_quota_note')),
        totalSentToVendor: @json(__('admin.marketers.total_sent_to_vendor')),
        failedToLoadDetails: @json(__('admin.marketers.failed_to_load_details')),
        uncategorised: @json(__('admin.marketers.uncategorised')),
        marketerQty: @json(__('admin.marketers.marketer_qty')),
        adminQtySecret: @json(__('admin.marketers.admin_qty_secret')),
        noItemsInRequest: @json(__('admin.marketers.no_items_in_request')),
        failedToLoadItems: @json(__('admin.marketers.failed_to_load_items')),
        quantitiesNegativeError: @json(__('admin.marketers.quantities_negative_error')),
        noItemsFound: @json(__('admin.marketers.no_items_found')),
        approving: @json(__('admin.marketers.approving')),
        approveSetQuantities: @json(__('admin.marketers.approve_set_quantities')),
        errorGeneric: @json(__('admin.marketers.error_generic')),
        markDispatchedConfirm: @json(__('admin.marketers.mark_dispatched_confirm')),
        rejectionReasonRequired: @json(__('admin.marketers.rejection_reason_required')),
    });
</script>
<script type="module">
$(function () {
    const tok = '{{ csrf_token() }}';
    const T = window.TRANSLATIONS;

    const dt = $('#samples-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.samples.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: d => {
                d.filter_status = $('#filter-status').val();
            },
        },
        columns: [{}, {}, {}, {}, { orderable: false }],
        order: [[3, 'desc']],
        pageLength: 20,
    });

    $('#filter-status').on('change', () => dt.ajax.reload());

    // ── Details modal ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-view-sample', function () {
        const id = $(this).data('id');
        $('#details-body').html(`<div class="flex items-center justify-center py-8 text-gray-400">${T.loading}</div>`);
        $('#details-modal').css('display', 'flex');
        $.get('{{ route('admin.marketers.samples.show', ':id') }}'.replace(':id', id))
            .done(r => {
                const marketerItems = (r.items || []).filter(i => !i.is_mandatory);
                const mandatoryItems = (r.items || []).filter(i => i.is_mandatory);

                const marketerTotal = marketerItems.reduce((s, i) => s + i.quantity, 0);
                const mandatoryTotal = mandatoryItems.reduce((s, i) => s + i.quantity, 0);
                const vendorTotal = marketerTotal + mandatoryTotal;

                const itemTable = (items, emptyMsg) => items.length
                    ? `<table class="w-full text-sm">
                        <thead><tr class="text-left text-gray-500 text-xs">
                            <th class="pb-1 pr-3">${T.product}</th>
                            <th class="pb-1 pr-3 text-center">${T.qty}</th>
                            <th class="pb-1 text-right">${T.cost}</th>
                        </tr></thead>
                        <tbody>${items.map(i => `
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-3">
                                    ${i.product_name}${i.variant_name ? ` <span class="text-gray-400">(${i.variant_name})</span>` : ''}
                                    <div class="text-xs text-gray-400">${i.category_name}</div>
                                </td>
                                <td class="py-2 pr-3 text-center font-medium">${i.quantity}</td>
                                <td class="py-2 text-right">${i.cost ? i.cost : '—'}</td>
                            </tr>`).join('')}
                        </tbody>
                       </table>`
                    : `<p class="text-xs text-gray-400 italic py-2">${emptyMsg}</p>`;

                const mandatoryNote = mandatoryItems.length
                    ? (() => {
                        const cat = mandatoryItems[0]?.category_name || '';
                        return `<p class="text-xs text-gray-500 mt-1">${T.autoAddedQuotaNote.replace(':category', cat).replace(':n', mandatoryTotal)}</p>`;
                      })()
                    : '';

                $('#details-body').html(`
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 mb-4">
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">${T.marketerLabel}</span><p class="font-medium mt-0.5">${r.marketer}</p></div>
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">${T.vendor}</span><p class="font-medium mt-0.5">${r.vendor}</p></div>
                        ${r.campaign ? `<div><span class="text-gray-400 text-xs uppercase tracking-wide">${T.campaign}</span><p class="font-medium mt-0.5">${r.campaign}</p></div>` : ''}
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">${T.status}</span><p class="font-medium mt-0.5 capitalize">${r.status}</p></div>
                        <div><span class="text-gray-400 text-xs uppercase tracking-wide">${T.date}</span><p class="font-medium mt-0.5">${r.created_at}</p></div>
                    </div>
                    ${r.notes ? `<div class="mb-4"><span class="text-gray-400 text-xs uppercase tracking-wide">${T.notes}</span><p class="mt-0.5">${r.notes}</p></div>` : ''}
                    ${r.rejection_reason ? `<div class="mb-4 p-3 bg-red-50 rounded-lg"><span class="text-red-600 text-xs font-semibold uppercase tracking-wide">${T.rejectionReason}</span><p class="mt-0.5 text-red-700">${r.rejection_reason}</p></div>` : ''}

                    <div class="mb-4">
                        <span class="text-gray-400 text-xs uppercase tracking-wide block mb-2">
                            ${T.marketerRequestedSamples}
                            <span class="ml-1 font-semibold text-blue-700">${marketerTotal} ${T.units}</span>
                        </span>
                        ${itemTable(marketerItems, T.noMarketerRequestedItems)}
                    </div>

                    ${mandatoryItems.length ? `
                    <div class="mb-4 p-3 bg-orange-50 rounded-lg border border-orange-100">
                        <span class="text-orange-700 text-xs uppercase tracking-wide font-semibold block mb-1">
                            ${T.adminMandatedSamples}
                            <span class="ml-1">${mandatoryTotal} ${T.units}</span>
                        </span>
                        ${mandatoryNote}
                        <div class="mt-2">${itemTable(mandatoryItems, '')}</div>
                    </div>` : ''}

                    <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">${T.totalSentToVendor}</span>
                        <span class="text-sm font-bold text-gray-900">${vendorTotal} ${T.units}</span>
                    </div>`);
            })
            .fail(() => $('#details-body').html(`<p class="text-red-500 text-center py-4">${T.failedToLoadDetails}</p>`));
    });

    $('#details-close').on('click', () => $('#details-modal').css('display', 'none'));

    // ── Approve modal ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-approve-sample', function () {
        const id = $(this).data('id');
        $('#approve-sample-id').val(id);
        $('#approve-items-body').html(`<div class="flex items-center justify-center py-8 text-gray-400">${T.loading}</div>`);
        $('#approve-modal').css('display', 'flex');

        $.get('{{ route('admin.marketers.samples.show', ':id') }}'.replace(':id', id))
            .done(r => {
                // Group items by category
                const byCategory = {};
                (r.items || []).forEach(item => {
                    const cat = item.category_name || T.uncategorised;
                    if (!byCategory[cat]) byCategory[cat] = [];
                    byCategory[cat].push(item);
                });

                let html = '';
                Object.entries(byCategory).forEach(([cat, items]) => {
                    html += `<div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2 border-b border-gray-100 pb-1">${cat}</p>
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-gray-400 text-xs">
                                <th class="pb-2 pr-3 w-1/2">${T.product}</th>
                                <th class="pb-2 pr-3 text-center">${T.marketerQty}</th>
                                <th class="pb-2 text-center">${T.adminQtySecret}</th>
                            </tr></thead>
                            <tbody>`;
                    items.forEach(item => {
                        const mktQty = item.marketer_quantity || item.quantity || 0;
                        const admDefault = item.category_admin_quota || 0;
                        html += `<tr class="border-t border-gray-50">
                            <td class="py-2 pr-3">
                                ${item.product_name}
                                ${item.variant_name ? `<span class="text-gray-400 text-xs ml-1">(${item.variant_name})</span>` : ''}
                            </td>
                            <td class="py-2 pr-3 text-center">
                                <span class="approve-item-mkt-qty inline-block w-20 text-center font-semibold text-gray-700 bg-gray-100 rounded-lg py-1 text-sm"
                                    data-item-id="${item.id}" data-value="${mktQty}">${mktQty}</span>
                            </td>
                            <td class="py-2">
                                <input type="number" min="0" value="${admDefault}"
                                    class="form-input text-sm py-1 w-20 text-center mx-auto block approve-item-adm-qty border-orange-300 focus:border-orange-500"
                                    data-item-id="${item.id}">
                            </td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>`;
                });

                $('#approve-items-body').html(html || `<p class="text-gray-400 text-center py-4">${T.noItemsInRequest}</p>`);
            })
            .fail(() => $('#approve-items-body').html(`<p class="text-red-500 text-center py-4">${T.failedToLoadItems}</p>`));
    });

    $('#approve-modal-close, #approve-modal-cancel').on('click', () => $('#approve-modal').css('display', 'none'));

    $('#approve-confirm').on('click', function () {
        const id = $('#approve-sample-id').val();

        const items = [];
        const itemIds = new Set();
        $('#approve-items-body .approve-item-mkt-qty').each(function () {
            itemIds.add($(this).data('item-id'));
        });

        let valid = true;
        itemIds.forEach(itemId => {
            const mkt = parseInt($(`[data-item-id="${itemId}"].approve-item-mkt-qty`).data('value')) || 0;
            const adm = parseInt($(`[data-item-id="${itemId}"].approve-item-adm-qty`).val()) || 0;
            if (adm < 0) { valid = false; }
            items.push({ id: itemId, marketer_quantity: mkt, admin_quantity: adm });
        });

        if (!valid) { window.Toast.error(T.quantitiesNegativeError); return; }
        if (!items.length) { window.Toast.error(T.noItemsFound); return; }

        $(this).prop('disabled', true).text(T.approving);

        $.ajax({
            url: '{{ route('admin.marketers.samples.approve', ':id') }}'.replace(':id', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Accept': 'application/json' },
            contentType: 'application/json',
            data: JSON.stringify({ items }),
        })
        .done(r => {
            window.Toast.success(r.message);
            $('#approve-modal').css('display', 'none');
            dt.ajax.reload();
        })
        .fail(xhr => window.Toast.error(xhr.responseJSON?.message || T.errorGeneric))
        .always(() => $('#approve-confirm').prop('disabled', false).text(T.approveSetQuantities));
    });

    // ── Dispatch ───────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-dispatch-sample', async function () {
        const id = $(this).data('id');
        if (!await window.confirmDialog({ title: T.markDispatchedConfirm })) return;
        $.post('{{ route('admin.marketers.samples.dispatch', ':id') }}'.replace(':id', id), { _token: tok })
            .done(r => { window.Toast.success(r.message); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || T.errorGeneric));
    });

    // ── Reject modal ───────────────────────────────────────────────────────────
    $(document).on('click', '.btn-reject-sample', function () {
        const id = $(this).data('id');
        $('#reject-sample-id').val(id);
        $('#reject-reason').val('');
        $('#reject-modal').css('display', 'flex');
    });

    $('#reject-cancel').on('click', () => $('#reject-modal').css('display', 'none'));

    $('#reject-confirm').on('click', function () {
        const id = $('#reject-sample-id').val();
        const reason = $('#reject-reason').val().trim();
        if (!reason) { window.Toast.error(T.rejectionReasonRequired); return; }
        $.post('{{ route('admin.marketers.samples.reject', ':id') }}'.replace(':id', id), { _token: tok, rejection_reason: reason })
            .done(r => { window.Toast.success(r.message); $('#reject-modal').css('display', 'none'); dt.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || T.errorGeneric));
    });
});
</script>
@endpush
