@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <x-datatable id="products-table" url="{{ route('admin.products.datatable') }}" :columns="[
            ['title' => 'Product', 'data' => 'name', 'name' => 'name'],
            ['title' => 'SKU', 'data' => 'sku', 'name' => 'sku', 'className' => 'font-mono text-xs'],
            ['title' => 'Category', 'data' => 'category_name', 'name' => 'category'],
            ['title' => 'Vendor', 'data' => 'vendor_name', 'name' => 'vendor'],
            [
                'title' => 'Price',
                'data' => 'price',
                'name' => 'price',
                'className' => 'text-right',
                'render' => 'Renderers.currency(\'EGP\')'
            ],
            ['title' => 'Stock', 'data' => 'stock_quantity', 'name' => 'stock', 'className' => 'text-right'],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'render' => 'Renderers.badge({ active: { label: \'Active\', color: \'success\' }, inactive: { label: \'Inactive\', color: \'gray\' }, draft: { label: \'Draft\', color: \'warning\' } })'
            ],
            [
                'title' => 'Created',
                'data' => 'created_at',
                'name' => 'created_at',
                'render' => 'Renderers.dateAgo'
            ],
            [
                'title' => '',
                'data' => 'id',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                 { type: \'link\',   label: \'Edit\',   url: \'/admin/products/:id/edit\', icon: \'<svg class=\\\'w-4 h-4\\\' fill=\\\'none\\\' stroke=\\\'currentColor\\\' viewBox=\\\'0 0 24 24\\\'><path stroke-linecap=\\\'round\\\' stroke-linejoin=\\\'round\\\' stroke-width=\\\'2\\\' d=\\\'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125\\\'/></svg>\' },
                 { type: \'button\', label: \'Delete\', id: \'delete\',  class: \'btn-danger\', icon: \'<svg class=\\\'w-4 h-4\\\' fill=\\\'none\\\' stroke=\\\'currentColor\\\' viewBox=\\\'0 0 24 24\\\'><path stroke-linecap=\\\'round\\\' stroke-linejoin=\\\'round\\\' stroke-width=\\\'2\\\' d=\\\'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0\\\'/></svg>\' },
             ])'
            ],
        ]" :filters="[
            ['type' => 'text', 'name' => 'search', 'label' => 'Name / SKU', 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft']
            ],
            [
                'type' => 'async_select',
                'name' => 'category_id',
                'label' => 'Category',
                'url' => '/admin/categories/search',
                'param' => 'q',
                'placeholder' => 'All categories'
            ],
            [
                'type' => 'async_select',
                'name' => 'vendor_id',
                'label' => 'Vendor',
                'url' => '/admin/vendors/search',
                'param' => 'q',
                'placeholder' => 'All vendors'
            ],
            ['type' => 'date_range', 'name' => 'date', 'label' => 'Created'],
            [
                'type' => 'number_range',
                'name' => 'price',
                'label' => 'Price (EGP)',
                'min_placeholder' => 'Min price',
                'max_placeholder' => 'Max price'
            ],
        ]" :bulk-actions="[
            ['id' => 'activate',   'label' => 'Activate',   'class' => 'btn-success',
             'confirmMessage' => 'Activate selected products?'],
            ['id' => 'deactivate', 'label' => 'Deactivate', 'class' => 'btn-ghost',
             'confirmMessage' => 'Deactivate selected products?'],
            ['id' => 'bulk-delete', 'label' => 'Delete',    'class' => 'btn-danger',
             'confirmMessage' => 'Permanently delete selected products? This cannot be undone.'],
        ]" :create-action="['url' => route('admin.products.create'), 'label' => 'Add Product']" :selectable="true"
        :page-length="25" :order="[[7, 'desc']]" />
@endsection

@push('scripts')
    <script>
        /**
         * Row-level action handlers for the products table.
         * Registered on window.tableActions so the global data-action handler
         * (defined in datatable.js) can delegate to them.
         */
        window.tableActions = window.tableActions || {};

        window.tableActions.delete = function (id, row) {
            if (!confirm('Delete "' + (row.name || 'this product') + '"? This cannot be undone.')) return;

            $.ajax({
                url: '/admin/products/' + id,
                method: 'DELETE',
            })
                .done(function (res) {
                    Toast.success(res.message || 'Product deleted.');
                    window.reloadDataTable('products-table');
                })
                .fail(function (xhr) {
                    Toast.error(xhr.responseJSON?.message || 'Delete failed.');
                });
        };

        /**
         * Bulk action handlers.
         */
        window.tableActions.activate = function (ids, tableId) {
            window.bulkPost('/admin/products/bulk', { action: 'activate', ids: ids }, tableId, ids.length + ' product(s) activated.');
        };

        window.tableActions.deactivate = function (ids, tableId) {
            window.bulkPost('/admin/products/bulk', { action: 'deactivate', ids: ids }, tableId, ids.length + ' product(s) deactivated.');
        };

        window.tableActions['bulk-delete'] = function (ids, tableId) {
            window.bulkPost('/admin/products/bulk', { action: 'delete', ids: ids }, tableId, ids.length + ' product(s) deleted.');
        };

        // Override the confirm modal for bulk delete to use a nicer message
        window.bulkConfirmModal = function (message, count, callback) {
            if (confirm(message + '\n\n' + count + ' item(s) will be affected.')) {
                callback();
            }
        };
    </script>
@endpush