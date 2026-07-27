@extends('layouts.admin')

@section('title', $listing->productVariant->product->name_en)

@push('styles')
    @vite(['resources/js/components/flatpickr.js'])
@endpush

@section('content')
    <div x-data="adminListingShow(@js([
            'listingId' => $listing->id,
            'currentStatus' => $listing->status->value,
            'urls' => [
                'updateStatus' => route('admin.admin-product-listings.update-status', $listing),
                'adjustStock' => route('admin.admin-product-listings.adjust-stock', $listing),
                'saveShippingRule' => route('admin.admin-product-listings.save-shipping-rule', $listing),
                'destroy' => route('admin.admin-product-listings.destroy', $listing),
            ],
            'reviewApproveUrl' => route('admin.reviews.approve', ['review' => '__ID__']),
            'reviewRejectUrl' => route('admin.reviews.reject', ['review' => '__ID__']),
            'reviewsUrl' => route('admin.admin-product-listings.reviews.index', $listing),
        ]))" class="p-6 space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $listing->productVariant->product->name_en }}</h1>
                <p class="text-sm text-gray-500 mt-0.5 font-mono">
                    {{ $listing->productVariant->sku }}
                    @if($listing->productVariant->variant_name) · {{ $listing->productVariant->variant_name }} @endif
                    · {{ $listing->country->name_en }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.admin-product-listings.edit', $listing) }}" class="btn btn-secondary btn-sm">
                    Edit Listing
                </a>
                <a href="{{ route('admin.admin-product-listings.index') }}" class="btn btn-ghost btn-sm">
                    {{ __('common.back') }}
                </a>
            </div>
        </div>

        @php $customerUrl = "/products/{$listing->productVariant->id}/{$listing->id}"; @endphp
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide flex-shrink-0">Customer URL</p>
            <input type="text" readonly value="{{ $customerUrl }}"
                   class="flex-1 border border-green-200 bg-white rounded-lg px-3 py-1.5 text-xs font-mono text-gray-700 focus:outline-none">
            <button type="button" class="js-copy inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-700 border border-green-300 rounded-lg hover:bg-green-100 flex-shrink-0"
                    data-value="{{ $customerUrl }}">
                Copy URL
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

            {{-- ── Main column ─────────────────────────────────────────────── --}}
            <div class="lg:col-span-3 space-y-4">

                {{-- Tabs --}}
                <div class="border-b border-gray-200 flex gap-6 overflow-x-auto">
                    @php
                        $tabs = [
                            'overview' => 'Overview',
                            'inventory' => 'Inventory',
                            'flash-sales' => 'Flash Sales',
                            'campaigns' => 'Marketer Campaigns',
                            'reviews' => 'Reviews',
                        ];
                        if ($canViewCost) { $tabs['cost-reference'] = 'Cost Reference'; }
                        $tabs['shipping-rules'] = 'Shipping Rules';
                    @endphp
                    @foreach($tabs as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'; $nextTick(() => window.dispatchEvent(new Event('resize')))"
                            :class="tab === '{{ $key }}' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="pb-3 border-b-2 text-sm font-medium transition-colors whitespace-nowrap">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- ── Tab: Overview ────────────────────────────────────────── --}}
                <div x-show="tab === 'overview'" class="space-y-4">

                    {{-- Product info --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Product</h3>
                        <div class="flex items-start gap-4">
                            @php $img = $listing->productVariant->product->images->firstWhere('is_primary', true) ?? $listing->productVariant->product->images->first(); @endphp
                            <img src="{{ $img ? \Illuminate\Support\Facades\Storage::disk($img->disk)->url($img->path) : asset('images/placeholder.png') }}"
                                 alt="" class="w-20 h-20 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm flex-1">
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Product</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $listing->productVariant->product->name_en }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Variant</dt>
                                    <dd class="mt-1 text-gray-800">{{ $listing->productVariant->variant_name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">SKU</dt>
                                    <dd class="mt-1 font-mono text-gray-800">{{ $listing->productVariant->sku }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Brand</dt>
                                    <dd class="mt-1 text-gray-800">{{ $listing->productVariant->product->brand?->name_en ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Category</dt>
                                    <dd class="mt-1 text-gray-800">{{ $listing->productVariant->product->category?->name_en ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Pricing</h3>
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Price</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ number_format($listing->price) }} {{ $listing->currency }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Compare-at Price</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->compare_at_price !== null ? number_format($listing->compare_at_price) . ' ' . $listing->currency : '—' }}</dd>
                            </div>
                            @if($canViewCost)
                                <div>
                                    <dt class="text-gray-400 text-xs uppercase tracking-wide">Cost Price</dt>
                                    <dd class="mt-1 text-gray-800">{{ $listing->cost_price !== null ? number_format($listing->cost_price) . ' ' . $listing->currency : '—' }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Shipping Cost</dt>
                                <dd class="mt-1 text-gray-800">{{ number_format($listing->shipping_cost) }} {{ $listing->currency }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Commission --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Commission</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Type</dt>
                                <dd class="mt-1 text-gray-800">{{ ucfirst($listing->commission_type->value) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Value</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->commission_value }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Fulfillment --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Fulfillment</h3>
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Fulfillment Type</dt>
                                <dd class="mt-1 text-gray-800">{{ ucfirst($listing->fulfillment_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Payment Options</dt>
                                <dd class="mt-1 text-gray-800">{{ ['cod_only' => 'COD only', 'electronic_only' => 'Electronic only', 'both' => 'COD + Electronic'][$listing->payment_options] ?? $listing->payment_options }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Primary Shipping Method</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->primaryShippingMethod?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Global Shipping</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->is_global_shipping ? 'Yes' : 'No' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Flags --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Flags</h3>
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Featured in Nawy</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->featured_in_nawy ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Nawy Category</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->nawyCategory?->name_en ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Exclusive</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->is_exclusive ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Available for Vendors / Marketers</dt>
                                <dd class="mt-1 text-gray-800">
                                    {{ $listing->available_for_vendors ? 'Yes' : 'No' }} / {{ $listing->available_for_marketers ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Buy Box Eligible</dt>
                                <dd class="mt-1 text-gray-800">{{ $listing->buy_box_eligible ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 text-xs uppercase tracking-wide">Status</dt>
                                <dd class="mt-1">
                                    @php
                                        $sc = ['active' => 'bg-green-100 text-green-800', 'paused' => 'bg-yellow-100 text-yellow-700', 'out_of_stock' => 'bg-orange-100 text-orange-700', 'archived' => 'bg-gray-100 text-gray-500'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$listing->status->value] ?? '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $listing->status->value)) }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- ── Tab: Inventory ───────────────────────────────────────── --}}
                <div x-show="tab === 'inventory'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Warehouse Inventory</h3>
                        <button type="button" @click="openAdjustStock()" class="btn btn-primary btn-sm">Adjust Stock</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-base w-full">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Reserved</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Inbound</th>
                                    <th class="text-end">Damaged</th>
                                    <th>Bin Location</th>
                                    <th class="text-end">Reorder Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($listing->warehouseInventories as $inv)
                                    <tr class="border-b border-gray-100">
                                        <td>{{ $inv->warehouse?->name ?? '—' }}</td>
                                        <td class="text-end">{{ $inv->quantity_on_hand }}</td>
                                        <td class="text-end">{{ $inv->quantity_reserved }}</td>
                                        <td class="text-end font-medium">{{ $inv->quantity_available }}</td>
                                        <td class="text-end">{{ $inv->quantity_inbound }}</td>
                                        <td class="text-end">{{ $inv->quantity_damaged }}</td>
                                        <td>{{ $inv->bin_location ?? '—' }}</td>
                                        <td class="text-end">{{ $inv->reorder_point ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-gray-400 py-6">No inventory records yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="px-5 py-2 text-xs text-gray-400">Available quantity is calculated automatically (on hand − reserved) and cannot be edited directly.</p>
                </div>

                {{-- ── Tab: Flash Sales ─────────────────────────────────────── --}}
                <div x-show="tab === 'flash-sales'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    <table class="table-base w-full">
                        <thead>
                            <tr>
                                <th>Flash Sale</th>
                                <th class="text-end">Flash Price</th>
                                <th class="text-end">Original Price</th>
                                <th class="text-end">Discount %</th>
                                <th>Status</th>
                                <th class="text-end">Sold / Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($listing->flashSaleSubmissions as $sub)
                                <tr class="border-b border-gray-100">
                                    <td>
                                        @if($sub->flashSale)
                                            <a href="{{ route('admin.flash-sales.show', $sub->flashSale) }}" class="text-primary-600 hover:underline">{{ $sub->flashSale->name_en }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($sub->flash_price / 100, 2) }} {{ $sub->flash_price_currency }}</td>
                                    <td class="text-end">{{ number_format($sub->original_price / 100, 2) }} {{ $sub->flash_price_currency }}</td>
                                    <td class="text-end">{{ $sub->calculated_discount_pct !== null ? $sub->calculated_discount_pct . '%' : '—' }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            {{ \App\Models\FlashSaleSubmission::STATUS_LABELS[$sub->status->value] ?? $sub->status->value }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $sub->quantity_sold }} / {{ $sub->max_quantity_total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-gray-400 py-6">No flash sale submissions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Tab: Marketer Campaigns ──────────────────────────────── --}}
                <div x-show="tab === 'campaigns'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    <table class="table-base w-full">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th class="text-end">Commission Override</th>
                                <th class="text-end">Position</th>
                                <th>Campaign Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($listing->marketerCampaignProducts as $mcp)
                                <tr class="border-b border-gray-100">
                                    <td>{{ $mcp->campaign?->name ?? '—' }}</td>
                                    <td class="text-end">{{ $mcp->commission_override !== null ? $mcp->commission_override . '%' : '—' }}</td>
                                    <td class="text-end">{{ $mcp->position ?? '—' }}</td>
                                    <td>{{ $mcp->campaign?->status ? ucfirst(str_replace('_', ' ', $mcp->campaign->status->value)) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-gray-400 py-6">Not linked to any marketer campaigns.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Tab: Reviews ─────────────────────────────────────────── --}}
                <div x-show="tab === 'reviews'" x-cloak
                     x-init="$watch('tab', (v) => { if (v === 'reviews' && !reviewsLoaded) loadReviews(); })"
                     class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    <div x-ref="reviewsContainer" x-html="reviewsHtml"></div>
                </div>

                {{-- ── Tab: Cost References (guarded) ───────────────────────── --}}
                @if($canViewCost)
                    <div x-show="tab === 'cost-reference'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
                        @include('admin.admin-product-listings.partials.cost-references')
                    </div>
                @endif

                {{-- ── Tab: Shipping Rules ──────────────────────────────────── --}}
                <div x-show="tab === 'shipping-rules'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Marketplace Shipping Rule</h3>
                    <form @submit.prevent="saveShippingRule()">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="shippingForm.requires_special_vehicle" class="form-checkbox">
                                Requires Special Vehicle
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="shippingForm.requires_refrigeration" class="form-checkbox">
                                Requires Refrigeration
                            </label>
                            <div>
                                <label class="label-sm block mb-1">Max Weight (kg)</label>
                                <input type="number" step="0.01" min="0" x-model.number="shippingForm.max_weight_kg" class="form-input w-full text-sm">
                            </div>
                            <div>
                                <label class="label-sm block mb-1">Max Dimensions (cm)</label>
                                <input type="text" x-model="shippingForm.max_dimensions_cm" placeholder="LxWxH" class="form-input w-full text-sm">
                            </div>
                            <div>
                                <label class="label-sm block mb-1">Commission Type</label>
                                <select x-model="shippingForm.commission_type" class="form-input w-full text-sm">
                                    <option value="fixed">Fixed</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                            </div>
                            <div>
                                <label class="label-sm block mb-1">Commission Value</label>
                                <input type="number" step="0.01" min="0" x-model.number="shippingForm.commission_value" class="form-input w-full text-sm">
                            </div>
                            <div>
                                <label class="label-sm block mb-1">Extra Delivery Fee (cents)</label>
                                <input type="number" min="0" x-model.number="shippingForm.extra_delivery_fee" class="form-input w-full text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label-sm block mb-1">Special Handling Notes</label>
                                <textarea x-model="shippingForm.special_handling_notes" rows="2" class="form-input w-full text-sm"></textarea>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" :disabled="savingShippingRule" class="btn btn-primary btn-sm">
                                <span x-text="savingShippingRule ? 'Saving…' : 'Save Shipping Rule'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Quick Status</h3>
                    <select x-model="currentStatus" @change="changeStatus()" class="form-input w-full text-sm">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                    <a href="{{ route('admin.admin-product-listings.edit', $listing) }}" class="btn btn-secondary btn-sm w-full justify-center">Edit Listing</a>
                    <button type="button" @click="confirmDelete = true" class="btn btn-danger btn-sm w-full justify-center">Delete Listing</button>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-sm">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Audit Trail</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-gray-400 text-xs">Created By</dt>
                            <dd class="text-gray-800">{{ $listing->createdByAdmin?->name ?? 'System' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 text-xs">Created At</dt>
                            <dd class="text-gray-800">{{ $listing->created_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 text-xs">Last Updated</dt>
                            <dd class="text-gray-800">{{ $listing->updated_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ── Adjust Stock Modal ──────────────────────────────────────────── --}}
        <div x-show="adjustStockOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5" @click.outside="adjustStockOpen = false">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Adjust Stock</h3>
                <div class="space-y-3">
                    <div>
                        <label class="label-sm block mb-1">Warehouse</label>
                        <select x-model="adjustForm.warehouse_id" class="form-input w-full text-sm">
                            <option value="">Select warehouse…</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-sm block mb-1">Adjustment (+/-)</label>
                        <input type="number" x-model.number="adjustForm.adjustment" class="form-input w-full text-sm" placeholder="e.g. 10 or -5">
                    </div>
                    <div>
                        <label class="label-sm block mb-1">Reason</label>
                        <textarea x-model="adjustForm.reason" rows="2" class="form-input w-full text-sm"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="adjustStockOpen = false" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="button" @click="submitAdjustStock()" :disabled="savingStock" class="btn btn-primary btn-sm">
                        <span x-text="savingStock ? 'Saving…' : 'Apply'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Delete confirm modal ────────────────────────────────────────── --}}
        <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-5" @click.outside="confirmDelete = false">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Delete this listing?</h3>
                <p class="text-sm text-gray-500 mb-5">This will archive the listing. This action can be reversed by reactivating it later.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmDelete = false" class="btn btn-ghost btn-sm">Cancel</button>
                    <form method="POST" action="{{ route('admin.admin-product-listings.destroy', $listing) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminListingShow(config) {
            return {
                tab: 'overview',
                currentStatus: config.currentStatus,
                confirmDelete: false,
                adjustStockOpen: false,
                savingStock: false,
                savingShippingRule: false,
                adjustForm: { warehouse_id: '', adjustment: null, reason: '' },
                shippingForm: @json(optional($listing->marketplaceShippingRule)->only([
                    'requires_special_vehicle', 'requires_refrigeration', 'max_weight_kg',
                    'max_dimensions_cm', 'special_handling_notes', 'commission_type',
                    'commission_value', 'extra_delivery_fee',
                ]) ?? [
                    'requires_special_vehicle' => false,
                    'requires_refrigeration' => false,
                    'max_weight_kg' => null,
                    'max_dimensions_cm' => null,
                    'special_handling_notes' => null,
                    'commission_type' => 'fixed',
                    'commission_value' => 0,
                    'extra_delivery_fee' => 0,
                ]),

                openAdjustStock() {
                    this.adjustForm = { warehouse_id: '', adjustment: null, reason: '' };
                    this.adjustStockOpen = true;
                },

                async changeStatus() {
                    try {
                        const res = await fetch(config.urls.updateStatus, {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({ status: this.currentStatus }),
                        });
                        const data = await res.json();
                        if (window.Toast) window.Toast[data.success ? 'success' : 'error'](data.message);
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to update status.');
                    }
                },

                async submitAdjustStock() {
                    if (!this.adjustForm.warehouse_id || !this.adjustForm.adjustment || !this.adjustForm.reason) {
                        if (window.Toast) window.Toast.error('Please fill in all fields.');
                        return;
                    }
                    this.savingStock = true;
                    try {
                        const res = await fetch(config.urls.adjustStock, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify(this.adjustForm),
                        });
                        const data = await res.json();
                        if (data.success) {
                            if (window.Toast) window.Toast.success(data.message);
                            window.location.reload();
                        } else if (window.Toast) {
                            window.Toast.error(data.message || 'Failed to adjust stock.');
                        }
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to adjust stock.');
                    } finally {
                        this.savingStock = false;
                        this.adjustStockOpen = false;
                    }
                },

                async saveShippingRule() {
                    this.savingShippingRule = true;
                    try {
                        const res = await fetch(config.urls.saveShippingRule, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify(this.shippingForm),
                        });
                        const data = await res.json();
                        if (window.Toast) window.Toast[data.success ? 'success' : 'error'](data.message);
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to save shipping rule.');
                    } finally {
                        this.savingShippingRule = false;
                    }
                },

                async approveReview(id) {
                    try {
                        const res = await fetch(config.reviewApproveUrl.replace('__ID__', id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                        });
                        const data = await res.json();
                        if (res.ok) {
                            document.querySelector(`[data-review-status="${id}"]`).textContent = 'Published';
                            if (window.Toast) window.Toast.success(data.message);
                        } else if (window.Toast) {
                            window.Toast.error(data.message);
                        }
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to approve review.');
                    }
                },

                async rejectReview(id) {
                    const reason = prompt('Rejection reason (optional):') || '';
                    try {
                        const res = await fetch(config.reviewRejectUrl.replace('__ID__', id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({ reason }),
                        });
                        const data = await res.json();
                        if (res.ok) {
                            document.querySelector(`[data-review-status="${id}"]`).textContent = 'Rejected';
                            if (window.Toast) window.Toast.success(data.message);
                        } else if (window.Toast) {
                            window.Toast.error(data.message);
                        }
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to reject review.');
                    }
                },

                reviewsHtml: '',
                reviewsLoaded: false,
                async loadReviews(page = 1) {
                    try {
                        const res = await fetch(config.reviewsUrl + '?page=' + page, {
                            headers: { Accept: 'text/html' },
                        });
                        this.reviewsHtml = await res.text();
                        this.reviewsLoaded = true;
                        this.$nextTick(() => {
                            this.$refs.reviewsContainer?.querySelectorAll('a[href*="?page="]').forEach(a => {
                                a.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const url = new URL(a.href);
                                    this.loadReviews(url.searchParams.get('page') || 1);
                                });
                            });
                        });
                    } catch (e) {
                        if (window.Toast) window.Toast.error('Failed to load reviews.');
                    }
                },
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-copy').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var value = btn.dataset.value;
                    if (!value) return;
                    navigator.clipboard.writeText(value).then(function () {
                        var original = btn.textContent;
                        btn.textContent = 'Copied!';
                        setTimeout(function () { btn.textContent = original; }, 1500);
                    }).catch(function () {
                        if (window.Toast) window.Toast.error('Copy failed.');
                    });
                });
            });
        });
    </script>
@endpush
