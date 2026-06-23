@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/warehouse-detail.js'])
@endpush

@section('title', $warehouse->name . ' — Warehouse')

@section('content')

    {{-- ─── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-1.5">
        <a href="{{ route('admin.warehouses.index') }}" class="hover:text-primary-600">Warehouses</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">{{ $warehouse->name }}</span>
    </nav>

    {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $warehouse->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $warehouse->code }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-secondary btn-sm">Edit</a>
            <button
                class="btn btn-sm js-toggle-active {{ $warehouse->is_active ? 'btn-ghost text-red-500' : 'btn-primary' }}"
                data-url="{{ route('admin.warehouses.toggle-active', $warehouse->id) }}"
                data-active="{{ (int) $warehouse->is_active }}">
                {{ $warehouse->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            {{ $errors->first('error') }}
        </div>
    @endif

    {{-- ─── Info Cards ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

        <x-card class="lg:col-span-2">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Details</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-500">Type</dt>
                    <dd class="mt-0.5 font-medium">
                        @php
                            $typeBadge = match ($warehouse->type) {
                                'platform_fbn' => 'bg-indigo-100 text-indigo-700',
                                'seller_owned' => 'bg-orange-100 text-orange-700',
                                default        => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeBadge }}">
                            {{ str_replace('_', ' ', $warehouse->type) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Country</dt>
                    <dd class="mt-0.5">{{ $warehouse->country->name_en ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Owner Vendor</dt>
                    <dd class="mt-0.5">
                        @if($warehouse->ownerVendor)
                            <a href="{{ route('admin.vendors.show', $warehouse->owner_vendor_id) }}"
                               class="text-primary-600 hover:underline">{{ $warehouse->ownerVendor->store_name }}</a>
                        @else
                            <span class="text-gray-400">Platform-owned</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Manager</dt>
                    <dd class="mt-0.5">{{ $warehouse->managerAdmin->name ?? '—' }}</dd>
                </div>
                @if($warehouse->latitude)
                    <div>
                        <dt class="text-xs text-gray-500">Location</dt>
                        <dd class="mt-0.5 font-mono text-xs">{{ $warehouse->latitude }}, {{ $warehouse->longitude }}</dd>
                    </div>
                @endif
                @if($warehouse->storage_rate_per_m3_price)
                    <div>
                        <dt class="text-xs text-gray-500">Storage Rate</dt>
                        <dd class="mt-0.5">{{ $warehouse->storage_rate_formatted }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-gray-500">Status</dt>
                    <dd class="mt-0.5">
                        @if($warehouse->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Capacity</h3>
            @php $barColor = $usedPct >= 90 ? 'bg-red-500' : ($usedPct >= 70 ? 'bg-warning-500' : 'bg-green-500'); @endphp
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-700 mb-1">
                    <span>{{ number_format($warehouse->used_capacity_m3, 1) }} m³ used</span>
                    <span class="font-medium {{ $usedPct >= 90 ? 'text-red-600' : '' }}">{{ $usedPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3">
                    <div class="h-3 rounded-full {{ $barColor }}" style="width: {{ min($usedPct, 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">of {{ number_format($warehouse->total_capacity_m3, 1) }} m³ total</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($inventoryStats['total_skus']) }}</p>
                    <p class="text-xs text-gray-500">SKUs</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($inventoryStats['total_units']) }}</p>
                    <p class="text-xs text-gray-500">Units</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-orange-700">{{ number_format($inventoryStats['low_stock']) }}</p>
                    <p class="text-xs text-orange-600">Low Stock</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-red-700">{{ number_format($inventoryStats['out_of_stock']) }}</p>
                    <p class="text-xs text-red-600">Out of Stock</p>
                </div>
            </div>
        </x-card>

    </div>

    {{-- ─── Tabs ────────────────────────────────────────────────────────────── --}}
    <div x-data="{ tab: 'inventory' }">

        <div class="flex gap-1 mb-4 border-b border-gray-200">
            <button @click="tab = 'inventory'"
                :class="tab === 'inventory' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                Inventory
            </button>
            <button @click="tab = 'movements'"
                :class="tab === 'movements' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                Recent Movements
            </button>
            <button @click="tab = 'transfers'"
                :class="tab === 'transfers' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                Transfers
            </button>
        </div>

        {{-- ─── Inventory Tab ─────────────────────────────────────────────── --}}
        <div x-show="tab === 'inventory'">
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Inventory</h3>
                    <div class="flex items-center gap-2">
                        <select id="inv-filter-status" class="form-input text-xs w-36">
                            <option value="">All statuses</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="inventory-table"
                        data-url="{{ route('admin.warehouses.inventory.datatable', $warehouse->id) }}"
                        class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                                <th class="pb-3 pr-4">Product / SKU</th>
                                <th class="pb-3 pr-4">Vendor</th>
                                <th class="pb-3 pr-4 text-right">On Hand</th>
                                <th class="pb-3 pr-4 text-right">Available</th>
                                <th class="pb-3 pr-4 text-right">Reserved</th>
                                <th class="pb-3 pr-4 text-right">Damaged</th>
                                <th class="pb-3 pr-4">Bin</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ─── Recent Movements Tab ──────────────────────────────────────── --}}
        <div x-show="tab === 'movements'" x-cloak>
            <x-card>
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Recent Movements (last 20)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                                <th class="pb-3 pr-4">Time</th>
                                <th class="pb-3 pr-4">Type</th>
                                <th class="pb-3 pr-4">Product</th>
                                <th class="pb-3 pr-4 text-right">Delta</th>
                                <th class="pb-3 pr-4 text-right">After</th>
                                <th class="pb-3 pr-4">Reason</th>
                                <th class="pb-3">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentMovements as $mv)
                                <tr>
                                    <td class="py-2.5 pr-4 text-xs text-gray-400 whitespace-nowrap">
                                        {{ $mv->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono
                                            {{ str_starts_with($mv->movement_type, 'transfer') ? 'bg-blue-50 text-blue-700' :
                                               ($mv->movement_type === 'damaged' ? 'bg-red-50 text-red-700' :
                                               ($mv->quantity_delta > 0 ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700')) }}">
                                            {{ $mv->movement_type }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-xs text-gray-700">
                                        {{ $mv->warehouseInventory?->vendorListing?->productVariant?->product?->name_en ?? '—' }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right tabular-nums font-medium {{ $mv->quantity_delta > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $mv->quantity_delta > 0 ? '+' : '' }}{{ number_format($mv->quantity_delta) }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right tabular-nums text-gray-600">
                                        {{ number_format($mv->quantity_after) }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-xs text-gray-500 max-w-xs truncate">
                                        {{ $mv->reason ?? '—' }}
                                    </td>
                                    <td class="py-2.5 text-xs text-gray-400">
                                        {{ $mv->createdBy?->name ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-400">No movements recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ─── Transfers Tab ─────────────────────────────────────────────── --}}
        <div x-show="tab === 'transfers'" x-cloak>
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Recent Transfers</h3>
                    <a href="{{ route('admin.warehouses.transfers.create') }}" class="btn btn-primary btn-xs">+ New Transfer</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                                <th class="pb-3 pr-4">Transfer #</th>
                                <th class="pb-3 pr-4">Direction</th>
                                <th class="pb-3 pr-4">Status</th>
                                <th class="pb-3 pr-4">Created</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transfers as $tf)
                                @php
                                    $isOutbound = $tf->source_warehouse_id === $warehouse->id;
                                    $statusBadge = match($tf->status) {
                                        'pending'    => 'bg-yellow-100 text-yellow-700',
                                        'in_transit' => 'bg-blue-100 text-blue-700',
                                        'received'   => 'bg-green-100 text-green-700',
                                        'cancelled'  => 'bg-gray-100 text-gray-500',
                                        default      => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-2.5 pr-4">
                                        <a href="{{ route('admin.warehouses.transfers.show', $tf->id) }}"
                                           class="font-mono text-xs text-primary-600 hover:underline">
                                            {{ $tf->transfer_number }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 pr-4 text-xs">
                                        @if($isOutbound)
                                            <span class="text-orange-600">▶ OUT → {{ $tf->destinationWarehouse?->name ?? '—' }}</span>
                                        @else
                                            <span class="text-green-600">◀ IN ← {{ $tf->sourceWarehouse?->name ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadge }}">
                                            {{ str_replace('_', ' ', $tf->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-xs text-gray-400">
                                        {{ $tf->created_at?->format('M d, Y') }}
                                    </td>
                                    <td class="py-2.5">
                                        <a href="{{ route('admin.warehouses.transfers.show', $tf->id) }}"
                                           class="btn btn-xs btn-ghost">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-gray-400">No transfers for this warehouse.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

    </div>

    {{-- ─── Adjust Inventory Modal ──────────────────────────────────────────── --}}
    <div id="adjust-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Adjust Inventory</h3>
                <button id="close-adjust-modal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form id="adjust-form" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" id="adjust-url" name="_adjust_url">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Movement Type</label>
                    <select name="movement_type" class="form-input w-full text-sm" required>
                        <option value="adjustment">Manual Adjustment</option>
                        <option value="receive">Receive</option>
                        <option value="return">Return</option>
                        <option value="count">Stock Count</option>
                        <option value="damaged">Mark Damaged</option>
                        <option value="write_off">Write-off</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Quantity Delta <span class="text-gray-400">(negative to remove)</span>
                    </label>
                    <input type="number" name="delta" class="form-input w-full text-sm" placeholder="e.g. +50 or -10" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Reason</label>
                    <input type="text" name="reason" class="form-input w-full text-sm" placeholder="Reason for adjustment…" required>
                </div>
                <div id="adjust-error" class="hidden text-sm text-red-600 bg-red-50 rounded p-2"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="cancel-adjust" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script type="module">
const INVENTORY_DATATABLE_URL = '{{ route('admin.warehouses.inventory.datatable', $warehouse->id) }}';
const TOGGLE_URL = '{{ route('admin.warehouses.toggle-active', $warehouse->id) }}';
</script>
@endpush
