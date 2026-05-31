@extends('layouts.admin')

@section('title', 'Flash Sales')

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/flash-sales.js'])
@endpush

@section('content')

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="Live Now"            :value="$stats['live_count']"                  color="success" icon="bolt" />
        <x-stat-card label="Upcoming"            :value="$stats['upcoming_count']"               color="primary" icon="calendar" />
        <x-stat-card label="Units This Month"    :value="number_format($stats['total_this_month_units'])" color="info" icon="shopping-bag" />
        <x-stat-card label="Revenue This Month"  :value="number_format($stats['total_this_month_revenue'], 2)" color="warning" icon="currency-dollar" />
    </div>

    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex flex-wrap gap-1" x-data="{ tab: 'all' }">
                @foreach ([
                    'all'               => 'All',
                    'draft'             => 'Draft',
                    'submission_open'   => 'Accepting Submissions',
                    'under_review'      => 'Under Review',
                    'live'              => 'Live',
                    'ended'             => 'Ended',
                    'cancelled'         => 'Cancelled',
                ] as $val => $label)
                    <button
                        @click="tab = '{{ $val }}'; window.filterTable('{{ $val }}')"
                        :class="tab === '{{ $val }}' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                    >{{ $label }}</button>
                @endforeach
            </div>

            @if(auth('admin')->user()->can('flash_sales.create', \App\Models\FlashSale::class))
                <a href="{{ route('admin.flash-sales.create') }}" class="btn btn-primary btn-sm">
                    + New Flash Sale
                </a>
            @endif
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-3 mb-4" id="table-filters">
            <select id="filter-country" class="form-select form-select-sm w-44">
                <option value="">All countries</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                @endforeach
            </select>
            <input type="date" id="filter-date-from" class="form-input form-input-sm w-36" placeholder="From">
            <input type="date" id="filter-date-to"   class="form-input form-input-sm w-36" placeholder="To">
        </div>

        <table id="flash-sales-table" class="w-full text-sm"></table>
    </x-card>

@endsection

@push('scripts')
<script type="module">
const FLASH_SALES_DATATABLE_URL = '{{ route('admin.flash-sales.datatable') }}';

const STATUS_BADGES = {
    draft:              { label: 'Draft',                  color: 'gray'    },
    submission_open:    { label: 'Accepting Submissions',  color: 'primary' },
    submission_closed:  { label: 'Submissions Closed',     color: 'warning' },
    under_review:       { label: 'Under Review',           color: 'info'    },
    approved:           { label: 'Approved',               color: 'success' },
    live:               { label: 'Live',                   color: 'success' },
    ended:              { label: 'Ended',                  color: 'gray'    },
    cancelled:          { label: 'Cancelled',              color: 'danger'  },
};

window.filterTable = function filterTable(status) {
    if ($.fn.DataTable.isDataTable('#flash-sales-table')) {
        const dt = $('#flash-sales-table').DataTable();
        dt.ajax.url(
            FLASH_SALES_DATATABLE_URL + (status !== 'all' ? '?status=' + status : '')
        ).load();
    }
};

$(function () {
    initDataTable('flash-sales-table', {
        url: FLASH_SALES_DATATABLE_URL,
        columns: [
            { data: 'name_en',           title: 'Name',
              render: (d, t, r) => `<a href="${r.edit_url}" class="font-medium text-primary-600 hover:underline">${d}</a>` },
            { data: 'country_name',      title: 'Country' },
            { data: 'status',            title: 'Status',
              render: (d) => {
                const b = STATUS_BADGES[d] || { label: d, color: 'gray' };
                return `<span class="badge badge-${b.color}">${b.label}</span>`;
              }},
            { data: 'submission_period', title: 'Submissions', orderable: false },
            { data: 'sale_period',       title: 'Sale Period' },
            { data: 'slots',             title: 'Slots', orderable: false },
            { data: 'min_discount_pct',  title: 'Min Disc.' },
            { data: 'units_sold',        title: 'Units Sold' },
        ],
        serverSideFilters: {
            country_id: () => $('#filter-country').val(),
            date_from:  () => $('#filter-date-from').val(),
            date_to:    () => $('#filter-date-to').val(),
        },
    });

    $('#filter-country, #filter-date-from, #filter-date-to').on('change', function () {
        if ($.fn.DataTable.isDataTable('#flash-sales-table')) {
            $('#flash-sales-table').DataTable().ajax.reload();
        }
    });
});
</script>
@endpush
