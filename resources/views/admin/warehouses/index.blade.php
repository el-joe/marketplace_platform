@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/warehouses.js'])
@endpush

@section('title', 'Warehouses')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Warehouses</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage platform fulfillment and seller-owned warehouses.</p>
        </div>
        @if(auth('admin')->user()->can('warehouses.view'))
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary btn-sm">+ Add Warehouse</a>
        @endif
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    @php
        $totalPct = $stats['total_capacity'] > 0
            ? round($stats['used_capacity'] / $stats['total_capacity'] * 100, 1)
            : 0;
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="Platform (FBN)" :value="number_format($stats['platform'])"
            iconBg="bg-indigo-100 text-indigo-600" />
        <x-stat-card title="Seller-Owned" :value="number_format($stats['seller_owned'])"
            iconBg="bg-orange-100 text-orange-600" />
        <x-stat-card title="Third-Party" :value="number_format($stats['third_party'])" iconBg="bg-gray-100 text-gray-500" />
        <x-stat-card title="Total Capacity" :value="number_format($stats['total_capacity'], 1) . ' m³'"
            iconBg="bg-teal-100 text-teal-600" :subtitle="$totalPct . '% used'" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search name / code</label>
                <input type="text" id="search-input" class="form-input w-full text-sm"
                    placeholder="Warehouse name or code…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select id="filter-type" class="form-input w-full text-sm">
                    <option value="">All types</option>
                    <option value="platform_fbn">Platform FBN</option>
                    <option value="seller_owned">Seller-Owned</option>
                    <option value="third_party">Third-Party</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">Reset</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="warehouses-table" data-url="{{ route('admin.warehouses.datatable') }}" class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4">Name</th>
                        <th class="pb-3 pr-4">Code</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Country</th>
                        <th class="pb-3 pr-4">Vendor</th>
                        <th class="pb-3 pr-4">Manager</th>
                        <th class="pb-3 pr-4 min-w-[120px]">Capacity</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection