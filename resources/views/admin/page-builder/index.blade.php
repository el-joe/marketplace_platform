@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Page Builder')

@section('content')
    @php
        $columns = [
            [
                'title' => 'Name',
                'data' => 'name',
                'name' => 'name',
                'searchable' => true,
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Type',
                'data' => 'page_type',
                'name' => 'page_type',
                'searchable' => true,
                'render' => 'function(d){return "<span class=\"capitalize\">"+d+"</span>";}',
            ],
            [
                'title' => 'Country',
                'data' => 'country_name',
                'name' => 'country_name',
                'searchable' => false,
            ],
            [
                'title' => 'Slug',
                'data' => 'slug',
                'name' => 'slug',
                'searchable' => true,
                'render' => 'function(d){return "<span class=\"font-mono text-xs text-gray-500\">"+d+"</span>";}',
            ],
            [
                'title' => 'Ver.',
                'data' => 'version',
                'name' => 'version',
                'searchable' => false,
                'className' => 'text-center font-mono',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            draft:     { label: "Draft",     color: "gray"    },
                            published: { label: "Published", color: "success" },
                            scheduled: { label: "Scheduled", color: "primary" },
                            archived:  { label: "Archived",  color: "warning" }
                        })',
            ],
            [
                'title' => 'Last Edited',
                'data' => 'updated_at',
                'name' => 'updated_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.dateAgo(data) : "—";}',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                            { type: "link", label: "Edit", url: ":show_url", class: "btn-primary btn-sm" }
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Name / Slug', 'placeholder' => 'Search pages…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'scheduled' => 'Scheduled',
                    'archived' => 'Archived',
                ]
            ],
            [
                'type' => 'select',
                'name' => 'page_type',
                'label' => 'Type',
                'options' => [
                    'home' => 'Home',
                    'category' => 'Category',
                    'brand' => 'Brand',
                    'landing' => 'Landing',
                    'campaign' => 'Campaign',
                    'custom' => 'Custom',
                ]
            ],
        ];
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Page Builder</h1>
        <a href="{{ route('admin.page-builder.create') }}" class="btn btn-primary">
            <x-heroicon name="plus" class="w-4 h-4 mr-1.5" />
            New Page
        </a>
    </div>

    <x-table.datatable id="pages-table" :url="route('admin.page-builder.datatable')" :columns="$columns" :filters="$filters"
        :page-length="25" />
@endsection