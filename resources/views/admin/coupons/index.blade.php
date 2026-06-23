@extends('layouts.admin')

@section('title', 'Coupons')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/coupons.js'])
@endpush

@section('content')
    @php
        $columns = [
            [
                'title' => 'Code',
                'data' => 'code',
                'name' => 'code',
                'render' => 'function(data){ return "<code class=\"font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded\">" + data + "</code>"; }',
            ],
            ['title' => 'Name', 'data' => 'name', 'name' => 'name'],
            [
                'title' => 'Type',
                'data' => 'type',
                'name' => 'type',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            percentage:   { label: "Percentage",   color: "blue"   },
                            fixed_amount: { label: "Fixed Amount", color: "purple" },
                            free_shipping:{ label: "Free Shipping",color: "teal"   },
                            bogo:         { label: "BOGO",         color: "orange" }
                        })',
            ],
            [
                'title' => 'Value',
                'data' => 'value',
                'name' => 'value',
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'function(data, type, row){ return row.type === "percentage" ? data + "%" : (row.type === "free_shipping" ? "—" : data); }',
            ],
            [
                'title' => 'Scope',
                'data' => 'scope',
                'name' => 'scope',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            platform: { label: "Platform", color: "gray"   },
                            vendor:   { label: "Vendor",   color: "indigo" },
                            category: { label: "Category", color: "yellow" },
                            product:  { label: "Product",  color: "pink"   }
                        })',
            ],
            [
                'title' => 'Used',
                'data' => 'times_used',
                'name' => 'times_used',
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'function(data, type, row){ var limit = row.usage_limit_total ? row.usage_limit_total : "∞"; return data + " / " + limit; }',
            ],
            [
                'title' => 'Active',
                'data' => 'is_active',
                'name' => 'is_active',
                'searchable' => false,
                'render' => 'Renderers.badge({ true: { label: "Active", color: "success" }, false: { label: "Inactive", color: "gray" } })',
            ],
            [
                'title' => 'Valid Until',
                'data' => 'valid_until',
                'name' => 'valid_until',
                'searchable' => false,
                'render' => 'Renderers.date',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                            { type: "link",   label: "Edit",   url: ":edit_url" },
                            { type: "button", label: "Delete", id: "delete", class: "btn-danger" }
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Code / Name', 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Status',
                'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive'],
            ],
            [
                'type' => 'select',
                'name' => 'type',
                'label' => 'Type',
                'options' => ['' => 'All', 'percentage' => 'Percentage', 'fixed_amount' => 'Fixed Amount', 'free_shipping' => 'Free Shipping', 'bogo' => 'BOGO'],
            ],
            [
                'type' => 'select',
                'name' => 'scope',
                'label' => 'Scope',
                'options' => ['' => 'All', 'platform' => 'Platform', 'vendor' => 'Vendor', 'category' => 'Category', 'product' => 'Product'],
            ],
            [
                'type' => 'select',
                'name' => 'expired',
                'label' => 'Expiry',
                'options' => ['' => 'All', '0' => 'Valid', '1' => 'Expired'],
            ],
        ];

        $bulkActions = [
            ['value' => 'activate', 'label' => 'Activate'],
            ['value' => 'deactivate', 'label' => 'Deactivate'],
            ['value' => 'delete', 'label' => 'Delete', 'class' => 'text-red-600'],
        ];
    @endphp

    <x-table.datatable id="coupons-table" url="{{ route('admin.coupons.datatable') }}" :columns="$columns"
        :filters="$filters" :bulk-actions="$bulkActions" bulk-url="{{ route('admin.coupons.bulk') }}"
        :create-action="['url' => route('admin.coupons.create'), 'label' => 'Add Coupon']" :page-length="25" :order="[[7, 'desc']]" />
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.delete = async function (id, row) {
            const message = 'Delete coupon "' + (row.code || 'this coupon') + '"?';
            const confirmed = window.confirmDelete
                ? await window.confirmDelete(message, { title: 'Delete coupon?' })
                : confirm(message);
            if (!confirmed) return;

            $.ajax({
                url: row.delete_url,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(function () {
                    window.Toast && window.Toast.success('Coupon deleted.');
                    window.reloadDataTable('coupons-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
                });
        };
    </script>
@endpush