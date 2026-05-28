@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/banners.js'])
@endpush

@section('title', 'Banners')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Banners</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage promotional banners across the platform.</p>
        </div>
        @can('create', App\Models\Banner::class)
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
                + New Banner
            </a>
        @endcan
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card title="Active" :value="number_format($stats['active'])" icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Scheduled" :value="number_format($stats['scheduled'])" icon="calendar"
            iconBg="bg-primary-100 text-primary-600" />
        <x-stat-card title="Expired" :value="number_format($stats['expired'])" icon="x-circle"
            iconBg="bg-danger-100 text-danger-600" />
        <x-stat-card title="Impressions Today" :value="number_format($stats['impressions_today'])" icon="eye"
            iconBg="bg-warning-100 text-warning-600" />
    </div>

    {{-- ─── Status tabs ─────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-4 border-b border-gray-200">
        @foreach(['' => 'All', 'active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Inactive'] as $val => $label)
            <button type="button"
                class="status-tab px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                            {{ $val === '' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                data-status="{{ $val }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Banner name…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Placement</label>
                <select id="filter-placement" class="form-input w-full text-sm">
                    <option value="">All placements</option>
                    @foreach($placements as $p)
                        <option value="{{ $p->code }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Device</label>
                <select id="filter-device" class="form-input w-full text-sm">
                    <option value="">All devices</option>
                    <option value="all">All</option>
                    <option value="desktop">Desktop</option>
                    <option value="mobile">Mobile</option>
                    <option value="app">App</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Starts from</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ends by</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="banners-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-3 w-16 text-xs font-medium text-gray-500 uppercase">Image</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Placement</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Device</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Audience</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Date Range</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Impr.</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">CTR</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Priority</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Delete Confirm Modal ────────────────────────────────────────────────── --}}
    <x-modal id="delete-modal" title="Delete Banner" size="sm">
        <p class="text-sm text-gray-600">
            Are you sure you want to delete <strong id="delete-banner-name"></strong>?
            This will also delete all associated images and cannot be undone.
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#delete-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-delete-btn" class="btn btn-danger">Delete</button>
        </div>
    </x-modal>

    {{-- ─── Duplicate Confirm Modal ─────────────────────────────────────────────── --}}
    <x-modal id="duplicate-modal" title="Duplicate Banner" size="sm">
        <p class="text-sm text-gray-600">
            Duplicate <strong id="duplicate-banner-name"></strong>? A copy will be created with status <em>inactive</em>.
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#duplicate-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-duplicate-btn" class="btn btn-primary">Duplicate</button>
        </div>
    </x-modal>

@endsection