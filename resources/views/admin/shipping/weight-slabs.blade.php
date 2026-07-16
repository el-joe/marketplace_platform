@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js'])
@endpush

@section('title', 'Shipping Weight Slabs')

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shipping Weight Slabs</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure extra fees per weight range for each shipping method and country.</p>
        </div>
        <button type="button" id="btn-add-slab"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" />
            Add Slab
        </button>
    </div>

    {{-- ─── Filter Bar ─────────────────────────────────────────────────────── --}}
    <x-card padding="sm" class="mb-4">
        <form id="weight-slabs-table-filter-form" class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Method</label>
                <select name="shipping_method_id" id="filter-shipping-method" data-select2-init
                        class="block w-full rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                    <option value="">All Methods</option>
                    @foreach($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-56">
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select name="country_id" id="filter-country" data-select2-init
                        class="block w-full rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" data-currency="{{ $country->currency_code }}">{{ $country->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" id="btn-apply-slab-filters"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <x-heroicon name="funnel" class="w-4 h-4" />
                    Apply
                </button>
            </div>
        </form>
    </x-card>

    {{-- ─── DataTable ──────────────────────────────────────────────────────── --}}
    <x-card padding="none">
        <table id="weight-slabs-table" class="table-base w-full">
            <thead>
                <tr>
                    <th>Shipping Method</th>
                    <th>Country</th>
                    <th>Weight Range</th>
                    <th class="text-end">Extra Fee</th>
                    <th class="text-center">Status</th>
                    <th class="text-center w-24">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ─── Reference Table ────────────────────────────────────────────────── --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-900 mb-2">All Slabs Reference</h2>
        <p class="text-sm text-gray-500 mb-3">All slabs for the current filter, sorted by minimum weight.</p>
        <x-card padding="none">
            <div class="overflow-x-auto">
                <table class="table-base w-full">
                    <thead>
                        <tr>
                            <th>Shipping Method</th>
                            <th>Country</th>
                            <th>Weight Range</th>
                            <th class="text-end">Extra Fee</th>
                        </tr>
                    </thead>
                    <tbody id="weight-slabs-reference-body">
                        <tr><td colspan="4" class="text-center text-sm text-gray-400 py-6">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         Add / Edit Modal
         ════════════════════════════════════════════════════════════════════ --}}
    <x-modal id="slab-modal" title="Weight Slab" size="lg">
        <form id="slab-form" novalidate x-data="{ openEnded: false }">
            @csrf
            <input type="hidden" id="slab-id">
            <input type="hidden" id="slab-http" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-select name="shipping_method_id" label="Shipping Method" :select2="true" required>
                        <option value="">Select method…</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="country_id" id="slab-country" label="Country" :select2="true" required>
                        <option value="">Select country…</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" data-currency="{{ $country->currency_code }}">{{ $country->name_en }}</option>
                        @endforeach
                    </x-form-select>
                </div>

                <div>
                    <x-form-input name="min_weight_grams_kg" id="slab-min-weight-kg" type="number" step="0.001" min="0"
                                  label="Min Weight (kg)" placeholder="0.000" required />
                    <input type="hidden" name="min_weight_grams" id="slab-min-weight" />
                </div>
                <div>
                    <x-form-input name="max_weight_grams_kg" id="slab-max-weight-kg" type="number" step="0.001" min="0"
                                  label="Max Weight (kg)" placeholder="5.000" x-bind:disabled="openEnded" />
                    <input type="hidden" name="max_weight_grams" id="slab-max-weight" />
                    <label class="mt-1.5 flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" id="slab-open-ended" x-model="openEnded"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                        Open-ended (no maximum)
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra Fee <span class="text-danger-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="number" id="slab-fee-display" step="0.01" min="0" placeholder="0.00"
                               class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                        <span id="slab-currency-label" class="inline-flex items-center px-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-500">—</span>
                    </div>
                    <input type="hidden" name="extra_fee" id="slab-fee-input" />
                </div>

                <div>
                    <x-form-toggle name="is_active" label="Active" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="slab-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Confirm Delete Modal --}}
    <x-modal id="delete-slab-modal" title="Delete Weight Slab" size="sm">
        <p class="text-sm text-gray-600">Are you sure you want to delete this weight slab? This cannot be undone.</p>
        <input type="hidden" id="delete-slab-id" />
        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">Cancel</button>
            <button type="button" id="btn-confirm-delete-slab" class="btn-danger">Delete</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/admin/shipping-weight-slabs.js'])
    <script type="module">
        window.SHIPPING_WEIGHT_SLABS_ROUTES = {
            datatable: @json(route('admin.shipping.weight-slabs.datatable')),
            store: @json(route('admin.shipping.weight-slabs.store')),
            update: @json(route('admin.shipping.weight-slabs.update', ['slab' => '__ID__'])),
            destroy: @json(route('admin.shipping.weight-slabs.destroy', ['slab' => '__ID__'])),
        };
    </script>
@endpush
