@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('content')
    @php
        $columns = [
            ['title' => 'Role Name', 'data' => 'name', 'name' => 'name'],
            ['title' => 'Guard', 'data' => 'guard_name', 'name' => 'guard_name', 'searchable' => false],
            [
                'title' => 'Admins',
                'data' => 'admin_count',
                'name' => 'admin_count',
                'searchable' => false,
                'orderable' => false,
                'className' => 'text-right',
                'render' => 'function(data) { return "<span class=\"font-mono text-sm\">" + data + "</span>"; }',
            ],
            [
                'title' => 'Permissions',
                'data' => 'permissions_count',
                'name' => 'permissions_count',
                'searchable' => false,
                'orderable' => false,
                'className' => 'text-right',
                'render' => 'function(data) { return "<span class=\"font-mono text-sm\">" + data + "</span>"; }',
            ],
            [
                'title' => 'Created',
                'data' => 'created_at',
                'name' => 'created_at',
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
                            { type: "link",   label: "Edit",   url: ":edit_url",   class: "btn-secondary" },
                            { type: "button", label: "Delete", id: "delete",       class: "btn-danger",  condition: (row) => !row.is_super_admin },
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Role Name', 'placeholder' => 'Search…'],
        ];
    @endphp

    <x-table.datatable id="roles-table" url="{{ route('admin.roles.datatable') }}" :columns="$columns" :filters="$filters"
        :create-action="['url' => route('admin.roles.create'), 'label' => 'Create Role']" :page-length="25" :order="[[0, 'asc']]" />
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        window.tableActions.delete = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete('Delete role "' + row.name + '"? This cannot be undone.', { title: 'Delete Role?' })
                : confirm('Delete role "' + row.name + '"?');
            if (!confirmed) return;

            $.ajax({
                url: row.delete_url, method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(() => { window.Toast?.success('Role deleted.'); window.reloadDataTable('roles-table'); })
                .fail(xhr => { window.Toast?.error(xhr.responseJSON?.message || 'Delete failed.'); });
        };
    </script>
@endpush