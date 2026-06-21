@extends('layouts.partner')
@section('title', 'Transfer ' . $transfer->transfer_number)
@section('page-title', 'Transfer ' . $transfer->transfer_number)

@push('scripts')
    @vite('resources/js/partner/warehouses.js')
    <script>
        window.TRANSFER_SHOW_CFG = {
            transferId: '{{ $transfer->id }}',
            shipUrl:    '{{ route('partner.warehouses.transfers.ship', $transfer->id) }}',
            cancelUrl:  '{{ route('partner.warehouses.transfers.cancel', $transfer->id) }}',
            status:     '{{ $transfer->status }}',
        };
    </script>
@endpush

@section('content')

    <div class="mb-5">
        <a href="{{ route('partner.warehouses.transfers.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
            ← Back to transfers
        </a>
    </div>

    {{-- Header card --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="font-mono font-bold text-lg text-gray-900">{{ $transfer->transfer_number }}</span>
                    @php
                        $statusClasses = match($transfer->status) {
                            'draft'      => 'bg-gray-100 text-gray-600',
                            'in_transit' => 'bg-blue-100 text-blue-700',
                            'received'   => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-600',
                            default      => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold {{ $statusClasses }}">
                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
                    <div>
                        <span class="text-gray-500">From:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $transfer->sourceWarehouse->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">To:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $transfer->destinationWarehouse->name }}</span>
                    </div>
                    @if($transfer->expected_arrival_date)
                        <div>
                            <span class="text-gray-500">Expected arrival:</span>
                            <span class="font-medium text-gray-800 ml-1">{{ $transfer->expected_arrival_date->format('d M Y') }}</span>
                        </div>
                    @endif
                    @if($transfer->carrier)
                        <div>
                            <span class="text-gray-500">Carrier:</span>
                            <span class="font-medium text-gray-800 ml-1">{{ $transfer->carrier }}</span>
                        </div>
                    @endif
                    @if($transfer->tracking_number)
                        <div>
                            <span class="text-gray-500">Tracking:</span>
                            <span class="font-medium text-gray-800 ml-1">{{ $transfer->tracking_number }}</span>
                        </div>
                    @endif
                    @if($transfer->shipped_at)
                        <div>
                            <span class="text-gray-500">Shipped:</span>
                            <span class="font-medium text-gray-800 ml-1">{{ $transfer->shipped_at->format('d M Y H:i') }}</span>
                        </div>
                    @endif
                </div>
                @if($transfer->notes)
                    <p class="mt-2 text-sm text-gray-500 italic">{{ $transfer->notes }}</p>
                @endif
            </div>

            <div class="flex gap-2 flex-shrink-0 ml-4">
                @if($transfer->status === 'draft')
                    <button id="ship-transfer-btn"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                        Mark as Shipped
                    </button>
                    <button id="cancel-transfer-btn"
                        class="inline-flex items-center px-3 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 transition-colors">
                        Cancel
                    </button>
                @elseif($transfer->status === 'in_transit')
                    <button id="cancel-transfer-btn"
                        class="inline-flex items-center px-3 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 transition-colors">
                        Cancel transfer
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-800 text-sm">Transfer items ({{ $transfer->items->count() }})</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-2.5 text-left">Product</th>
                    <th class="px-4 py-2.5 text-left">SKU</th>
                    <th class="px-4 py-2.5 text-right">Requested</th>
                    <th class="px-4 py-2.5 text-right">Received</th>
                    <th class="px-4 py-2.5 text-right">Damaged</th>
                    @if($transfer->items->first()?->condition_notes !== null || $transfer->status === 'received')
                        <th class="px-4 py-2.5 text-left">Notes</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transfer->items as $item)
                    @php
                        $product = $item->vendorListing?->productVariant?->product;
                        $variant = $item->vendorListing?->productVariant;
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $product?->name_en ?? '—' }}</p>
                            @if($variant?->variant_name)
                                <p class="text-xs text-gray-500">{{ $variant->variant_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $item->vendorListing?->vendor_sku ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($item->quantity_requested) }}</td>
                        <td class="px-4 py-3 text-right {{ $item->quantity_received > 0 ? 'text-green-700 font-semibold' : 'text-gray-400' }}">
                            {{ number_format($item->quantity_received) }}
                        </td>
                        <td class="px-4 py-3 text-right {{ $item->damaged_quantity > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                            {{ number_format($item->damaged_quantity) }}
                        </td>
                        @if($transfer->items->first()?->condition_notes !== null || $transfer->status === 'received')
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $item->condition_notes ?? '—' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Ship modal --}}
    @if($transfer->status === 'draft')
        <div id="ship-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Mark as Shipped</h3>
                    <button onclick="document.getElementById('ship-modal').classList.add('hidden');document.getElementById('ship-modal').classList.remove('flex');"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-sm text-gray-600">This will deduct stock from the source warehouse. Provide carrier info if available.</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Carrier (optional)</label>
                        <input type="text" id="ship-carrier" maxlength="100"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tracking number (optional)</label>
                        <input type="text" id="ship-tracking" maxlength="100"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div id="ship-error" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"></div>
                    <div class="flex justify-end gap-3">
                        <button onclick="document.getElementById('ship-modal').classList.add('hidden');document.getElementById('ship-modal').classList.remove('flex');"
                            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50">Cancel</button>
                        <button id="confirm-ship-btn"
                            class="px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 disabled:opacity-50 transition-colors">
                            Confirm Shipment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
