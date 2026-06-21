@extends('layouts.partner')
@section('title', 'New Transfer')
@section('page-title', 'Create Transfer')

@push('scripts')
    @vite('resources/js/partner/warehouse-create.js')
    <script>
        window.TRANSFER_CREATE_CFG = {
            storeUrl:          '{{ route('partner.warehouses.transfers.store') }}',
            indexUrl:          '{{ route('partner.warehouses.transfers.index') }}',
            defaultSourceId:   '{{ $sourceWarehouseId ?? '' }}',
            listingsSearchUrl: '{{ url('/partner/listings/search') }}',
        };
    </script>
@endpush

@section('content')
    <div class="max-w-3xl">

        <a href="{{ route('partner.warehouses.transfers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-5">
            ← Back to transfers
        </a>

        <form id="transfer-create-form" novalidate>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-4">
                <h3 class="font-semibold text-gray-800 mb-4">Transfer details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From warehouse <span class="text-red-500">*</span></label>
                        <select name="source_warehouse_id" id="source-warehouse-select" required
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Select source…</option>
                            @foreach($myWarehouses as $wh)
                                <option value="{{ $wh->id }}" {{ ($sourceWarehouseId ?? '') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} ({{ $wh->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To warehouse <span class="text-red-500">*</span></label>
                        <select name="destination_warehouse_id" id="destination-warehouse-select" required
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Select destination…</option>
                            @foreach($destinationOptions as $wh)
                                <option value="{{ $wh->id }}" data-type="{{ $wh->type }}">
                                    {{ $wh->name }}
                                    @if($wh->type === 'platform_fbn') (Platform FBN) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expected arrival</label>
                        <input type="date" name="expected_arrival_date" id="transfer-arrival-date"
                            min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <input type="text" name="notes" id="transfer-notes" maxlength="500"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Items</h3>
                    <button type="button" id="add-transfer-item-btn"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-primary-300 text-primary-700 text-sm font-medium rounded-lg hover:bg-primary-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add item
                    </button>
                </div>

                <div id="transfer-items-list" class="space-y-3">
                    <p id="transfer-items-empty" class="text-sm text-gray-400 text-center py-4">No items added yet. Click "Add item" to start.</p>
                </div>
            </div>

            <div id="transfer-error" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"></div>

            <div class="flex gap-3">
                <button type="submit" id="create-transfer-btn"
                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 disabled:opacity-50 transition-colors">
                    Create transfer
                </button>
                <a href="{{ route('partner.warehouses.transfers.index') }}"
                    class="inline-flex items-center px-4 py-2.5 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>

        {{-- Add item modal --}}
        <div id="add-item-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Add Product</h3>
                    <button onclick="document.getElementById('add-item-modal').classList.add('hidden');document.getElementById('add-item-modal').classList.remove('flex');"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search product / SKU</label>
                        <input type="text" id="item-search-input" placeholder="Type to search…"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div id="item-search-results" class="max-h-48 overflow-y-auto space-y-1"></div>
                    <div id="item-selected-info" class="hidden p-3 bg-gray-50 rounded-xl">
                        <p id="item-selected-name" class="font-medium text-sm text-gray-800"></p>
                        <p id="item-selected-sku" class="text-xs text-gray-500"></p>
                        <input type="hidden" id="item-selected-listing-id" />
                    </div>
                    <div id="item-qty-wrap" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" id="item-qty" min="1" value="1"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('add-item-modal').classList.add('hidden');document.getElementById('add-item-modal').classList.remove('flex');"
                            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" id="confirm-add-item-btn" disabled
                            class="px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 disabled:opacity-40 transition-colors">
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
