@extends('layouts.admin')

@section('title', 'Brands')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/brands.js'])
@endpush

@section('content')
    @php
        $columns = [
            ['title' => 'Name (EN)', 'data' => 'name_en', 'name' => 'name_en'],
            ['title' => 'Name (AR)', 'data' => 'name_ar', 'name' => 'name_ar', 'searchable' => false],
            [
                'title' => 'Verified',
                'data' => 'is_verified',
                'name' => 'is_verified',
                'searchable' => false,
                'render' => 'Renderers.badge({ true: { label: "Yes", color: "success" }, false: { label: "No", color: "gray" } })',
            ],
            [
                'title' => 'Restricted',
                'data' => 'is_restricted',
                'name' => 'is_restricted',
                'searchable' => false,
                'render' => 'Renderers.badge({ true: { label: "Yes", color: "danger" }, false: { label: "No", color: "gray" } })',
            ],
            [
                'title' => 'Active',
                'data' => 'is_active',
                'name' => 'is_active',
                'searchable' => false,
                'render' => 'Renderers.badge({ true: { label: "Active", color: "success" }, false: { label: "Inactive", color: "gray" } })',
            ],
            ['title' => 'Products', 'data' => 'product_count', 'name' => 'product_count', 'orderable' => false, 'searchable' => false, 'className' => 'text-right'],
            [
                'title' => 'Created',
                'data' => 'created_at',
                'name' => 'created_at',
                'searchable' => false,
                'render' => 'Renderers.dateAgo',
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
            ['type' => 'text', 'name' => 'search', 'label' => 'Name', 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'is_verified',
                'label' => 'Verified',
                'options' => ['' => 'All', '1' => 'Verified', '0' => 'Unverified'],
            ],
            [
                'type' => 'select',
                'name' => 'is_restricted',
                'label' => 'Restricted',
                'options' => ['' => 'All', '1' => 'Restricted', '0' => 'Not Restricted'],
            ],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Active',
                'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive'],
            ],
        ];
    @endphp

    <x-table.datatable id="brands-table" url="{{ route('admin.brands.datatable') }}" :columns="$columns" :filters="$filters"
        :create-action="['url' => route('admin.brands.create'), 'label' => 'Add Brand']" :page-length="25" :order="[[6, 'desc']]" />
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.delete = async function (id, row) {
            const message = 'Delete "' + (row.name_en || 'this brand') + '"?';
            const confirmed = window.confirmDelete
                ? await window.confirmDelete(message, { title: 'Delete brand?' })
                : confirm(message);
            if (!confirmed) return;

            $.ajax({ url: row.delete_url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                .done(function (res) {
                    window.Toast && window.Toast.success('Brand deleted.');
                    window.reloadDataTable('brands-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || 'Delete failed.');
                });
        };
    </script>
@endpush