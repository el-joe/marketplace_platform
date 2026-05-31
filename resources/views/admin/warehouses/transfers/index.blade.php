@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/warehouses.js'])
@endpush

@section('title', 'Inventory Transfers')

@section('content')

    {{-- ─── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-1.5">
        <a href="{{ route('admin.warehouses.index') }}" class="hover:text-primary-600">Warehouses</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">Transfers</span>
    </nav>

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Inventory Transfers</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track stock movements between warehouses.</p>
        </div>
        @if(auth('admin')->user()->can('warehouses.view'))
            <a href="{{ route('admin.warehouses.transfers.create') }}" class="btn btn-primary btn-sm">+ New Transfer</a>
        @endif
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="Pending" :value="number_format($stats['pending'])" iconBg="bg-yellow-100 text-yellow-600" />
        <x-stat-card title="In Transit" :value="number_format($stats['in_transit'])" iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="Received" :value="number_format($stats['received'])" iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="Cancelled" :value="number_format($stats['cancelled'])" iconBg="bg-gray-100 text-gray-500" />
    </div>

    {{-- ─── Filter Bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_transit">In Transit</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Source Warehouse</label>
                <select id="filter-source" class="form-input w-full text-sm">
                    <option value="">Any</option>
                    @foreach($warehouses as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Destination Warehouse</label>
                <select id="filter-dest" class="form-input w-full text-sm">
                    <option value="">Any</option>
                    @foreach($warehouses as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">Reset</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="transfers-table" data-url="{{ route('admin.warehouses.transfers.datatable') }}"
                class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4">Transfer #</th>
                        <th class="pb-3 pr-4">Source</th>
                        <th class="pb-3 pr-4">Destination</th>
                        <th class="pb-3 pr-4">Vendor</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 pr-4">Created</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection