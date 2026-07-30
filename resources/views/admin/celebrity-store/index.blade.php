@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/components/select2.js'])
@endpush

@section('title', 'Celebrity Open Market')

@section('content')
    @php
        $columns = [
            ['title' => 'Celebrity', 'data' => 'celebrity_name', 'name' => 'celebrity_name', 'searchable' => false, 'orderable' => false],
            ['title' => 'Product', 'data' => 'product_name', 'name' => 'product_name', 'searchable' => false, 'orderable' => false],
            ['title' => 'Commission Amount', 'data' => 'promoter_commission_amount', 'name' => 'promoter_commission_amount', 'searchable' => false],
            ['title' => 'Currency', 'data' => 'currency_code', 'name' => 'currency_code', 'searchable' => false],
            ['title' => 'Admin % Cut', 'data' => 'admin_commission_pct_bps', 'name' => 'admin_commission_pct_bps', 'searchable' => false],
            [
                'title' => 'Status',
                'data' => 'is_active',
                'name' => 'is_active',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data;
                    return data ? "✅ Active" : "⛔ Inactive";
                }',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'Renderers.actions([
                    { type: "button", label: "Approve", id: "toggle" },
                    { type: "button", label: "Set Commission", id: "editCommission" },
                    { type: "button", label: "Remove", id: "remove", class: "btn-danger" }
                ])',
            ],
        ];

        $filters = [
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Status',
                'options' => [1 => 'Active', 0 => 'Inactive'],
                'placeholder' => 'All',
            ],
        ];
    @endphp

    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Celebrity Open Market</h1>
                <p class="text-sm text-gray-500 mt-0.5">Tier 2 celebrity store products, promoter commission, and admin cut management.</p>
            </div>
            <button type="button" data-modal-open="add-celebrity-store-product-modal" class="btn btn-primary gap-2 text-sm">
                <x-heroicon name="plus" class="w-4 h-4" />
                Approve Product
            </button>
        </div>

        <x-table.datatable id="celebrity-store-table" url="{{ route('admin.celebrity-store.datatable') }}"
            :columns="$columns" :filters="$filters" :selectable="false" :page-length="25" />
    </div>

    {{-- Approve Product modal --}}
    <x-modal id="add-celebrity-store-product-modal" title="Approve Celebrity Store Product" size="md">
        <div class="space-y-4">
            <div>
                <label class="form-label">Celebrity</label>
                <select id="cs-add-celebrity" data-async-select
                    data-config='{{ json_encode(["url" => route("admin.celebrity-store.search-celebrities"), "param" => "q", "minLength" => 0, "delay" => 300]) }}'
                    class="form-select w-full">
                    <option value=""></option>
                </select>
            </div>
            <div>
                <label class="form-label">Product</label>
                <select id="cs-add-listing" data-async-select
                    data-config='{{ json_encode(["url" => route("admin.celebrity-store.search-listings"), "param" => "q", "minLength" => 0, "delay" => 300]) }}'
                    class="form-select w-full">
                    <option value=""></option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Promoter Commission Amount</label>
                    <input type="number" id="cs-add-commission-amount" min="0" step="1" class="form-input w-full" value="0">
                </div>
                <div>
                    <label class="form-label">Currency</label>
                    <select id="cs-add-currency" class="form-select w-full">
                        @foreach(['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'] as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Admin % Cut (bps, 0-10000)</label>
                <input type="number" id="cs-add-bps" min="0" max="10000" step="1" class="form-input w-full" value="0">
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-add-celebrity-store-product" class="btn btn-primary">Approve</button>
        </x-slot:footer>
    </x-modal>

    {{-- Set Commission modal --}}
    <x-modal id="edit-cs-commission-modal" title="Set Commission" size="sm">
        <div class="space-y-4">
            <input type="hidden" id="cs-edit-commission-url">
            <div>
                <label class="form-label">Promoter Commission Amount</label>
                <input type="number" id="cs-edit-commission-amount" min="0" step="1" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Admin % Cut (bps, 0-10000)</label>
                <input type="number" id="cs-edit-bps" min="0" max="10000" step="1" class="form-input w-full">
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-edit-cs-commission" class="btn btn-primary">Save</button>
        </x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.toggle = function (id, row) {
            $.ajax({ url: row.toggle_url, method: 'POST' })
                .done(function () {
                    window.Toast && window.Toast.success('Approved.');
                    window.reloadDataTable('celebrity-store-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Failed to approve.');
                });
        };

        window.tableActions.remove = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete('Remove this product from the celebrity open market?', { title: 'Remove Product' })
                : confirm('Remove this product from the celebrity open market?');
            if (!confirmed) return;

            $.ajax({ url: row.delete_url, method: 'DELETE' })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message || 'Removed.');
                    window.reloadDataTable('celebrity-store-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Failed to remove.');
                });
        };

        window.tableActions.editCommission = function (id, row) {
            $('#cs-edit-commission-url').val(row.update_commission_url);
            $('#cs-edit-commission-amount').val(row.current_commission_amount);
            $('#cs-edit-bps').val(row.current_commission_bps);
            $('#edit-cs-commission-modal').modal('open');
        };

        document.getElementById('btn-confirm-edit-cs-commission')?.addEventListener('click', function () {
            const btn = this;
            const url = $('#cs-edit-commission-url').val();
            btn.disabled = true;
            $.ajax({
                url: url,
                method: 'PUT',
                data: {
                    promoter_commission_amount: $('#cs-edit-commission-amount').val(),
                    admin_commission_pct_bps: $('#cs-edit-bps').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
            })
                .done(function (res) {
                    btn.disabled = false;
                    $('#edit-cs-commission-modal').modal('close');
                    window.Toast && window.Toast.success(res.message || 'Commission updated.');
                    window.reloadDataTable('celebrity-store-table');
                })
                .fail(function (xhr) {
                    btn.disabled = false;
                    const msg = xhr.responseJSON?.message
                        || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                        || 'Failed to update commission.';
                    window.Toast && window.Toast.error(msg);
                });
        });

        document.getElementById('btn-confirm-add-celebrity-store-product')?.addEventListener('click', function () {
            const btn = this;
            const celebrityId = $('#cs-add-celebrity').val();
            const selectedListing = $('#cs-add-listing').val();
            if (!celebrityId || !selectedListing) {
                window.Toast && window.Toast.error('Please select a celebrity and a product.');
                return;
            }
            const [listingType, listingId] = selectedListing.split(':');

            btn.disabled = true;
            $.ajax({
                url: '{{ route('admin.celebrity-store.products.approve') }}',
                method: 'POST',
                data: {
                    celebrity_marketer_id: celebrityId,
                    listing_type: listingType,
                    listing_id: listingId,
                    promoter_commission_amount: $('#cs-add-commission-amount').val() || 0,
                    currency_code: $('#cs-add-currency').val(),
                    admin_commission_pct_bps: $('#cs-add-bps').val() || 0,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
            })
                .done(function (res) {
                    btn.disabled = false;
                    $('#add-celebrity-store-product-modal').modal('close');
                    window.Toast && window.Toast.success(res.message || 'Product approved.');
                    window.reloadDataTable('celebrity-store-table');
                })
                .fail(function (xhr) {
                    btn.disabled = false;
                    const msg = xhr.responseJSON?.message
                        || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                        || 'Failed to approve product.';
                    window.Toast && window.Toast.error(msg);
                });
        });
    </script>
@endpush
