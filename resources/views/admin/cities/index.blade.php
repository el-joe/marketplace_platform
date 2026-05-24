@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Cities')

@section('content')
    @php
        $columns = [
            ['title' => 'City', 'data' => 'name', 'name' => 'name'],
            ['title' => 'Country', 'data' => 'country_name', 'name' => 'country_name', 'orderable_column' => 'countries.name_en'],
            ['title' => 'Zone', 'data' => 'shipping_zone', 'name' => 'shipping_zone', 'orderable' => false, 'searchable' => false],
            [
                'title' => 'COD',
                'data' => 'cod_available',
                'name' => 'cod_available',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center w-12'
            ],
            [
                'title' => 'Status',
                'data' => 'is_active',
                'name' => 'is_active',
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'Renderers.badge({true:{label:"Active",color:"success"},false:{label:"Inactive",color:"gray"}})'
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([{type:"link",label:"Edit",url:":edit_url",class:"btn-ghost btn-sm"}])'
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Name'],
            [
                'type' => 'select',
                'name' => 'country_id',
                'label' => 'Country',
                'options' => array_merge(['' => 'All Countries'], $countries->toArray())
            ],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Status',
                'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']
            ],
            [
                'type' => 'select',
                'name' => 'no_zone',
                'label' => 'Zone',
                'options' => ['' => 'All', '1' => 'No zone ⚠']
            ],
        ];
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div></div>
        <div class="flex gap-2">
            <button type="button" id="btn-bulk-import" class="btn btn-secondary">
                <x-heroicon name="arrow-up-tray" class="w-4 h-4" />
                Bulk Import CSV
            </button>
            <a href="{{ route('admin.cities.create') }}" class="btn btn-primary">
                <x-heroicon name="plus" class="w-4 h-4" />
                Add City
            </a>
        </div>
    </div>

    <x-table.datatable id="cities-table" url="{{ route('admin.cities.datatable') }}" :columns="$columns" :filters="$filters"
        :page-length="50" :order="[[1, 'asc'], [0, 'asc']]" />

    {{-- Bulk Import Modal --}}
    <x-modal id="bulk-import-modal" title="Bulk Import Cities from CSV">
        <div class="p-4 space-y-4">
            <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-800">
                <p class="font-semibold mb-1">CSV Format (header row required):</p>
                <code
                    class="text-xs block font-mono">name_en, name_ar, country_id_or_iso2, latitude, longitude, shipping_zone_id, is_active (0/1), cod_available (0/1)</code>
                <p class="mt-1 text-xs text-blue-600">shipping_zone_id, is_active, and cod_available are optional (defaults:
                    no zone, 1, 0)</p>
            </div>
            <form id="bulk-import-form" enctype="multipart/form-data" novalidate>
                @csrf
                <x-form.input name="file" label="CSV File" type="file" accept=".csv,.txt" required />
            </form>
            <div id="import-progress" class="hidden">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="animate-spin w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Importing…
                </div>
            </div>
            <div id="import-result" class="hidden text-sm"></div>
        </div>
        <div class="flex justify-end gap-2 px-4 pb-4">
            <button type="button" class="btn btn-ghost" data-modal-close="bulk-import-modal">Close</button>
            <button type="submit" form="bulk-import-form" id="btn-start-import" class="btn btn-primary">Import</button>
        </div>
    </x-modal>
@endsection

@push('scripts')
    @vite('resources/js/admin/cities.js')
@endpush