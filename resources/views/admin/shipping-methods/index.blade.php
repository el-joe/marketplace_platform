@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', 'Shipping Methods')

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Shipping Methods</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage shipping options, carriers, rates and country settings.</p>
        </div>
    </div>

    {{-- ─── Tabs ────────────────────────────────────────────────────────────── --}}
    <div x-data="{ activeTab: window.location.hash.replace('#','') || 'methods' }">

        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-0 overflow-x-auto" aria-label="Shipping tabs">
                @foreach([
                    ['key' => 'methods',  'icon' => 'truck',           'label' => 'Shipping Methods'],
                    ['key' => 'carriers', 'icon' => 'building-office',  'label' => 'Carriers'],
                    ['key' => 'rates',    'icon' => 'currency-dollar',  'label' => 'Rates'],
                    ['key' => 'settings', 'icon' => 'cog-6-tooth',     'label' => 'Country Settings'],
                ] as $tab)
                    <button type="button"
                            @click="activeTab = '{{ $tab['key'] }}'; window.location.hash = '{{ $tab['key'] }}';"
                            :class="activeTab === '{{ $tab['key'] }}'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="flex items-center gap-1.5 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        <x-heroicon name="{{ $tab['icon'] }}" class="w-4 h-4" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ════════════════════════════════════════════════════════════════════
             TAB: Shipping Methods
             ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'methods'" x-cloak>
            <div class="mb-4 flex justify-end">
                <button type="button" id="btn-add-method"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                    <x-heroicon name="plus" class="w-4 h-4" />
                    New Shipping Method
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($methods as $method)
                    <x-card>
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $method->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $method->min_delivery_days }}–{{ $method->max_delivery_days }} days
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button"
                                        class="btn-toggle-method text-xs rounded-full px-2 py-0.5 font-semibold transition
                                               {{ $method->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                        data-id="{{ $method->id }}"
                                        data-active="{{ $method->is_active ? '1' : '0' }}">
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </div>
                        </div>
                        <div class="p-4 flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ $method->country_settings_count }} countr{{ $method->country_settings_count === 1 ? 'y' : 'ies' }}</span>
                            <div class="flex gap-1">
                                <button type="button"
                                        class="btn-edit-method p-1 rounded text-gray-400 hover:text-primary-600"
                                        data-id="{{ $method->id }}"
                                        data-row="{{ json_encode($method) }}">
                                    <x-heroicon name="pencil-square" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-empty-state title="No shipping methods" description="Create your first shipping method to get started." />
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════════
             TAB: Carriers
             ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'carriers'" x-cloak>
            <div class="mb-4 flex justify-end">
                <button type="button" id="btn-add-carrier"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                    <x-heroicon name="plus" class="w-4 h-4" />
                    New Carrier
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($carriers as $carrier)
                    <x-card>
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $carrier->name }}</p>
                                <code class="text-xs text-gray-400 font-mono">{{ $carrier->code }}</code>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button"
                                        class="btn-test-carrier text-xs rounded-lg px-2 py-0.5 font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200"
                                        data-code="{{ $carrier->code }}"
                                        data-id="{{ $carrier->id }}">
                                    Test
                                </button>
                                <button type="button"
                                        class="btn-toggle-carrier text-xs rounded-full px-2 py-0.5 font-semibold transition
                                               {{ $carrier->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                        data-id="{{ $carrier->id }}"
                                        data-active="{{ $carrier->is_active ? '1' : '0' }}">
                                    {{ $carrier->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </div>
                        </div>
                        <div class="p-4 space-y-2">
                            <div class="flex gap-2">
                                @if($carrier->supports_cod)
                                    <span class="rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200 px-2 py-0.5 text-xs font-medium">COD</span>
                                @endif
                                @if($carrier->supports_returns)
                                    <span class="rounded-full bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 text-xs font-medium">Returns</span>
                                @endif
                            </div>
                            @if($carrier->tracking_url_pattern)
                                <p class="text-xs text-gray-400 truncate" title="{{ $carrier->tracking_url_pattern }}">
                                    {{ $carrier->tracking_url_pattern }}
                                </p>
                            @endif
                            <div class="flex justify-between items-center pt-1">
                                <span class="carrier-test-status text-xs text-gray-400" id="carrier-status-{{ $carrier->id }}"></span>
                                <button type="button"
                                        class="btn-edit-carrier p-1 rounded text-gray-400 hover:text-primary-600"
                                        data-id="{{ $carrier->id }}"
                                        data-row="{{ json_encode($carrier->makeHidden(['credentials_encrypted'])) }}">
                                    <x-heroicon name="pencil-square" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-empty-state title="No carriers" description="Add a shipping carrier to configure rates." />
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════════
             TAB: Rates
             ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'rates'" x-cloak>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                {{-- Filters --}}
                <div class="flex flex-wrap gap-2">
                    <select id="filter-method"
                            class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                        <option value="">All Methods</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                    <select id="filter-carrier"
                            class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                        @endforeach
                    </select>
                    <select id="filter-zone"
                            class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                        <option value="">All Zones</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }} ({{ optional($zone->country)->iso_code_2 }})</option>
                        @endforeach
                    </select>
                    <button type="button" id="btn-apply-rate-filters"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <x-heroicon name="funnel" class="w-4 h-4" />
                        Apply
                    </button>
                </div>
                <button type="button" id="btn-add-rate"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                    <x-heroicon name="plus" class="w-4 h-4" />
                    New Rate
                </button>
            </div>

            <x-card padding="none">
                <table id="shipping-rates-table" class="table-base w-full">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Method</th>
                            <th>Carrier</th>
                            <th class="text-right">Base Fee</th>
                            <th class="text-right">Rate/kg</th>
                            <th class="text-right">Free Threshold</th>
                            <th class="text-right">COD Fee</th>
                            <th class="text-center">Status</th>
                            <th class="text-center w-16">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </x-card>
        </div>

        {{-- ════════════════════════════════════════════════════════════════════
             TAB: Country Settings
             ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'settings'" x-cloak>
            <div class="overflow-x-auto">
                <table class="table-base w-full">
                    <thead>
                        <tr>
                            <th>Country</th>
                            @foreach($methods as $method)
                                <th class="text-center">{{ $method->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $country)
                            <tr>
                                <td class="font-medium text-gray-900 whitespace-nowrap">
                                    {{ $country->name_en }}
                                    <span class="text-xs text-gray-400 ml-1">{{ $country->iso_code_2 }}</span>
                                </td>
                                @foreach($methods as $method)
                                    @php
                                        $setting = $country->countryShippingSettings->firstWhere('shipping_method_id', $method->id);
                                    @endphp
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn-toggle-shipping inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold transition
                                                       {{ $setting && $setting->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                                data-country-id="{{ $country->id }}"
                                                data-method-id="{{ $method->id }}"
                                                data-active="{{ ($setting && $setting->is_active) ? '1' : '0' }}">
                                            {{ ($setting && $setting->is_active) ? '✓' : '—' }}
                                        </button>
                                        @if($setting)
                                            <div class="mt-1">
                                                <input type="number"
                                                       placeholder="Free shipping threshold"
                                                       class="input-free-threshold w-24 text-xs rounded border border-gray-200 px-1.5 py-0.5 text-center focus:outline-none focus:ring-1 focus:ring-primary-300"
                                                       data-country-id="{{ $country->id }}"
                                                       data-method-id="{{ $method->id }}"
                                                       value="{{ $setting->free_shipping_threshold_cents ? number_format($setting->free_shipping_threshold_cents / 100, 2) : '' }}"
                                                       title="Free shipping threshold in currency units" />
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /x-data --}}

    {{-- ════════════════════════════════════════════════════════════════════════
         Modals
         ════════════════════════════════════════════════════════════════════════ --}}

    {{-- Shipping Method Modal --}}
    <x-modal id="method-modal" title="Shipping Method" size="md">
        <form id="method-form" novalidate>
            @csrf
            <input type="hidden" id="method-id">
            <input type="hidden" id="method-http" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form-input name="name" label="Name" placeholder="e.g. Standard Delivery" required />
                </div>
                <div>
                    <x-form-input name="code" label="Code" placeholder="e.g. standard" required />
                </div>
                <div>
                    <x-form-input name="description" label="Description" placeholder="Optional" />
                </div>
                <div>
                    <x-form-input name="min_delivery_days" label="Min Days" type="number" placeholder="1" required />
                </div>
                <div>
                    <x-form-input name="max_delivery_days" label="Max Days" type="number" placeholder="5" required />
                </div>
                <div class="sm:col-span-2">
                    <x-form-toggle name="is_active" label="Active" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="method-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Carrier Modal --}}
    <x-modal id="carrier-modal" title="Shipping Carrier" size="lg">
        <form id="carrier-form" novalidate>
            @csrf
            <input type="hidden" id="carrier-id">
            <input type="hidden" id="carrier-http" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-input name="name" label="Name" placeholder="e.g. Aramex" required />
                </div>
                <div>
                    <x-form-input name="code" label="Code" placeholder="e.g. aramex" required />
                </div>
                <div class="sm:col-span-2">
                    <x-form-input name="api_endpoint" label="API Endpoint" placeholder="https://api.example.com/" />
                </div>
                <div class="sm:col-span-2">
                    <x-form-input name="tracking_url_pattern" label="Tracking URL Pattern" placeholder="https://track.example.com/{tracking_number}" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Credentials (JSON)</label>
                    <textarea name="credentials" id="carrier-credentials" rows="4"
                              placeholder='{"account_number": "...", "password": "..."}'
                              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm font-mono text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Stored encrypted. Leave blank to keep existing credentials.</p>
                </div>
                <div class="flex items-center gap-6">
                    <x-form-toggle name="supports_cod" label="Supports COD" />
                    <x-form-toggle name="supports_returns" label="Supports Returns" />
                </div>
                <div>
                    <x-form-toggle name="is_active" label="Active" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="carrier-form" class="btn-primary">Save Carrier</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Rate Modal --}}
    <x-modal id="rate-modal" title="Shipping Rate" size="lg">
        <form id="rate-form" novalidate>
            @csrf
            <input type="hidden" id="rate-id">
            <input type="hidden" id="rate-http" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-select name="shipping_method_id" label="Shipping Method" required>
                        <option value="">— Select method —</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="destination_zone_id" label="Destination Zone" required>
                        <option value="">— Select zone —</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }} ({{ optional($zone->country)->iso_code_2 }})</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="carrier_id" label="Carrier (optional)">
                        <option value="">— Any carrier —</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="origin_zone_id" label="Origin Zone (optional)">
                        <option value="">— Any origin —</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>

                {{-- Money fields (stored as cents) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Fee <span class="text-danger-500">*</span></label>
                    <input type="number" id="rate-base-fee-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="base_fee" id="rate-base-fee" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rate per kg <span class="text-danger-500">*</span></label>
                    <input type="number" id="rate-per-kg-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="rate_per_kg" id="rate-per-kg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Free Shipping Threshold</label>
                    <input type="number" id="rate-free-threshold-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="free_shipping_threshold" id="rate-free-threshold" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">COD Extra Fee</label>
                    <input type="number" id="rate-cod-fee-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="cod_extra_fee" id="rate-cod-fee" />
                </div>
                <div>
                    <x-form-input name="min_weight_grams" label="Min Weight (grams)" type="number" placeholder="0" />
                </div>
                <div>
                    <x-form-input name="volumetric_divisor" label="Volumetric Divisor" type="number" placeholder="5000" />
                </div>
                <div class="sm:col-span-2">
                    <x-form-toggle name="is_active" label="Active" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="rate-form" class="btn-primary">Save Rate</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Confirm Delete Rate Modal --}}
    <x-modal id="delete-rate-modal" title="Delete Shipping Rate" size="sm">
        <p class="text-sm text-gray-600">Delete this shipping rate? This action cannot be undone.</p>
        <input type="hidden" id="delete-rate-id" />
        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">Cancel</button>
            <button type="button" id="btn-confirm-delete-rate" class="btn-danger">Delete</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/shipping-methods.js'])
@endpush
