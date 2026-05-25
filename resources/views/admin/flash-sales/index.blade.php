@extends('layouts.admin')

@section('title', 'Flash Sales')

@section('content')
    @php
        $columns = [
            [
                'title' => 'Name',
                'data' => 'name_en',
                'name' => 'name_en',
                'searchable' => true,
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Country',
                'data' => 'country_name',
                'name' => 'country_name',
                'searchable' => false,
            ],
            [
                'title' => 'Starts',
                'data' => 'sale_starts_at',
                'name' => 'sale_starts_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.date(data) : "—";}',
            ],
            [
                'title' => 'Ends',
                'data' => 'sale_ends_at',
                'name' => 'sale_ends_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.date(data) : "—";}',
            ],
            [
                'title' => 'Slots',
                'data' => 'slots',
                'name' => 'slots',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(d,t,row){return "<span class=\"font-mono\">"+row.approved_slots_count+"/"+(row.max_total_slots||"∞")+"</span>";}',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            draft:     { label: "Draft",            color: "gray"    },
                            open:      { label: "Submissions Open", color: "primary" },
                            review:    { label: "Under Review",     color: "warning" },
                            scheduled: { label: "Scheduled",        color: "primary" },
                            live:      { label: "Live",             color: "success" },
                            ended:     { label: "Ended",            color: "gray"    },
                            cancelled: { label: "Cancelled",        color: "danger"  }
                        })',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                            { type: "link", label: "View", url: ":show_url", class: "btn-primary btn-sm" }
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Name', 'placeholder' => 'Search name…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'draft' => 'Draft',
                    'open' => 'Submissions Open',
                    'review' => 'Under Review',
                    'scheduled' => 'Scheduled',
                    'live' => 'Live',
                    'ended' => 'Ended',
                    'cancelled' => 'Cancelled',
                ],
            ],
            ['type' => 'date_range', 'name' => 'date', 'label' => 'Sale Dates'],
        ];
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div></div>
        <a href="{{ route('admin.flash-sales.create') }}" class="btn btn-primary">
            <x-heroicon name="plus" class="w-4 h-4 mr-1.5" />
            New Flash Sale
        </a>
    </div>

    <x-table.datatable id="flash-sales-table" url="{{ route('admin.flash-sales.datatable') }}" :columns="$columns"
        :filters="$filters" :page-length="25" :order="[[2, 'desc']]" />
@endsection