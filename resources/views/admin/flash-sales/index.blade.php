@extends('layouts.admin')

@section('title', __('admin.flash_sales.title'))

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/flash-sales.js'])
@endpush

@section('content')

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('admin.flash_sales.live_now') }}"            :value="$stats['live_count']"                  color="success" icon="bolt" />
        <x-stat-card label="{{ __('admin.flash_sales.upcoming') }}"            :value="$stats['upcoming_count']"               color="primary" icon="calendar" />
        <x-stat-card label="{{ __('admin.flash_sales.units_this_month') }}"    :value="number_format($stats['total_this_month_units'])" color="info" icon="shopping-bag" />
        <x-stat-card label="{{ __('admin.flash_sales.revenue_this_month') }}"  :value="number_format($stats['total_this_month_revenue'], 2)" color="warning" icon="currency-dollar" />
    </div>

    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex flex-wrap gap-1" x-data="{ tab: 'all' }">
                @foreach ([
                    'all'               => __('common.all'),
                    'draft'             => __('admin.flash_sales.status_draft'),
                    'submission_open'   => __('admin.flash_sales.status_submission_open'),
                    'under_review'      => __('admin.flash_sales.status_under_review'),
                    'live'              => __('admin.flash_sales.status_live'),
                    'ended'             => __('admin.flash_sales.status_ended'),
                    'cancelled'         => __('admin.flash_sales.status_cancelled'),
                ] as $val => $label)
                    <button
                        @click="tab = '{{ $val }}'; window.filterTable('{{ $val }}')"
                        :class="tab === '{{ $val }}' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'"
                    >{{ $label }}</button>
                @endforeach
            </div>

            @if(auth('admin')->user()->can('flash_sales.create', \App\Models\FlashSale::class))
                <a href="{{ route('admin.flash-sales.create') }}" class="btn btn-primary btn-sm">
                    + {{ __('admin.flash_sales.new_flash_sale') }}
                </a>
            @endif
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-3 mb-4" id="table-filters">
            <select id="filter-country" class="form-select form-select-sm w-44">
                <option value="">{{ __('admin.flash_sales.all_countries') }}</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                @endforeach
            </select>
            <input type="date" id="filter-date-from" class="form-input form-input-sm w-36" placeholder="{{ __('common.from') }}">
            <input type="date" id="filter-date-to"   class="form-input form-input-sm w-36" placeholder="{{ __('common.to') }}">
        </div>

        <table id="flash-sales-table" class="w-full text-sm"></table>
    </x-card>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        name: @json(__('admin.flash_sales.name')),
        country: @json(__('admin.flash_sales.country')),
        status: @json(__('admin.status')),
        submissions: @json(__('admin.flash_sales.submissions')),
        salePeriod: @json(__('admin.flash_sales.sale_period')),
        slots: @json(__('admin.flash_sales.slots')),
        minDisc: @json(__('admin.flash_sales.min_disc')),
        unitsSold: @json(__('admin.flash_sales.units_sold')),
        statusDraft: @json(__('admin.flash_sales.status_draft')),
        statusSubmissionOpen: @json(__('admin.flash_sales.status_submission_open')),
        statusSubmissionClosed: @json(__('admin.flash_sales.status_submission_closed')),
        statusUnderReview: @json(__('admin.flash_sales.status_under_review')),
        statusApproved: @json(__('admin.flash_sales.status_approved')),
        statusLive: @json(__('admin.flash_sales.status_live')),
        statusEnded: @json(__('admin.flash_sales.status_ended')),
        statusCancelled: @json(__('admin.flash_sales.status_cancelled')),
    });
</script>
<script type="module">
const FLASH_SALES_DATATABLE_URL = '{{ route('admin.flash-sales.datatable') }}';

const STATUS_BADGES = {
    draft:              { label: window.TRANSLATIONS.statusDraft,              color: 'gray'    },
    submission_open:    { label: window.TRANSLATIONS.statusSubmissionOpen,      color: 'primary' },
    submission_closed:  { label: window.TRANSLATIONS.statusSubmissionClosed,    color: 'warning' },
    under_review:       { label: window.TRANSLATIONS.statusUnderReview,        color: 'info'    },
    approved:           { label: window.TRANSLATIONS.statusApproved,           color: 'success' },
    live:               { label: window.TRANSLATIONS.statusLive,               color: 'success' },
    ended:              { label: window.TRANSLATIONS.statusEnded,              color: 'gray'    },
    cancelled:          { label: window.TRANSLATIONS.statusCancelled,          color: 'danger'  },
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
            { data: 'name_en',           title: window.TRANSLATIONS.name,
              render: (d, t, r) => `<a href="${r.show_url}" class="font-medium text-primary-600 hover:underline">${d}</a>` },
            { data: 'country_name',      title: window.TRANSLATIONS.country },
            { data: 'status',            title: window.TRANSLATIONS.status,
              render: (d) => {
                const b = STATUS_BADGES[d] || { label: d, color: 'gray' };
                return `<span class="badge badge-${b.color}">${b.label}</span>`;
              }},
            { data: 'submission_period', title: window.TRANSLATIONS.submissions, orderable: false },
            { data: 'sale_period',       title: window.TRANSLATIONS.salePeriod },
            { data: 'slots',             title: window.TRANSLATIONS.slots, orderable: false },
            { data: 'min_discount_pct',  title: window.TRANSLATIONS.minDisc },
            { data: 'units_sold',        title: window.TRANSLATIONS.unitsSold },
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
