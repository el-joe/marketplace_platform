@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/shipping-zones.js'])
@endpush

@section('title', 'Shipping Zones')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shipping Zones</h1>
            <p class="text-sm text-gray-500 mt-0.5">Define geographic zones for shipping rules.</p>
        </div>
        @can('countries.view')
            <button id="btn-add-zone" class="btn btn-primary btn-sm">+ Add Zone</button>
        @endcan
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-52">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->flag_emoji }} {{ $country->name_en }}</option>
                    @endforeach
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
            <table id="shipping-zones-table"
                   data-url="{{ route('admin.shipping-zones.datatable') }}"
                   class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4">Name</th>
                        <th class="pb-3 pr-4">Country</th>
                        <th class="pb-3 pr-4">Description</th>
                        <th class="pb-3 pr-4">Cities</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

    {{-- ─── Add / Edit Zone Modal ───────────────────────────────────────────── --}}
    <x-modal id="zone-modal" title="Shipping Zone" size="md">
        <form id="zone-form" novalidate>
            @csrf
            <input type="hidden" id="zone-id">
            <input type="hidden" id="zone-method" value="POST">

            <div class="space-y-4">
                <x-form-input
                    id="zone-name"
                    name="zone-name"
                    label="Zone Name"
                    required />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <select id="zone-country" class="form-input w-full text-sm" required>
                        <option value="">Select country…</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->flag_emoji }} {{ $country->name_en }}</option>
                        @endforeach
                    </select>
                </div>

                <x-form-input
                    id="zone-description"
                    name="zone-description"
                    label="Description" />

                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">Active</label>
                    <x-form-toggle id="zone-is-active" name="zone-is-active" :checked="true" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('zone-modal').modal?.close()"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="zone-submit-btn">Save Zone</button>
            </div>
        </form>
    </x-modal>

    {{-- ─── Assign Cities Modal ─────────────────────────────────────────────── --}}
    <x-modal id="assign-cities-modal" title="Assign Cities to Zone" size="lg">
        <div class="mb-4">
            <p class="text-sm text-gray-600 mb-2">Assigning cities to: <strong id="assign-zone-name" class="text-gray-900"></strong></p>
            <input type="text" id="assign-city-search" class="form-input w-full text-sm" placeholder="Search cities…">
        </div>
        <div id="assign-cities-list" class="max-h-80 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
            <div class="p-4 text-sm text-gray-400 text-center">Select a zone first…</div>
        </div>
        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('assign-cities-modal').modal?.close()"
                    class="btn btn-secondary">Cancel</button>
            <button type="button" id="btn-confirm-assign" class="btn btn-primary">Assign Selected</button>
        </div>
    </x-modal>

@endsection
