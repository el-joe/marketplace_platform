@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.products.title'))

@section('content')
    @php
        $columns = [
            [
                'title' => '',
                'data' => 'image',
                'name' => 'image',
                'orderable' => false,
                'searchable' => false,
                'className' => 'w-12 px-2',
                'render' => 'Renderers.image()'
            ],
            ['title' => __('admin.products.product_name'), 'data' => 'name_en', 'name' => 'name_en'],
            ['title' => __('admin.category'), 'data' => 'category', 'name' => 'category', 'searchable' => false],
            ['title' => __('admin.brand'), 'data' => 'brand', 'name' => 'brand', 'searchable' => false],
            [
                'title' => __('admin.status'),
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                                                                draft:        { label: "Draft",        color: "gray"    },
                                                                active:       { label: "Active",       color: "success" },
                                                                discontinued: { label: "Discontinued", color: "warning" },
                                                                restricted:   { label: "Restricted",   color: "danger"  }
                                                            })'
            ],
            ['title' => __('admin.sellers'), 'data' => 'seller_count', 'name' => 'seller_count', 'searchable' => false, 'className' => 'text-end'],
            ['title' => __('admin.rating'), 'data' => 'rating_avg', 'name' => 'rating_avg', 'searchable' => false, 'className' => 'text-end'],
            ['title' => __('admin.sold'), 'data' => 'total_sold', 'name' => 'total_sold', 'searchable' => false, 'className' => 'text-end'],
            [
                'title' => __('admin.created_at'),
                'data' => 'created_at',
                'name' => 'created_at',
                'searchable' => false,
                'render' => 'Renderers.dateAgo'
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'Renderers.actions([
                                                                    { type: "link",   label: "Edit",   url: ":edit_url" },
                                                                    { type: "button", label: "Delete", id: "delete", class: "btn-danger" }
                                                                ])'
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => __('admin.search'), 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => __('admin.status'),
                'options' => ['draft' => 'Draft', 'active' => 'Active', 'discontinued' => 'Discontinued', 'restricted' => 'Restricted']
            ],
            [
                'type' => 'async_select',
                'name' => 'category_id',
                'label' => __('admin.category'),
                'url' => route('admin.categories.search'),
                'param' => 'q',
                'placeholder' => 'All categories'
            ],
            [
                'type' => 'async_select',
                'name' => 'brand_id',
                'label' => __('admin.brand'),
                'url' => route('admin.brands.search'),
                'param' => 'q',
                'placeholder' => 'All brands'
            ],
            ['type' => 'date_range', 'name' => 'date', 'label' => __('admin.created_at')],
        ];

        $bulkActions = [
            ['id' => 'publish', 'label' => 'Publish', 'class' => 'btn-success', 'confirmMessage' => 'Publish selected products?'],
            ['id' => 'archive', 'label' => 'Archive', 'class' => 'btn-ghost', 'confirmMessage' => 'Archive selected products?'],
            ['id' => 'feature', 'label' => __('admin.products.featured'), 'class' => 'btn-secondary', 'confirmMessage' => 'Mark as featured?'],
            ['id' => 'delete', 'label' => __('admin.delete'), 'class' => 'btn-danger', 'confirmMessage' => 'Delete selected products? This cannot be undone.'],
        ];
    @endphp
    <x-table.datatable id="products-table" url="{{ route('admin.products.datatable') }}" :columns="$columns"
        :filters="$filters" :bulk-actions="$bulkActions"
        :create-action="['url' => route('admin.products.create'), 'label' => __('admin.products.add_product')]" :selectable="true"
        :page-length="25" :order="[[8, 'desc']]" />
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.delete = async function (id, row) {
            const message = 'Delete "' + (row.name_en || 'this product') + '"?';
            const confirmed = window.confirmDelete
                ? await window.confirmDelete(message, { title: 'Delete product?' })
                : confirm(message);
            if (!confirmed) return;

            $.ajax({ url: row.delete_url, method: 'DELETE' })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message || 'Product deleted.');
                    window.reloadDataTable('products-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
                });
        };

        ['publish', 'archive', 'feature'].forEach(function (action) {
            window.tableActions[action] = function (ids, tableId) {
                window.bulkPost(
                    '{{ route('admin.products.bulk') }}',
                    { action: action, ids: ids },
                    tableId,
                    ids.length + ' product(s) updated.'
                );
            };
        });

        window.tableActions.delete_bulk = function (ids, tableId) {
            window.bulkPost('{{ route('admin.products.bulk') }}', { action: 'delete', ids: ids }, tableId);
        };
    </script>
@endpush
