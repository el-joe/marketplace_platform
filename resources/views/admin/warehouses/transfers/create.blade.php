@extends('layouts.admin')

@section('title', 'New Inventory Transfer')

@section('content')

    {{-- ─── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-1.5">
        <a href="{{ route('admin.warehouses.index') }}" class="hover:text-primary-600">Warehouses</a>
        <span>/</span>
        <a href="{{ route('admin.warehouses.transfers.index') }}" class="hover:text-primary-600">Transfers</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">New Transfer</span>
    </nav>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">New Inventory Transfer</h1>
        <p class="text-sm text-gray-500 mt-0.5">Move stock between warehouses.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    <form action="{{ route('admin.warehouses.transfers.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ─── Transfer Details ─────────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-5">
                <x-card>
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Transfer Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Source Warehouse <span class="text-red-500">*</span>
                            </label>
                            <select name="source_warehouse_id" class="form-input w-full text-sm" required>
                                <option value="">Select source…</option>
                                @foreach($warehouses as $id => $name)
                                    <option value="{{ $id }}" {{ old('source_warehouse_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('source_warehouse_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Destination Warehouse <span class="text-red-500">*</span>
                            </label>
                            <select name="destination_warehouse_id" class="form-input w-full text-sm" required>
                                <option value="">Select destination…</option>
                                @foreach($warehouses as $id => $name)
                                    <option value="{{ $id }}" {{ old('destination_warehouse_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Vendor (optional)</label>
                            <select name="vendor_id" class="form-input w-full text-sm">
                                <option value="">— Any vendor —</option>
                                @foreach($vendors as $id => $name)
                                    <option value="{{ $id }}" {{ old('vendor_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Expected Arrival</label>
                            <input type="date" name="expected_arrival_date" class="form-input w-full text-sm"
                                value="{{ old('expected_arrival_date') }}" min="{{ now()->addDay()->format('Y-m-d') }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="3" class="form-input w-full text-sm"
                                placeholder="Optional notes…">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </x-card>

                {{-- ─── Line Items ────────────────────────────────────────── --}}
                <x-card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Items</h3>
                        <button type="button" id="add-item-row" class="btn btn-secondary btn-xs">+ Add Item</button>
                    </div>

                    <div id="items-container" class="space-y-3">
                        @php $oldItems = old('items', [[]]); @endphp
                        @foreach($oldItems as $i => $oldItem)
                            <div class="flex items-start gap-3 item-row">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Vendor Listing ID / SKU</label>
                                    <input type="text" name="items[{{ $i }}][vendor_listing_id]"
                                        class="form-input w-full text-sm" placeholder="UUID of vendor listing"
                                        value="{{ $oldItem['vendor_listing_id'] ?? '' }}" required>
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs text-gray-500 mb-1">Qty Requested</label>
                                    <input type="number" name="items[{{ $i }}][quantity_requested]"
                                        class="form-input w-full text-sm" placeholder="0" min="1"
                                        value="{{ $oldItem['quantity_requested'] ?? '' }}" required>
                                </div>
                                @if($i > 0)
                                    <button type="button"
                                        class="mt-5 text-red-400 hover:text-red-600 remove-item-row">&times;</button>
                                @else
                                    <div class="w-5 mt-5"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('items')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </x-card>
            </div>

            {{-- ─── Sidebar Actions ──────────────────────────────────────── --}}
            <div class="space-y-5">
                <x-card>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Actions</h3>
                    <div class="space-y-2">
                        <button type="submit" class="btn btn-primary w-full">Create Transfer</button>
                        <a href="{{ route('admin.warehouses.transfers.index') }}"
                            class="btn btn-ghost w-full text-gray-500">Cancel</a>
                    </div>
                </x-card>
            </div>

        </div>
    </form>

@endsection

@push('scripts')
    <script type="module">
        document.getElementById('add-item-row').addEventListener('click', function () {
            const container = document.getElementById('items-container');
            const idx = container.querySelectorAll('.item-row').length;

            const row = document.createElement('div');
            row.className = 'flex items-start gap-3 item-row';
            row.innerHTML = `
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Vendor Listing ID / SKU</label>
                <input type="text" name="items[${idx}][vendor_listing_id]"
                    class="form-input w-full text-sm" placeholder="UUID of vendor listing" required>
            </div>
            <div class="w-32">
                <label class="block text-xs text-gray-500 mb-1">Qty Requested</label>
                <input type="number" name="items[${idx}][quantity_requested]"
                    class="form-input w-full text-sm" placeholder="0" min="1" required>
            </div>
            <button type="button" class="mt-5 text-red-400 hover:text-red-600 remove-item-row">&times;</button>
        `;
            container.appendChild(row);
        });

        document.getElementById('items-container').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item-row')) {
                e.target.closest('.item-row').remove();
            }
        });
    </script>
@endpush