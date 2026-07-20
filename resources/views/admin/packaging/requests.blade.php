@extends('layouts.admin')

@section('title', 'Packaging Orders')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('content')
<div class="p-6 space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Packaging Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review and process vendor packaging supply requests.</p>
        </div>
        <a href="{{ route('admin.packaging.catalog') }}" class="btn btn-secondary text-sm">Manage Catalog</a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card title="Pending" :value="$stats['pending']" icon="clock" iconBg="bg-amber-100 text-amber-600" />
        <x-stat-card title="In Transit" :value="$stats['in_transit']" icon="truck" iconBg="bg-indigo-100 text-indigo-600" />
        <x-stat-card title="Delivered This Month" :value="$stats['delivered_this_month']" icon="check-circle" iconBg="bg-emerald-100 text-emerald-600" />
    </div>

    @if($stats['revenue_this_month']->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Revenue This Month</p>
        <div class="flex flex-wrap gap-4">
            @foreach($stats['revenue_this_month'] as $row)
                <div class="text-sm">
                    <span class="font-semibold text-gray-900">{{ number_format($row['total'] / 100, 2) }}</span>
                    <span class="text-gray-500">{{ $row['currency'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- DataTable --}}
    @php
        $columns = [
            ['title' => 'Request #', 'data' => 'request_number', 'name' => 'request_number'],
            ['title' => 'Vendor', 'data' => 'vendor', 'name' => 'vendor', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'searchable' => false],
            ['title' => 'Items', 'data' => 'items_count', 'name' => 'items_count', 'orderable' => false, 'searchable' => false],
            ['title' => 'Total', 'data' => 'total_cost', 'name' => 'total_cost', 'searchable' => false],
            ['title' => 'Delivery Fee', 'data' => 'delivery_fee', 'name' => 'delivery_fee', 'searchable' => false],
            ['title' => 'Grand Total', 'data' => 'grand_total', 'name' => 'grand_total', 'orderable' => false, 'searchable' => false],
            ['title' => 'Currency', 'data' => 'currency', 'name' => 'currency', 'orderable' => false, 'searchable' => false],
            ['title' => 'Date', 'data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
        ];

        $filters = [
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'pending' => 'Pending', 'approved' => 'Approved', 'shipped' => 'Shipped',
                    'delivered' => 'Delivered', 'rejected' => 'Rejected',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'vendor_id',
                'label' => 'Vendor',
                'options' => $vendors->pluck('store_name', 'id')->toArray(),
            ],
            [
                'type' => 'date_range',
                'name' => 'date',
                'label' => 'Date Range',
            ],
        ];
    @endphp

    <x-table.datatable id="packaging-requests-table" url="{{ route('admin.packaging.requests.datatable') }}"
        :columns="$columns" :filters="$filters" :page-length="25" />
</div>
@endsection
