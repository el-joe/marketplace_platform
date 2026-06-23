@extends('layouts.admin')

@section('title', 'Attributes')

@push('styles')
    @vite([
        'resources/js/components/datatable.js',
        'resources/js/components/column-renderers.js',
        'resources/js/admin/attributes.js',
    ])
@endpush

@section('content')
    @php
        $columns = [
            ['title' => 'Name', 'data' => 'name_en', 'name' => 'name_en'],
            ['title' => 'Code', 'data' => 'code', 'name' => 'code'],
            [
                'title' => 'Type',
                'data' => 'type',
                'name' => 'type',
                'searchable' => false,
                'render' => 'Renderers.badge({
                                                text:        { label: "Text",         color: "gray"    },
                                                number:      { label: "Number",       color: "gray"    },
                                                select:      { label: "Select",       color: "primary" },
                                                multi_select:{ label: "Multi-select", color: "primary" },
                                                boolean:     { label: "Boolean",      color: "warning" },
                                                color:       { label: "Color",        color: "danger"  },
                                                date:        { label: "Date",         color: "gray"    }
                                            })'
            ],
            ['title' => 'Unit', 'data' => 'unit', 'name' => 'unit', 'searchable' => false],
            ['title' => 'Created', 'data' => 'created_at', 'name' => 'created_at', 'searchable' => false, 'render' => 'Renderers.dateAgo'],
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
                                            ])'
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Name / Code', 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'type',
                'label' => 'Type',
                'options' => [
                    'text' => 'Text',
                    'number' => 'Number',
                    'select' => 'Select',
                    'multi_select' => 'Multi-select',
                    'boolean' => 'Boolean',
                    'color' => 'Color',
                    'date' => 'Date',
                ],
            ],
        ];
    @endphp

    <x-table.datatable id="attributes-table" url="{{ route('admin.attributes.datatable') }}" :columns="$columns"
        :filters="$filters" :create-action="['url' => route('admin.attributes.create'), 'label' => 'New Attribute']" />

    <script>
        window.ROUTES_ATTR = {
            destroy: '{{ url('attributes') }}/:id',
        };
    </script>
@endsection