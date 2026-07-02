@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.fbn_section.marketplace_rules'))

@section('content')


    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.fbn_section.marketplace_rules') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure special shipping requirements and commission overrides per
                marketplace listing.</p>
        </div>
        <button type="button" id="btn-create-rule" class="btn btn-primary btn-sm">+ {{ __('admin.add_new') }}</button>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-3 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Rules</p>
            <p class="text-xl font-extrabold text-gray-700">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-3 text-center">
            <p class="text-xs text-orange-400 uppercase tracking-wide mb-1">Special Vehicle</p>
            <p class="text-xl font-extrabold text-orange-600">{{ number_format($stats['special_vehicle']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-3 text-center">
            <p class="text-xs text-blue-400 uppercase tracking-wide mb-1">Refrigerated</p>
            <p class="text-xl font-extrabold text-blue-600">{{ number_format($stats['refrigerated']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-3 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Fixed Commission</p>
            <p class="text-xl font-extrabold text-gray-700">{{ number_format($stats['fixed_commission']) }}</p>
        </div>
    </div>

    {{-- ─── Filter ───────────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="label-sm">Commission Type</label>
                <select id="filter-commission-type" class="form-select text-sm py-1.5 pr-8">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                    <option value="mixed">Mixed</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <table id="tbl-rules" class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('admin.id') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.fbn_section.vendor') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.fbn_section.products_count') }}</th>
                    <th class="px-4 py-3 text-start">Requirements</th>
                    <th class="px-4 py-3 text-start">Max Weight</th>
                    <th class="px-4 py-3 text-start">Commission</th>
                    <th class="px-4 py-3 text-start">Extra Fee</th>
                    <th class="px-4 py-3 text-start">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- ─── Create / Edit Modal ─────────────────────────────────────────────────── --}}
    <div id="rule-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-xl">
            <h3 class="font-bold text-lg mb-5" id="rule-modal-title">New Marketplace Rule</h3>
            <input type="hidden" id="rm-id">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="label-sm">Vendor Listing ID <span class="text-red-500">*</span></label>
                    <input type="text" id="rm-listing-id" class="form-input w-full text-sm font-mono"
                        placeholder="UUID of the vendor_listing">
                    <p id="rm-listing-note" class="text-xs text-gray-400 mt-1">Listing must have global_system_type =
                        marketplace.</p>
                </div>

                <div>
                    <label class="label-sm">Commission Type <span class="text-red-500">*</span></label>
                    <select id="rm-commission-type" class="form-select w-full text-sm">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed (currency units)</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <div>
                    <label class="label-sm">Commission Value <span class="text-red-500">*</span></label>
                    <input type="number" id="rm-commission-value" class="form-input w-full text-sm" min="0" step="0.01"
                        placeholder="e.g. 15 or 50">
                </div>

                <div>
                    <label class="label-sm">Extra Delivery Fee (cents)</label>
                    <input type="number" id="rm-extra-fee" class="form-input w-full text-sm" min="0" value="0"
                        placeholder="e.g. 500 = 5.00">
                </div>
                <div>
                    <label class="label-sm">Max Weight (kg)</label>
                    <input type="number" id="rm-weight" class="form-input w-full text-sm" min="0" step="0.01"
                        placeholder="e.g. 120">
                </div>

                <div class="col-span-2">
                    <label class="label-sm">Max Dimensions (LxWxH cm)</label>
                    <input type="text" id="rm-dimensions" class="form-input w-full text-sm" placeholder="e.g. 200x100x80">
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="rm-special-vehicle" class="w-4 h-4">
                    <label for="rm-special-vehicle" class="text-sm font-medium text-gray-700">Requires Special
                        Vehicle</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="rm-refrigeration" class="w-4 h-4">
                    <label for="rm-refrigeration" class="text-sm font-medium text-gray-700">Requires Refrigeration</label>
                </div>

                <div class="col-span-2">
                    <label class="label-sm">Special Handling Notes</label>
                    <textarea id="rm-notes" rows="2" class="form-input w-full text-sm"
                        placeholder="e.g. Must use refrigerated truck. Handle with care."></textarea>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-5 pt-4 border-t">
                <button type="button" id="rule-modal-cancel" class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
                <button type="button" id="rule-modal-save" class="btn btn-primary btn-sm px-8">{{ __('admin.save') }}</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            const tok = '{{ csrf_token() }}';

            const tbl = $('#tbl-rules').DataTable({
                serverSide: true,
                processing: true,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route('admin.fbn.marketplace.datatable') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok },
                    data: d => { d.commission_type = $('#filter-commission-type').val(); },
                },
                columns: [
                    { data: 'listing_id', orderable: false },
                    { data: 'vendor', orderable: false },
                    { data: 'product', orderable: false },
                    { data: 'flags', orderable: false },
                    { data: 'weight', orderable: false },
                    { data: 'commission', orderable: false },
                    { data: 'extra_fee', orderable: false },
                    { data: 'actions', orderable: false },
                ],
                language: { processing: 'Loading…' },
            });

            $('#filter-commission-type').on('change', () => tbl.ajax.reload());

            function clearModal() {
                $('#rm-id').val('');
                $('#rm-listing-id').val('').prop('disabled', false);
                $('#rm-listing-note').show();
                $('#rm-commission-type').val('percentage');
                $('#rm-commission-value').val('');
                $('#rm-extra-fee').val('0');
                $('#rm-weight').val('');
                $('#rm-dimensions').val('');
                $('#rm-special-vehicle').prop('checked', false);
                $('#rm-refrigeration').prop('checked', false);
                $('#rm-notes').val('');
            }

            // ── Create ──────────────────────────────────────────────────────────────────
            $('#btn-create-rule').on('click', () => {
                clearModal();
                $('#rule-modal-title').text('New Marketplace Rule');
                $('#rule-modal').show();
            });

            // ── Edit ────────────────────────────────────────────────────────────────────
            $(document).on('click', '.btn-edit-rule', function () {
                const r = $(this).data('rule');
                $('#rm-id').val(r.id);
                $('#rule-modal-title').text('Edit Marketplace Rule');
                $('#rm-listing-id').val(r.vendor_listing_id).prop('disabled', true);
                $('#rm-listing-note').hide();
                $('#rm-commission-type').val(r.commission_type);
                $('#rm-commission-value').val(r.commission_value);
                $('#rm-extra-fee').val(r.extra_delivery_fee_cents ?? 0);
                $('#rm-weight').val(r.max_weight_kg ?? '');
                $('#rm-dimensions').val(r.max_dimensions_cm ?? '');
                $('#rm-special-vehicle').prop('checked', !!r.requires_special_vehicle);
                $('#rm-refrigeration').prop('checked', !!r.requires_refrigeration);
                $('#rm-notes').val(r.special_handling_notes ?? '');
                $('#rule-modal').show();
            });

            $('#rule-modal-cancel').on('click', () => $('#rule-modal').hide());

            // ── Save ────────────────────────────────────────────────────────────────────
            $('#rule-modal-save').on('click', function () {
                const id = $('#rm-id').val();
                const payload = {
                    vendor_listing_id: $('#rm-listing-id').val(),
                    commission_type: $('#rm-commission-type').val(),
                    commission_value: parseFloat($('#rm-commission-value').val()) || 0,
                    extra_delivery_fee_cents: parseInt($('#rm-extra-fee').val()) || 0,
                    max_weight_kg: parseFloat($('#rm-weight').val()) || null,
                    max_dimensions_cm: $('#rm-dimensions').val() || null,
                    requires_special_vehicle: $('#rm-special-vehicle').is(':checked') ? 1 : 0,
                    requires_refrigeration: $('#rm-refrigeration').is(':checked') ? 1 : 0,
                    special_handling_notes: $('#rm-notes').val() || null,
                };

                const url = id ? `/admin/fbn/marketplace/${id}` : '{{ route('admin.fbn.marketplace.store') }}';
                const method = id ? 'PUT' : 'POST';

                fetch(url, {
                    method,
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); $('#rule-modal').hide(); tbl.ajax.reload(); }
                    else { window.Toast.error(data.message ?? 'Error'); }
                });
            });

            // ── Delete ──────────────────────────────────────────────────────────────────
            $(document).on('click', '.btn-delete-rule', function () {
                const id = $(this).data('id');
                window.confirmDelete({
                    title: 'Delete rule?',
                    text: 'This will remove the special shipping configuration for this listing.',
                    onConfirm: () => {
                        fetch(`/admin/fbn/marketplace/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                            body: '{}',
                        }).then(r => r.json()).then(data => {
                            if (data.success) { window.Toast.success(data.message); tbl.ajax.reload(); }
                            else { window.Toast.error(data.message); }
                        });
                    }
                });
            });
        });
    </script>
@endpush
