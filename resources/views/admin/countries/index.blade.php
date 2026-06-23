@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Countries')

@section('content')
    @php
        $columns = [
            ['title' => 'Country', 'data' => 'name', 'name' => 'name'],
            ['title' => 'Code', 'data' => 'iso_code_2', 'name' => 'iso_code_2', 'className' => 'text-center w-16'],
            ['title' => 'Currency', 'data' => 'currency_code', 'name' => 'currency_code', 'orderable' => false, 'searchable' => false, 'className' => 'text-center w-20'],
            ['title' => 'VAT', 'data' => 'vat_rate', 'name' => 'vat_rate', 'orderable' => false, 'searchable' => false, 'className' => 'text-right w-16'],
            ['title' => 'Cities', 'data' => 'cities_count', 'name' => 'cities_count', 'orderable' => false, 'searchable' => false, 'className' => 'text-center w-16'],
            ['title' => 'Pay. Methods', 'data' => 'active_payment_methods', 'name' => 'active_payment_methods', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            [
                'title' => 'COD',
                'data' => 'cod_available',
                'name' => 'cod_available',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center w-12',
                'render' => 'function(data){return data?"<span class=\"text-success-600 font-bold\">✓</span>":"<span class=\"text-gray-300\">—</span>";}'
            ],
            [
                'title' => 'Status',
                'data' => 'is_launched',
                'name' => 'is_launched',
                'orderable_column' => 'countries.is_launched',
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'Renderers.badge({
                                                  true:  { label: "Launched", color: "success" },
                                                  false: { label: "Draft",    color: "gray"    }
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
                                             { type: "link", label: "Configure", url: ":edit_url", class: "btn-primary btn-sm" }
                                         ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Search', 'placeholder' => 'Name or code…'],
            [
                'type' => 'select',
                'name' => 'is_launched',
                'label' => 'Launch Status',
                'options' => ['' => 'All', '1' => 'Launched', '0' => 'Draft']
            ],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Active',
                'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']
            ],
        ];
    @endphp

    <x-table.datatable id="countries-table" url="{{ route('admin.countries.datatable') }}" :columns="$columns"
        :filters="$filters" :create-action="['url' => route('admin.countries.create'), 'label' => 'Add Country']"
        :selectable="false" :page-length="25" :order="[[0, 'asc']]" />
@endsection

@push('scripts')
    @vite('resources/js/admin/countries.js')
@endpush
