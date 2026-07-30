@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/components/select2.js'])
@endpush

@section('title', 'Influencer Open Market')

@section('content')
    @php
        $columns = [
            ['title' => 'Product', 'data' => 'product_name', 'name' => 'product_name', 'searchable' => false, 'orderable' => false],
            ['title' => 'Listing ID', 'data' => 'listing_id', 'name' => 'listing_id', 'searchable' => false, 'orderable' => false],
            [
                'title' => 'Current Tier',
                'data' => 'promotion_tier',
                'name' => 'promotion_tier',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    "Tier 3 (Admin Curated)": { label: "Tier 3 (Admin Curated)", color: "info" },
                    "Tier 4 (Nawy Now)": { label: "Tier 4 (Nawy Now)", color: "success" }
                })',
            ],
            ['title' => 'Commission', 'data' => 'celebrity_commission_amount', 'name' => 'celebrity_commission_amount', 'searchable' => false],
            [
                'title' => 'Active',
                'data' => 'is_active',
                'name' => 'is_active',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data;
                    return data ? "✅" : "⛔";
                }',
            ],
            ['title' => 'Added At', 'data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'Renderers.actions([
                    { type: "button", label: "Set Commission", id: "editCommission" },
                    { type: "button", label: "Toggle Active", id: "toggle" },
                    { type: "button", label: "Remove", id: "remove", class: "btn-danger" }
                ])',
            ],
        ];

        $filters = [
            [
                'type' => 'select',
                'name' => 'promotion_tier',
                'label' => 'Tier',
                'options' => [3 => 'Tier 3 (Admin Curated)', 4 => 'Tier 4 (Nawy Now)'],
                'placeholder' => 'All',
            ],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Active',
                'options' => [1 => 'Active', 0 => 'Inactive'],
                'placeholder' => 'All',
            ],
        ];

        $bulkActions = [
            ['id' => 'bulk-assign-tier-3', 'label' => 'Assign to Tier 3', 'class' => 'btn-secondary', 'confirmMessage' => 'Assign the selected products to Tier 3 (Admin Curated)?'],
            ['id' => 'bulk-assign-tier-4', 'label' => 'Assign to Tier 4', 'class' => 'btn-success', 'confirmMessage' => 'Assign the selected products to Tier 4 (Nawy Now)? Only Nawy-featured admin listings are eligible.'],
        ];
    @endphp

    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Influencer Open Market</h1>
                <p class="text-sm text-gray-500 mt-0.5">Products made available for influencers to promote (Category 3: Admin Intermediary, Category 4: Nawy Now).</p>
            </div>
            <button type="button" data-modal-open="add-open-market-product-modal" class="btn btn-primary gap-2 text-sm">
                <x-heroicon name="plus" class="w-4 h-4" />
                Add Product
            </button>
        </div>

        <x-table.datatable id="open-market-table" url="{{ route('admin.open-market.datatable') }}"
            :columns="$columns" :filters="$filters" :bulk-actions="$bulkActions" :selectable="true" :page-length="25" />
    </div>

    {{-- Add Product modal --}}
    <x-modal id="add-open-market-product-modal" title="Add Product to Open Market" size="md">
        <div class="space-y-4">
            <div>
                <label class="form-label">Category</label>
                <select id="om-add-category" class="form-select w-full">
                    <option value="3">Category 3 — Admin Intermediary</option>
                    <option value="4">Category 4 — Nawy Now</option>
                </select>
            </div>
            <div>
                <label class="form-label">Product</label>
                <select id="om-add-listing" data-async-select
                    data-config='{{ json_encode(["url" => route("admin.open-market.search-listings"), "param" => "q", "minLength" => 0, "delay" => 300, "open_market_category" => 3]) }}'
                    class="form-select w-full">
                    <option value=""></option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Search across vendor listings and Nawy Now admin listings. Category 4 only shows listings featured in Nawy.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Celebrity Commission Amount</label>
                    <input type="number" id="om-add-commission-amount" min="0" step="1" class="form-input w-full" value="0">
                </div>
                <div>
                    <label class="form-label">Currency</label>
                    <select id="om-add-commission-currency" class="form-select w-full">
                        @foreach(['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'] as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-add-open-market-product" class="btn btn-primary">Add Product</button>
        </x-slot:footer>
    </x-modal>

    {{-- Set Commission modal --}}
    <x-modal id="edit-commission-modal" title="Set Celebrity Commission" size="sm">
        <div class="space-y-4">
            <input type="hidden" id="om-edit-commission-url">
            <div>
                <label class="form-label">Celebrity Commission Amount</label>
                <input type="number" id="om-edit-commission-amount" min="0" step="1" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Currency</label>
                <select id="om-edit-commission-currency" class="form-select w-full">
                    @foreach(['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'] as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-edit-commission" class="btn btn-primary">Save</button>
        </x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.toggle = function (id, row) {
            $.ajax({ url: row.toggle_url, method: 'POST' })
                .done(function (res) {
                    window.Toast && window.Toast.success('Status updated.');
                    window.reloadDataTable('open-market-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Failed to update.');
                });
        };

        window.tableActions.remove = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete('Remove this product from the influencer open market?', { title: 'Remove Product' })
                : confirm('Remove this product from the influencer open market?');
            if (!confirmed) return;

            $.ajax({ url: row.delete_url, method: 'DELETE' })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message || 'Removed.');
                    window.reloadDataTable('open-market-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Failed to remove.');
                });
        };

        window.tableActions.editCommission = function (id, row) {
            $('#om-edit-commission-url').val(row.update_commission_url);
            $('#om-edit-commission-amount').val(row.current_commission_amount);
            $('#om-edit-commission-currency').val(row.current_commission_currency);
            $('#edit-commission-modal').modal('open');
        };

        document.getElementById('btn-confirm-edit-commission')?.addEventListener('click', function () {
            const btn = this;
            const url = $('#om-edit-commission-url').val();
            btn.disabled = true;
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    celebrity_commission_amount: $('#om-edit-commission-amount').val(),
                    commission_currency: $('#om-edit-commission-currency').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
            })
                .done(function (res) {
                    btn.disabled = false;
                    $('#edit-commission-modal').modal('close');
                    window.Toast && window.Toast.success(res.message || 'Commission updated.');
                    window.reloadDataTable('open-market-table');
                })
                .fail(function (xhr) {
                    btn.disabled = false;
                    const msg = xhr.responseJSON?.message
                        || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                        || 'Failed to update commission.';
                    window.Toast && window.Toast.error(msg);
                });
        });

        function bulkAssignTier(tier) {
            return function (ids) {
                $.ajax({
                    url: '{{ route('admin.open-market.bulk-assign-tier') }}',
                    method: 'POST',
                    data: {
                        ids: ids,
                        promotion_tier: tier,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                })
                    .done(function (res) {
                        window.Toast && window.Toast.success(res.message || 'Products reassigned.');
                        window.reloadDataTable('open-market-table');
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message
                            || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                            || 'Failed to reassign products.';
                        window.Toast && window.Toast.error(msg);
                    });
            };
        }

        window.tableActions['bulk-assign-tier-3'] = bulkAssignTier(3);
        window.tableActions['bulk-assign-tier-4'] = bulkAssignTier(4);

        $(document).on('change', '#om-add-category', function () {
            const category = $(this).val();
            const $listing = $('#om-add-listing');
            const cfg = JSON.parse($listing.attr('data-config') || '{}');
            $listing.attr('data-config', JSON.stringify({ ...cfg, open_market_category: category }));
            $listing.val(null).trigger('change');
        });

        $(document).on('click', '[data-modal-open="add-open-market-product-modal"]', function () {
            $('#om-add-category').val('3');
            $('#om-add-listing').val(null).trigger('change');
        });

        document.getElementById('btn-confirm-add-open-market-product')?.addEventListener('click', function () {
            const btn = this;
            const selected = $('#om-add-listing').val();
            if (!selected) {
                window.Toast && window.Toast.error('Please select a product.');
                return;
            }
            const [listingType, listingId] = selected.split(':');

            btn.disabled = true;
            $.ajax({
                url: '{{ route('admin.open-market.store') }}',
                method: 'POST',
                data: {
                    listing_type: listingType,
                    listing_id: listingId,
                    open_market_category: $('#om-add-category').val(),
                    celebrity_commission_amount: $('#om-add-commission-amount').val() || 0,
                    commission_currency: $('#om-add-commission-currency').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
            })
                .done(function (res) {
                    btn.disabled = false;
                    $('#add-open-market-product-modal').modal('close');
                    window.Toast && window.Toast.success(res.message || 'Product added.');
                    window.reloadDataTable('open-market-table');
                })
                .fail(function (xhr) {
                    btn.disabled = false;
                    const msg = xhr.responseJSON?.message
                        || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                        || 'Failed to add product.';
                    window.Toast && window.Toast.error(msg);
                });
        });
    </script>
@endpush
