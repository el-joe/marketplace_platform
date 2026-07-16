@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.admin_product_listings.title'))

@section('content')
    @php
        $columns = [
            ['title' => __('admin.admin_product_listings.product_col'), 'data' => 'product_name', 'name' => 'product_name'],
            ['title' => __('admin.admin_product_listings.country_col'), 'data' => 'country', 'name' => 'country', 'searchable' => false],
            ['title' => __('admin.admin_product_listings.price_col'), 'data' => 'price', 'name' => 'price', 'searchable' => false],
            [
                'title' => __('admin.admin_product_listings.fulfillment_col'),
                'data' => 'fulfillment_type',
                'name' => 'fulfillment_type',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    express: { label: "' . __('admin.admin_product_listings.express') . '", color: "warning" },
                    global:  { label: "' . __('admin.admin_product_listings.global') . '",  color: "info"    },
                    mixed:   { label: "' . __('admin.admin_product_listings.mixed') . '",   color: "gray"    }
                })'
            ],
            ['title' => __('admin.admin_product_listings.payment_col'), 'data' => 'payment_options', 'name' => 'payment_options', 'searchable' => false],
            [
                'title' => __('admin.admin_product_listings.now_nawy_col'),
                'data' => 'featured_in_nawy',
                'name' => 'featured_in_nawy',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data;
                    return \'<button type="button" class="nawy-featured-toggle relative inline-flex h-5 w-9 items-center rounded-full transition-colors \' + (data ? "bg-primary-600" : "bg-gray-200") + \'" data-url="\' + row.toggle_featured_url + \'">\' +
                        \'<span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform \' + (data ? "translate-x-4" : "translate-x-1") + \'"></span>\' +
                        \'</button>\';
                }'
            ],
            [
                'title' => __('admin.admin_product_listings.status_col'),
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    active:   { label: "' . __('admin.admin_product_listings.active') . '",   color: "success" },
                    paused:   { label: "' . __('admin.admin_product_listings.paused') . '",   color: "warning" },
                    archived: { label: "' . __('admin.admin_product_listings.archived') . '", color: "gray"    }
                })'
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'Renderers.actions([
                    { type: "link",   label: "' . __('common.view') . '",   url: ":show_url" },
                    { type: "link",   label: "' . __('common.edit') . '",   url: ":edit_url" },
                    { type: "button", label: "' . __('admin.admin_product_listings.archive') . '", id: "archive", class: "btn-danger" }
                ])'
            ],
        ];

        $filters = [
            [
                'type' => 'select',
                'name' => 'country_id',
                'label' => __('admin.admin_product_listings.country_col'),
                'options' => $countries,
                'placeholder' => __('admin.all'),
            ],
            [
                'type' => 'select',
                'name' => 'fulfillment_type',
                'label' => __('admin.admin_product_listings.fulfillment_col'),
                'options' => [
                    'express' => __('admin.admin_product_listings.express'),
                    'global' => __('admin.admin_product_listings.global'),
                    'mixed' => __('admin.admin_product_listings.mixed'),
                ],
            ],
            [
                'type' => 'select',
                'name' => 'nawy_category_id',
                'label' => __('admin.admin_product_listings.nawy_category'),
                'options' => $nawyCategories,
                'placeholder' => __('admin.admin_product_listings.no_category'),
            ],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => __('common.status'),
                'options' => [
                    'active' => __('admin.admin_product_listings.active'),
                    'paused' => __('admin.admin_product_listings.paused'),
                    'archived' => __('admin.admin_product_listings.archived'),
                ],
            ],
        ];
    @endphp

    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ __('admin.admin_product_listings.title') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.admin_product_listings.page_subtitle') }}</p>
            </div>
            <a href="{{ route('admin.admin-product-listings.categories') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                <x-heroicon name="squares-2x2" class="w-4 h-4" />
                Sidebar Categories
            </a>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">Total Active Listings</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['active_listings']) }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">Revenue from Admin Listings</p>
                @forelse($stats['revenue_by_currency'] as $row)
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($row->total_cents / 100, 2) }} {{ $row->currency }}</p>
                @empty
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                @endforelse
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">Top Selling Admin Product</p>
                @if($stats['top_selling'])
                    <p class="mt-1 text-sm font-bold text-gray-900 truncate" title="{{ $stats['top_selling']->name_en }}">{{ $stats['top_selling']->name_en }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($stats['top_selling']->units_sold) }} units sold</p>
                @else
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">Countries Active</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['countries_active']) }}</p>
            </div>
        </div>

        <x-table.datatable id="nawy-listings-table" url="{{ route('admin.admin-product-listings.datatable') }}"
            :columns="$columns" :filters="$filters"
            :create-action="['url' => route('admin.admin-product-listings.create'), 'label' => __('admin.admin_product_listings.new_listing')]"
            :selectable="false" :page-length="25" />
    </div>
@endsection

@push('scripts')
    <script>
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            archiveListingQuestion: @json(__('admin.admin_product_listings.remove_listing_confirm')),
            listingArchived: @json(__('admin.admin_product_listings.listing_archived')),
            archiveFailed: @json(__('admin.admin_product_listings.archive_failed')),
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.nawy-featured-toggle');
            if (!btn) return;
            const url = btn.dataset.url;
            btn.disabled = true;
            $.ajax({ url: url, method: 'POST' })
                .done(function (res) {
                    btn.classList.toggle('bg-primary-600', res.featured_in_nawy);
                    btn.classList.toggle('bg-gray-200', !res.featured_in_nawy);
                    const knob = btn.querySelector('span');
                    knob.classList.toggle('translate-x-4', res.featured_in_nawy);
                    knob.classList.toggle('translate-x-1', !res.featured_in_nawy);
                    window.Toast && window.Toast.success(res.featured_in_nawy ? 'Featured in Nawy Now.' : 'Removed from Nawy Now.');
                })
                .fail(function () {
                    window.Toast && window.Toast.error('Failed to update.');
                })
                .always(function () {
                    btn.disabled = false;
                });
        });

        window.tableActions = window.tableActions || {};

        window.tableActions.archive = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete(window.TRANSLATIONS.archiveListingQuestion)
                : confirm(window.TRANSLATIONS.archiveListingQuestion);
            if (!confirmed) return;

            $.ajax({ url: row.delete_url, method: 'DELETE' })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message || window.TRANSLATIONS.listingArchived);
                    window.reloadDataTable('nawy-listings-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.archiveFailed);
                });
        };
    </script>
@endpush
