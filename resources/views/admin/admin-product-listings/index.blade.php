@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.admin_product_listings.title'))

@section('content')
    @php
        $columns = [
            ['title' => __('admin.admin_product_listings.product_col'), 'data' => 'product_name', 'name' => 'product_name'],
            ['title' => __('admin.admin_product_listings.variant_col'), 'data' => 'variant_name', 'name' => 'variant_name', 'searchable' => false],
            ['title' => __('admin.admin_product_listings.sku_col'), 'data' => 'platform_sku', 'name' => 'platform_sku'],
            ['title' => __('admin.admin_product_listings.country_col'), 'data' => 'country', 'name' => 'country'],
            ['title' => __('common.currency'), 'data' => 'currency', 'name' => 'currency', 'searchable' => false],
            ['title' => __('admin.admin_product_listings.price_col'), 'data' => 'price', 'name' => 'price', 'searchable' => false],
            ['title' => __('admin.admin_product_listings.compare_at_price_col'), 'data' => 'compare_at_price', 'name' => 'compare_at_price', 'searchable' => false],
            [
                'title' => __('admin.admin_product_listings.status_col'),
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    active:       { label: "' . __('admin.admin_product_listings.active') . '",       color: "success" },
                    paused:       { label: "' . __('admin.admin_product_listings.paused') . '",       color: "warning" },
                    out_of_stock: { label: "' . __('admin.admin_product_listings.out_of_stock') . '", color: "orange"  },
                    archived:     { label: "' . __('admin.admin_product_listings.archived') . '",     color: "gray"    }
                })'
            ],
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
            [
                'title' => __('admin.admin_product_listings.now_nawy_col'),
                'data' => 'featured_in_nawy',
                'name' => 'featured_in_nawy',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data;
                    return data ? "⭐" : "";
                }'
            ],
            [
                'title' => __('admin.admin_product_listings.rating_col'),
                'data' => 'rating_avg',
                'name' => 'rating_avg',
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data;
                    if (!data) return "—";
                    return data + " (" + (row.rating_count || 0) + ")";
                }'
            ],
            ['title' => __('admin.admin_product_listings.total_sold_col'), 'data' => 'total_sold', 'name' => 'total_sold', 'searchable' => false, 'className' => 'text-center'],
            ['title' => __('admin.admin_product_listings.warehouse_col'), 'data' => 'warehouse_name', 'name' => 'warehouse_name', 'searchable' => false],
            [
                'title' => __('admin.admin_product_listings.shipping_col'),
                'data' => 'shipping',
                'name' => 'shipping',
                'orderable' => false,
                'searchable' => false,
                'render' => 'function (data, type, row) {
                    if (type !== "display") return data ? data.label : "";
                    if (!data) return "<span class=\\"text-xs text-gray-400\\">—</span>";
                    var pill = "<span class=\\"inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold\\" style=\\"background-color:" + data.color + ";color:" + data.text_color + ";\\">" + data.label + "</span>";
                    if (data.is_inherited) pill += " <span class=\\"text-[10px] uppercase tracking-wide text-gray-400\\">(default)</span>";
                    return pill;
                }'
            ],
            ['title' => __('admin.admin_product_listings.created_at_col'), 'data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
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
                    { type: "button", label: "' . __('admin.admin_product_listings.activate') . '", id: "activate", class: "btn-success", condition: (row) => row.status !== "active" },
                    { type: "button", label: "' . __('admin.admin_product_listings.archive') . '", id: "archive", class: "btn-danger", condition: (row) => row.status !== "archived" }
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
                    'out_of_stock' => __('admin.admin_product_listings.out_of_stock'),
                    'archived' => __('admin.admin_product_listings.archived'),
                ],
            ],
        ];

        $bulkActions = [
            ['id' => 'bulk-activate', 'label' => __('admin.admin_product_listings.bulk_activate'), 'class' => 'btn-success', 'confirmMessage' => __('admin.admin_product_listings.confirm_bulk_activate')],
            ['id' => 'bulk-pause', 'label' => __('admin.admin_product_listings.bulk_pause'), 'class' => 'btn-ghost', 'confirmMessage' => __('admin.admin_product_listings.confirm_bulk_pause')],
            ['id' => 'bulk-archive', 'label' => __('admin.admin_product_listings.bulk_archive'), 'class' => 'btn-danger', 'confirmMessage' => __('admin.admin_product_listings.confirm_bulk_archive')],
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
                {{ __('admin.admin_product_listings.sidebar_categories') }}
            </a>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('admin.admin_product_listings.total_active_listings') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['active_listings']) }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('admin.admin_product_listings.revenue_from_admin_listings') }}</p>
                @forelse($stats['revenue_by_currency'] as $row)
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($row->total, 2) }} {{ $row->currency }}</p>
                @empty
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                @endforelse
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('admin.admin_product_listings.top_selling_admin_product') }}</p>
                @if($stats['top_selling'])
                    <p class="mt-1 text-sm font-bold text-gray-900 truncate" title="{{ $stats['top_selling']->name_en }}">{{ $stats['top_selling']->name_en }}</p>
                    <p class="text-xs text-gray-500">{{ __('admin.admin_product_listings.units_sold', ['count' => number_format($stats['top_selling']->units_sold)]) }}</p>
                @else
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('admin.admin_product_listings.countries_active') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['countries_active']) }}</p>
            </div>
        </div>

        <x-table.datatable id="nawy-listings-table" url="{{ route('admin.admin-product-listings.datatable') }}"
            :columns="$columns" :filters="$filters" :bulk-actions="$bulkActions"
            bulk-url="{{ route('admin.admin-product-listings.bulk') }}"
            :create-action="['url' => route('admin.admin-product-listings.create'), 'label' => __('admin.admin_product_listings.new_listing')]"
            :selectable="true" :page-length="25" />
    </div>
