@extends('layouts.admin')

@section('title', $warehouse->name . ' — Warehouse')

@section('content')

    {{-- ─── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-1.5">
        <a href="{{ route('admin.warehouses.index') }}" class="hover:text-primary-600">Warehouses</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">{{ $warehouse->name }}</span>
    </nav>

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

    {{-- ─── Info cards ──────────────────────────────────────────────────────── --}}
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
                                default => 'bg-gray-100 text-gray-600',
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
                                class="text-primary-600 hover:underline">
                                {{ $warehouse->ownerVendor->store_name }}
                            </a>
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
                        <dd class="mt-0.5">
                            {{ number_format($warehouse->storage_rate_per_m3_price / 100, 2) }}
                            {{ $warehouse->storage_currency }}/m³
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-gray-500">Status</dt>
                    <dd class="mt-0.5">
                        @if($warehouse->is_active)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        {{-- ─── Capacity ─────────────────────────────────────────────────────── --}}
        <x-card>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Capacity</h3>

            @php
                $barColor = $usedPct >= 90 ? 'bg-red-500' : ($usedPct >= 70 ? 'bg-warning-500' : 'bg-green-500');
            @endphp

            <div class="mb-3">
                <div class="flex justify-between text-sm text-gray-700 mb-1">
                    <span>{{ number_format($warehouse->used_capacity_m3, 1) }} m³ used</span>
                    <span class="font-medium {{ $usedPct >= 90 ? 'text-red-600' : '' }}">{{ $usedPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3">
                    <div class="h-3 rounded-full {{ $barColor }}" style="width: {{ min($usedPct, 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">of {{ number_format($warehouse->total_capacity_m3, 1) }} m³ total</p>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($inventoryStats['total_skus']) }}</p>
                    <p class="text-xs text-gray-500">SKUs</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($inventoryStats['total_items']) }}</p>
                    <p class="text-xs text-gray-500">Items</p>
                </div>
                @if($inventoryStats['low_stock'] > 0)
                    <div class="bg-warning-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-warning-700">{{ number_format($inventoryStats['low_stock']) }}</p>
                        <p class="text-xs text-warning-600">Low Stock</p>
                    </div>
                @endif
                @if($inventoryStats['out_of_stock'] > 0)
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-red-700">{{ number_format($inventoryStats['out_of_stock']) }}</p>
                        <p class="text-xs text-red-600">Out of Stock</p>
                    </div>
                @endif
            </div>
        </x-card>

    </div>

    {{-- ─── Inventory Table ─────────────────────────────────────────────────── --}}
    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-800">Inventory</h3>
            <span class="text-xs text-gray-500">{{ $inventory->total() }} SKUs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-2 pr-4">Listing</th>
                        <th class="pb-2 pr-4">Vendor</th>
                        <th class="pb-2 pr-4">Bin</th>
                        <th class="pb-2 pr-4 text-right">On Hand</th>
                        <th class="pb-2 pr-4 text-right">Reserved</th>
                        <th class="pb-2 pr-4 text-right">Available</th>
                        <th class="pb-2 pr-4 text-right">Inbound</th>
                        <th class="pb-2 pr-4 text-right">Damaged</th>
                        <th class="pb-2 text-right">Reorder at</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($inventory as $item)
                        @php
                            $isLow = $item->quantity_available <= $item->reorder_point && $item->quantity_available > 0;
                            $isOut = $item->quantity_available <= 0;
                            $rowClass = $isOut ? 'bg-red-50' : ($isLow ? 'bg-yellow-50' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="py-2.5 pr-4">
                                <span class="font-medium text-gray-800 text-xs">
                                    {{ $item->vendorListing->sku ?? $item->vendor_listing_id }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 text-xs text-gray-600">
                                {{ $item->vendorListing->vendor->store_name ?? '—' }}
                            </td>
                            <td class="py-2.5 pr-4 font-mono text-xs text-gray-500">
                                {{ $item->bin_location ?? '—' }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums text-gray-700">
                                {{ number_format($item->quantity_on_hand) }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums text-gray-500">
                                {{ number_format($item->quantity_reserved) }}
                            </td>
                            <td
                                class="py-2.5 pr-4 text-right tabular-nums font-medium {{ $isOut ? 'text-red-600' : ($isLow ? 'text-yellow-700' : 'text-gray-900') }}">
                                {{ number_format($item->quantity_available) }}
                            </td>
                            <td class="py-2.5 pr-4 text-right tabular-nums text-teal-600">
                                {{ $item->quantity_inbound > 0 ? number_format($item->quantity_inbound) : '—' }}
                            </td>
                            <td
                                class="py-2.5 pr-4 text-right tabular-nums {{ $item->quantity_damaged > 0 ? 'text-red-500' : 'text-gray-400' }}">
                                {{ $item->quantity_damaged > 0 ? number_format($item->quantity_damaged) : '—' }}
                            </td>
                            <td class="py-2.5 text-right tabular-nums text-xs text-gray-400">
                                {{ $item->reorder_point > 0 ? number_format($item->reorder_point) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-sm text-gray-400">
                                No inventory records for this warehouse.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($inventory->hasPages())
            <div class="mt-4 pt-4 border-t border-gray-100">
                {{ $inventory->links() }}
            </div>
        @endif
    </x-card>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle active button on show page
            document.querySelector('.js-toggle-active')?.addEventListener('click', async function () {
                const btn = this;
                const isActive = parseInt(btn.dataset.active, 10);
                if (!confirm(isActive ? 'Deactivate this warehouse?' : 'Activate this warehouse?')) return;

                try {
                    const res = await fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({}),
                    });
                    const json = await res.json();
                    if (!res.ok) throw json;
                    window.Toast?.success(json.message);
                    setTimeout(() => location.reload(), 800);
                } catch (e) {
                    window.Toast?.error(e.message ?? 'Request failed.');
                }
            });
        });
    </script>
@endpush