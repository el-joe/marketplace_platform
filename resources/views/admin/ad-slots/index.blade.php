@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Ad Slots')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">Ad Campaigns</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">Ad Slots</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Ad Slots</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage paid advertising placement slots.</p>
        </div>
        @can('create', App\Models\PaidAdSlot::class)
            <a href="{{ route('admin.ad-slots.create') }}" class="btn btn-primary btn-sm">+ New Slot</a>
        @endcan
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="Total Slots" :value="number_format($stats['total'])" iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card title="Available" :value="number_format($stats['available'])"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Unavailable" :value="number_format($stats['total'] - $stats['available'])"
            iconBg="bg-warning-100 text-warning-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Availability</label>
                <select id="slots-filter-available" class="form-input w-full text-sm">
                    <option value="">All</option>
                    <option value="1">Available only</option>
                    <option value="0">Unavailable only</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="slots-filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="slots-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Placement</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Pricing</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Base Rate</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Available</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

@endsection