@endsection

@push('scripts')
    <script>
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            archiveListingTitle: @json(__('admin.admin_product_listings.archive_listing_title')),
            archiveListingQuestion: @json(__('admin.admin_product_listings.remove_listing_confirm')),
            listingArchived: @json(__('admin.admin_product_listings.listing_archived')),
            archiveFailed: @json(__('admin.admin_product_listings.archive_failed')),
            activateListingTitle: @json(__('admin.admin_product_listings.activate_listing_title')),
            activateListingQuestion: @json(__('admin.admin_product_listings.activate_listing_confirm')),
            listingActivated: @json(__('admin.admin_product_listings.listing_activated')),
            activateFailed: @json(__('admin.admin_product_listings.activate_failed')),
            featuredInNawyNow: @json(__('admin.admin_product_listings.featured_in_nawy_now')),
            removedFromNawyNow: @json(__('admin.admin_product_listings.removed_from_nawy_now')),
            failedToUpdate: @json(__('admin.admin_product_listings.failed_to_update')),
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
                    window.Toast && window.Toast.success(res.featured_in_nawy ? window.TRANSLATIONS.featuredInNawyNow : window.TRANSLATIONS.removedFromNawyNow);
                })
                .fail(function () {
                    window.Toast && window.Toast.error(window.TRANSLATIONS.failedToUpdate);
                })
                .always(function () {
                    btn.disabled = false;
                });
        });

        window.tableActions = window.tableActions || {};

        window.tableActions.archive = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete(window.TRANSLATIONS.archiveListingQuestion, { title: window.TRANSLATIONS.archiveListingTitle })
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

        window.tableActions['bulk-activate'] = function (ids, tableId) {
            postBulkStatus(ids, tableId, 'active');
        };
        window.tableActions['bulk-pause'] = function (ids, tableId) {
            postBulkStatus(ids, tableId, 'paused');
        };
        window.tableActions['bulk-archive'] = function (ids, tableId) {
            postBulkStatus(ids, tableId, 'archived');
        };

        function postBulkStatus(ids, tableId, status) {
            $.ajax({ url: '{{ route('admin.admin-product-listings.bulk') }}', method: 'POST', data: { action: status, ids: ids } })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message);
                    window.reloadDataTable(tableId);
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.failedToUpdate);
                });
        }

        window.tableActions.activate = async function (id, row) {
            const confirmed = window.confirmDialog
                ? await window.confirmDialog({
                    title: window.TRANSLATIONS.activateListingTitle,
                    text: window.TRANSLATIONS.activateListingQuestion,
                    icon: 'question',
                    customClass: {
                        confirmButton: 'inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors',
                    },
                })
                : confirm(window.TRANSLATIONS.activateListingQuestion);
            if (!confirmed) return;

            $.ajax({ url: row.activate_url, method: 'POST' })
                .done(function (res) {
                    window.Toast && window.Toast.success(res.message || window.TRANSLATIONS.listingActivated);
                    window.reloadDataTable('nawy-listings-table');
                })
                .fail(function (xhr) {
                    window.Toast && window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.activateFailed);
                });
        };
    </script>
@endpush
