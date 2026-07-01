@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/orders.js'])
@endpush

@section('title', 'Order #' . $order->order_number)

@section('content')
    @php
        $addr = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : json_decode($order->shipping_address_snapshot ?? '{}', true);
        $currency = strtoupper($order->currency);
        $fmt = fn($cents) => $currency . ' ' . number_format($cents / 100, 2);

        $statusColors = [
            'placed' => 'gray',
            'confirmed' => 'primary',
            'partially_shipped' => 'primary',
            'shipped' => 'primary',
            'partially_delivered' => 'primary',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'warning',
            'disputed' => 'danger',
        ];
        $subStatusColors = [
            'placed' => 'gray',
            'confirmed' => 'primary',
            'processing' => 'primary',
            'packed' => 'primary',
            'shipped' => 'primary',
            'out_for_delivery' => 'primary',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'returned' => 'warning',
            'refunded' => 'warning',
        ];
        $payStatusColors = [
            'pending' => 'gray',
            'authorized' => 'primary',
            'captured' => 'success',
            'failed' => 'danger',
            'refunded' => 'warning',
            'partially_refunded' => 'warning',
        ];
        $txTypeColors = [
            'authorization' => 'primary',
            'capture' => 'success',
            'sale' => 'success',
            'refund' => 'warning',
            'void' => 'gray',
            'chargeback' => 'danger',
        ];

        $riskScore = $order->risk_score;
        $riskColor = $riskScore >= 70 ? 'danger' : ($riskScore >= 40 ? 'warning' : 'success');
        $riskBg = $riskScore >= 70 ? 'bg-red-50 border-red-200' : ($riskScore >= 40 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200');
        $riskText = $riskScore >= 70 ? 'text-red-700' : ($riskScore >= 40 ? 'text-amber-700' : 'text-green-700');

        $payMethodLabels = [
            'card' => 'Credit / Debit Card',
            'wallet' => 'Wallet',
            'cod' => 'Cash on Delivery',
            'bnpl' => 'Buy Now Pay Later',
            'bank_transfer' => 'Bank Transfer',
        ];

        $disputeReasonLabels = [
            'item_not_received' => 'Item Not Received',
            'item_damaged' => 'Item Damaged',
            'item_not_as_described' => 'Not as Described',
            'counterfeit' => 'Counterfeit Item',
            'wrong_item' => 'Wrong Item Sent',
            'quality_issue' => 'Quality Issue',
            'seller_unresponsive' => 'Seller Unresponsive',
            'refund_not_received' => 'Refund Not Received',
            'other' => 'Other',
        ];

        $refundReasonLabels = [
            'customer_request' => 'Customer Request',
            'out_of_stock' => 'Out of Stock',
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong Item',
            'not_as_described' => 'Not as Described',
            'late_delivery' => 'Late Delivery',
            'duplicate_order' => 'Duplicate Order',
            'other' => 'Other',
        ];
    @endphp

    <script>window.ORDER_ID = '{{ $order->id }}';</script>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- MAIN COLUMN (2/3) --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-6">

            {{-- ──────────────────────────────────── --}}
            {{-- Order Items (Sub-orders accordion) --}}
            {{-- ──────────────────────────────────── --}}
            <x-card title="Order Items">
                <x-slot:actions>
                    <span class="text-sm text-gray-500">{{ $order->subOrders->count() }} seller(s)</span>
                </x-slot:actions>

                @forelse($order->subOrders as $subOrder)
                        <div class="border border-gray-200 rounded-xl mb-3 last:mb-0 overflow-hidden">

                            {{-- Sub-order header --}}
                            <div class="sub-order-header flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200 cursor-pointer"
                                data-sub-order-id="{{ $subOrder->id }}">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="sub-order-toggle text-gray-400 hover:text-gray-600">
                                        <svg class="toggle-icon w-4 h-4 transition-transform duration-200" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-800">
                                            {{ $subOrder->vendor->store_name ?? $subOrder->vendor->name ?? 'Unknown Seller' }}
                                        </span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $subOrder->sub_order_number }}</span>
                                    </div>
                                    <x-badge :color="$subStatusColors[$subOrder->status] ?? 'gray'">
                                        {{ ucwords(str_replace('_', ' ', $subOrder->status)) }}
                                    </x-badge>
                                    @if($subOrder->fulfillment_model === 'fbn')
                                        <x-badge color="primary">FBN</x-badge>
                                    @else
                                        <x-badge color="gray">FBM</x-badge>
                                    @endif
                                    @if($subOrder->sla_breached)
                                        <x-badge color="danger">SLA Breached</x-badge>
                                    @endif
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ $fmt($subOrder->subtotal) }}</span>
                            </div>

                            {{-- Sub-order body --}}
                            <div class="sub-order-body">

                                {{-- Items table --}}
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50/50 border-b border-gray-100">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">
                                                    Product</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">SKU
                                                </th>
                                                <th
                                                    class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase w-16">
                                                    Qty</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Unit</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Total</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">
                                                    Commission</th>
                                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">
                                                    Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($subOrder->items as $item)
                                                @php
                                                    $snap = is_array($item->product_snapshot) ? $item->product_snapshot : json_decode($item->product_snapshot ?? '{}', true);
                                                @endphp
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-3">
                                                            @if(!empty($snap['image_url']))
                                                                <img src="{{ $snap['image_url'] }}" alt=""
                                                                    class="w-10 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0">
                                                            @else
                                                                <div
                                                                    class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                                                    <x-heroicon name="photo" class="w-5 h-5 text-gray-300" />
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <p class="font-medium text-gray-900 text-sm">
                                                                    {{ $snap['name_en'] ?? $snap['name'] ?? 'Product #' . $item->sku }}
                                                                </p>
                                                                @if(!empty($snap['variant_label']))
                                                                    <p class="text-xs text-gray-400">{{ $snap['variant_label'] }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $item->sku }}</td>
                                                    <td class="px-4 py-3 text-center font-medium">{{ $item->quantity }}</td>
                                                    <td class="px-4 py-3 text-right text-sm">{{ $fmt($item->unit_price) }}</td>
                                                    <td class="px-4 py-3 text-right font-medium">{{ $fmt($item->line_total) }}</td>
                                                    <td class="px-4 py-3 text-right text-xs text-gray-500">
                                                        @php
                                                            $fixedCents = $item->commission_fixed_cents ?? 0;
                                                        @endphp
                                                        <span
                                                            class="font-mono">{{ number_format((float) $item->commission_rate_pct, 2) }}%</span>
                                                        @if($fixedCents > 0)
                                                            <span class="text-gray-400"> + {{ $fmt($fixedCents) }}</span>
                                                        @endif
                                                        <br>
                                                        <span class="text-danger-600 font-medium">=
                                                            {{ $fmt($item->commission_amount) }}</span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <x-badge color="gray" class="text-xs">
                                                            {{ ucfirst(str_replace('_', ' ', $item->fulfillment_status)) }}
                                                        </x-badge>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Shipping & SLA info --}}
                                <div
                                    class="px-4 py-3 bg-gray-50/50 border-t border-gray-100 grid grid-cols-3 gap-4 text-xs text-gray-500">
                                    <div>
                                        <span class="font-medium text-gray-700">Carrier:</span>
                                        {{ $subOrder->carrier->name ?? '—' }}
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Tracking:</span>
                                        @if($subOrder->tracking_number)
                                            <span class="font-mono text-gray-800">{{ $subOrder->tracking_number }}</span>
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Est. Delivery:</span>
                                        {{ $subOrder->estimated_delivery_date
                    ? \Carbon\Carbon::parse($subOrder->estimated_delivery_date)->format('M j, Y')
                    : '—' }}
                                    </div>
                                    @if($subOrder->sla_ship_deadline)
                                        <div class="col-span-3 {{ $subOrder->sla_breached ? 'text-red-600 font-medium' : '' }}">
                                            <span class="font-medium">SLA Ship Deadline:</span>
                                            {{ \Carbon\Carbon::parse($subOrder->sla_ship_deadline)->format('M j, Y H:i') }}
                                            @if($subOrder->sla_breached)
                                                <x-badge color="danger" class="ml-1">BREACHED</x-badge>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Financial breakdown (admin-only) --}}
                                <div class="px-4 py-3 border-t border-gray-100 space-y-1 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">Platform Commission</span>
                                        <span class="text-danger-600">−{{ $fmt($subOrder->platform_commission) }}</span>
                                    </div>
                                    @if($subOrder->gateway_fee > 0)
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-500">Payment Processing Fee (vendor-borne)</span>
                                            <span class="text-danger-600">−{{ $fmt($subOrder->gateway_fee) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between font-medium text-gray-700">
                                        <span>Vendor Payout</span>
                                        <span>{{ $fmt($subOrder->vendor_payout) }}</span>
                                    </div>
                                </div>

                            </div>{{-- /sub-order-body --}}
                        </div>
                @empty
                    <p class="text-sm text-gray-400 italic py-4 text-center">No sub-orders found.</p>
                @endforelse
            </x-card>

            {{-- ──────────────────────────────────── --}}
            {{-- Shipping Assignment --}}
            {{-- ──────────────────────────────────── --}}
            <x-card title="Shipping Assignment">
                @forelse($order->subOrders as $subOrder)
                    <div class="border border-gray-200 rounded-xl mb-3 last:mb-0 p-4 flex items-center justify-between gap-4"
                        data-shipping-sub-order-id="{{ $subOrder->id }}">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $subOrder->vendor->store_name ?? $subOrder->vendor->name ?? 'Unknown Seller' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $subOrder->sub_order_number }}</span>
                            </div>
                            @if($subOrder->shippingMethod)
                                <p class="text-sm text-gray-600 shipping-assignment-summary">
                                    Assigned: <span class="font-medium text-gray-900">{{ $subOrder->shippingMethod->name }}</span>
                                    @if($subOrder->carrier)
                                        via <span class="font-medium text-gray-900">{{ $subOrder->carrier->name }}</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-sm font-medium text-amber-600 shipping-assignment-summary">Not yet assigned</p>
                            @endif
                        </div>
                        <button type="button"
                            class="btn-assign-shipping border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            data-sub-order-id="{{ $subOrder->id }}"
                            data-shipping-url="{{ route('admin.orders.sub-orders.shipping-methods', $subOrder->id) }}"
                            data-assign-url="{{ route('admin.orders.sub-orders.assign-shipping', $subOrder->id) }}"
                            @if(in_array($subOrder->status, ['shipped', 'out_for_delivery', 'delivered', 'completed'])) disabled
                            @endif>
                            {{ $subOrder->shippingMethod ? 'Reassign' : 'Assign Shipping Method' }}
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic py-4 text-center">No sub-orders found.</p>
                @endforelse
            </x-card>

            {{-- ──────────────────────────────────── --}}
            {{-- Payment Transactions --}}
            {{-- ──────────────────────────────────── --}}
            <x-card title="Payment Transactions">
                @forelse($order->transactions as $tx)
                    <div class="flex items-start gap-4 py-3 border-b border-gray-100 last:border-0">
                        <div class="flex-shrink-0 mt-0.5">
                            <x-badge :color="$txTypeColors[$tx->type] ?? 'gray'">
                                {{ ucfirst($tx->type) }}
                            </x-badge>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $fmt($tx->amount) }}</p>
                                <x-badge :color="$tx->status === 'succeeded' ? 'success' : ($tx->status === 'failed' ? 'danger' : 'gray')">
                                    {{ ucfirst($tx->status) }}
                                </x-badge>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span class="font-medium">{{ strtoupper($tx->gateway) }}</span>
                                <span class="mx-1">·</span>
                                <span class="font-mono">{{ Str::limit($tx->gateway_transaction_id, 32) }}</span>
                            </p>
                            @if($tx->failure_message)
                                <p class="text-xs text-red-600 mt-1">{{ $tx->failure_message }}</p>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 flex-shrink-0">
                            {{ $tx->processed_at ? \Carbon\Carbon::parse($tx->processed_at)->format('M j, Y H:i') : '—' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic py-4 text-center">No transactions recorded.</p>
                @endforelse
            </x-card>

            {{-- ──────────────────────────────────── --}}
            {{-- Status History --}}
            {{-- ──────────────────────────────────── --}}
            <x-card title="Status History">
                @forelse($order->statusHistories->sortByDesc('created_at') as $history)
                    <div class="relative pl-6 pb-4 last:pb-0">
                        {{-- Timeline dot --}}
                        <span
                            class="absolute left-0 top-1.5 w-2.5 h-2.5 rounded-full bg-primary-400 ring-2 ring-white ring-offset-0"></span>
                        {{-- Line --}}
                        @if(!$loop->last)
                            <span class="absolute left-1 top-4 bottom-0 w-px bg-gray-200"></span>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($history->sub_order_id)
                                        <span class="text-xs font-mono text-gray-400">
                                            sub-order
                                        </span>
                                    @endif
                                    <x-badge :color="$subStatusColors[$history->from_status] ?? 'gray'">
                                        {{ ucwords(str_replace('_', ' ', $history->from_status)) }}
                                    </x-badge>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                    <x-badge :color="$subStatusColors[$history->to_status] ?? ($statusColors[$history->to_status] ?? 'gray')">
                                        {{ ucwords(str_replace('_', ' ', $history->to_status)) }}
                                    </x-badge>
                                </div>
                                @if($history->reason)
                                    <p class="text-xs text-gray-500 mt-1">{{ $history->reason }}</p>
                                @endif
                                @if($history->changedByAdmin)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        by {{ $history->changedByAdmin->name ?? 'Admin' }}
                                    </p>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0">
                                {{ \Carbon\Carbon::parse($history->created_at)->format('M j, Y H:i') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic py-4 text-center">No history recorded.</p>
                @endforelse
            </x-card>

            {{-- ──────────────────────────────────── --}}
            {{-- Disputes (if any) --}}
            {{-- ──────────────────────────────────── --}}
            @if($order->disputes->isNotEmpty())
                <x-card title="Disputes">
                    @foreach($order->disputes as $dispute)
                        <div class="border border-gray-200 rounded-xl p-4 mb-3 last:mb-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-mono text-sm font-semibold text-gray-700">{{ $dispute->dispute_number }}</span>
                                    <x-badge :color="in_array($dispute->status, ['resolved', 'closed']) ? 'success' : ($dispute->status === 'escalated' ? 'danger' : 'warning')">
                                        {{ ucwords(str_replace('_', ' ', $dispute->status)) }}
                                    </x-badge>
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($dispute->created_at)->format('M j, Y') }}
                                </span>
                            </div>
                            <p class="text-xs font-medium text-gray-500 mb-1">
                                Reason: {{ $disputeReasonLabels[$dispute->reason] ?? $dispute->reason }}
                            </p>
                            <p class="text-sm text-gray-700">{{ $dispute->description }}</p>
                            @if($dispute->resolution_notes)
                                <div class="mt-2 p-2 bg-gray-50 rounded-lg border border-gray-100 text-xs text-gray-600">
                                    <span class="font-medium">Resolution:</span> {{ $dispute->resolution_notes }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </x-card>
            @endif

        </div>{{-- /main column --}}

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- SIDEBAR (1/3) --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div class="w-80 flex-shrink-0 space-y-4 sticky top-20">

            {{-- Order Summary --}}
            <x-card title="Order Summary">
                <div class="space-y-1 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Order #</span>
                        <span class="font-semibold font-mono text-gray-900">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Placed</span>
                        <span class="text-gray-700">
                            {{ \Carbon\Carbon::parse($order->placed_at)->format('M j, Y H:i') }}
                        </span>
                    </div>
                    @if(!empty($addr['country']) || !empty($addr['country_en']))
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Country</span>
                            <span class="text-gray-700">{{ $addr['country'] ?? $addr['country_en'] ?? '' }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Status</span>
                        <x-badge :color="$statusColors[$order->status] ?? 'gray'">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </x-badge>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-3 pt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span><span>{{ $fmt($order->subtotal) }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-success-600">
                            <span>Discount</span><span>−{{ $fmt($order->discount) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping</span><span>{{ $fmt($order->shipping) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax</span><span>{{ $fmt($order->tax) }}</span>
                    </div>
                    @if($order->cod_fee > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>COD Fee</span><span>{{ $fmt($order->cod_fee) }}</span>
                        </div>
                    @endif
                    @if($order->coupon_code_used)
                        <div class="flex justify-between text-gray-500 text-xs">
                            <span>Coupon</span><span class="font-mono">{{ $order->coupon_code_used }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold text-gray-900 text-base pt-1 border-t border-gray-100">
                        <span>Total</span><span>{{ $fmt($order->total) }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-3 pt-3 flex items-center justify-between text-xs">
                    <span
                        class="text-gray-500">{{ $payMethodLabels[$order->payment_method] ?? $order->payment_method }}</span>
                    <x-badge :color="$payStatusColors[$order->payment_status] ?? 'gray'">
                        {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}
                    </x-badge>
                </div>

                @if($order->payment_method === 'cod')
                    <div class="border-t border-gray-100 mt-3 pt-3 space-y-1 text-xs">
                        <p class="text-gray-500 font-medium uppercase tracking-wide">COD Remittance</p>
                        @php $firstSubOrder = $order->subOrders->first(); @endphp
                        @foreach($order->subOrders as $so)
                            @if(!$so->cod_remittance_confirmed)
                                <div class="flex items-start gap-1.5 text-amber-700">
                                    <x-heroicon name="clock" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500" />
                                    <span>
                                        Sub-order #{{ $so->sub_order_number }}:
                                        Pending remittance
                                        @if($so->codSettlement && $so->codSettlement->agent)
                                            from {{ $so->codSettlement->agent->name }}
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div class="flex items-start gap-1.5 text-green-700">
                                    <x-heroicon name="check-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0 text-green-500" />
                                    <span>
                                        Sub-order #{{ $so->sub_order_number }}:
                                        Remitted —
                                        @if($so->codSettlement)
                                            <a href="{{ route('admin.cod-settlements.show', $so->codSettlement) }}"
                                                class="underline hover:text-green-900">
                                                Settlement #{{ $so->cod_settlement_id }}
                                            </a>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </x-card>

            {{-- Customer --}}
            <x-card title="Customer">
                @if($order->customer)
                    <div class="space-y-2 text-sm">
                        <p class="font-semibold text-gray-900">{{ $order->customer->name }}</p>
                        @if($order->customer->email)
                            <p class="text-gray-500 text-xs">{{ $order->customer->email }}</p>
                        @endif
                        @if($order->customer->phone)
                            <p class="text-gray-500 text-xs">{{ $order->customer->phone }}</p>
                        @endif
                    </div>
                @endif

                @if(!empty($addr))
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Delivery Address</p>
                        <address class="not-italic text-sm text-gray-700 space-y-0.5">
                            @if(!empty($addr['name']))
                            <p class="font-medium">{{ $addr['name'] }}</p>@endif
                            @if(!empty($addr['line1']))
                            <p>{{ $addr['line1'] }}</p>@endif
                            @if(!empty($addr['line2']))
                            <p>{{ $addr['line2'] }}</p>@endif
                            @if(!empty($addr['city']))
                            <p>{{ $addr['city'] }}@if(!empty($addr['state'])), {{ $addr['state'] }}@endif</p>@endif
                            @if(!empty($addr['zip']))
                            <p>{{ $addr['zip'] }}</p>@endif
                            @if(!empty($addr['country']))
                            <p>{{ $addr['country'] }}</p>@endif
                            @if(!empty($addr['phone']))
                            <p class="text-gray-500">{{ $addr['phone'] }}</p>@endif
                        </address>
                    </div>
                @endif
            </x-card>

            {{-- Risk Assessment --}}
            <x-card title="Risk Assessment">
                <div class="flex items-center gap-4 mb-3">
                    @if($riskScore !== null)
                        <div
                            class="w-16 h-16 rounded-full flex items-center justify-center font-bold text-xl {{ $riskBg }} {{ $riskText }} border-2 {{ str_replace('bg-', 'border-', explode(' ', $riskBg)[0]) }}-300 flex-shrink-0">
                            {{ (int) $riskScore }}
                        </div>
                        <div>
                            <p class="font-semibold {{ $riskText }}">
                                {{ $riskScore >= 70 ? 'High Risk' : ($riskScore >= 40 ? 'Medium Risk' : 'Low Risk') }}
                            </p>
                            <p class="text-xs text-gray-400">Score out of 100</p>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 italic">No risk data.</p>
                    @endif
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-start gap-2">
                        <span class="text-gray-400 flex-shrink-0 w-20">IP Address</span>
                        <span class="font-mono text-gray-700 break-all">{{ $order->ip_address ?? '—' }}</span>
                    </div>
                    @if($order->device_fingerprint)
                        <div class="flex items-start gap-2">
                            <span class="text-gray-400 flex-shrink-0 w-20">Device</span>
                            <span
                                class="font-mono text-gray-600 break-all text-xs">{{ Str::limit($order->device_fingerprint, 40) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Fraud flag history entries --}}
                @php
                    $fraudFlags = $order->statusHistories->filter(
                        fn($h) => is_array($h->metadata) && ($h->metadata['action'] ?? '') === 'fraud_flagged'
                    );
                @endphp
                @if($fraudFlags->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold text-danger-600 uppercase tracking-wider mb-2">Fraud Flags</p>
                        @foreach($fraudFlags as $flag)
                            <div class="text-xs text-gray-600 py-1">
                                <p class="text-red-600">{{ $flag->reason }}</p>
                                <p class="text-gray-400">{{ \Carbon\Carbon::parse($flag->created_at)->format('M j, Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            {{-- Refunds (summary) --}}
            @if($order->refunds->isNotEmpty())
                <x-card title="Refunds">
                    @foreach($order->refunds as $refund)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
                            <div>
                                <p class="font-medium text-gray-800">{{ $fmt($refund->amount) }}</p>
                                <p class="text-xs text-gray-400">{{ $refundReasonLabels[$refund->reason] ?? $refund->reason }}</p>
                            </div>
                            <x-badge :color="$refund->status === 'completed' ? 'success' : ($refund->status === 'rejected' || $refund->status === 'failed' ? 'danger' : 'warning')">
                                {{ ucfirst($refund->status) }}
                            </x-badge>
                        </div>
                    @endforeach
                </x-card>
            @endif

            {{-- Actions --}}
            <x-card title="Actions">
                <div class="space-y-2">
                    <button type="button" data-modal-open="update-status-modal"
                        class="btn btn-primary w-full justify-center">
                        <x-heroicon name="arrow-path" class="w-4 h-4 mr-1.5" />
                        Update Status
                    </button>
                    <button type="button" data-modal-open="refund-modal" class="btn btn-secondary w-full justify-center">
                        <x-heroicon name="arrow-uturn-left" class="w-4 h-4 mr-1.5" />
                        Process Refund
                    </button>
                    @if(!in_array($order->status, ['cancelled', 'refunded', 'completed']))
                        <button type="button" data-modal-open="force-cancel-modal"
                            class="btn btn-ghost w-full justify-center text-danger-600 hover:bg-danger-50">
                            <x-heroicon name="x-circle" class="w-4 h-4 mr-1.5" />
                            Force Cancel
                        </button>
                    @endif
                    <button type="button" data-modal-open="dispute-modal" class="btn btn-ghost w-full justify-center">
                        <x-heroicon name="scale" class="w-4 h-4 mr-1.5" />
                        Escalate Dispute
                    </button>
                    <button type="button" data-modal-open="fraud-modal"
                        class="btn btn-ghost w-full justify-center text-warning-700 hover:bg-warning-50">
                        <x-heroicon name="shield-exclamation" class="w-4 h-4 mr-1.5" />
                        Flag Fraud
                    </button>
                </div>
            </x-card>

        </div>{{-- /sidebar --}}

    </div>{{-- /flex row --}}

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}

    {{-- 1. Update Order Status --}}
    @php
        $orderStatusTransitions = [
            'placed' => ['confirmed', 'cancelled', 'disputed'],
            'confirmed' => ['partially_shipped', 'shipped', 'cancelled', 'disputed'],
            'partially_shipped' => ['shipped', 'partially_delivered', 'cancelled', 'disputed'],
            'shipped' => ['partially_delivered', 'delivered', 'disputed'],
            'partially_delivered' => ['delivered', 'cancelled', 'disputed'],
            'delivered' => ['completed', 'refunded', 'disputed'],
            'completed' => ['refunded', 'disputed'],
            'cancelled' => ['refunded'],
            'refunded' => [],
            'disputed' => ['delivered', 'cancelled', 'refunded', 'completed'],
        ];
        $allowedNextStatuses = $orderStatusTransitions[$order->status] ?? [];
    @endphp
    <x-modal id="update-status-modal" title="Update Order Status" size="md">
        <form id="update-status-form">
            @csrf
            <div class="space-y-4">
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-sm text-gray-700">
                    Current status:
                    <x-badge :color="$statusColors[$order->status] ?? 'gray'" class="ml-1">
                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                    </x-badge>
                </div>
                <div>
                    <label class="form-label" for="order-new-status">New Status <span
                            class="text-danger-500">*</span></label>
                    @if(empty($allowedNextStatuses))
                        <p class="text-sm text-gray-400 italic">No further status transitions are available for this order.</p>
                    @else
                        <select id="order-new-status" name="new_status" class="form-select w-full">
                            <option value="">Select new status…</option>
                            @foreach($allowedNextStatuses as $s)
                                <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label class="form-label" for="order-status-reason">Reason / Notes</label>
                    <textarea id="order-status-reason" name="reason" rows="3" class="form-textarea w-full"
                        placeholder="Briefly explain this status change…"></textarea>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" form="update-status-form" class="btn btn-primary" @if(empty($allowedNextStatuses))
                disabled @endif>
                    Update Status
                </button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 2. Process Refund --}}
    <x-modal id="refund-modal" title="Process Refund" size="md">
        <form id="refund-form">
            @csrf
            <div class="space-y-4">
                {{-- Refund type --}}
                <div>
                    <label class="form-label">Refund Type</label>
                    <div class="space-y-2 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="refund_type" value="full" class="text-primary-600"
                                @if($order->payment_status !== 'refunded') checked @endif>
                            <span class="text-sm">Full order — <strong>{{ $fmt($order->total) }}</strong></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="refund_type" value="shipping_only" class="text-primary-600">
                            <span class="text-sm">Shipping only — <strong>{{ $fmt($order->shipping) }}</strong></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="refund_type" value="partial" class="text-primary-600">
                            <span class="text-sm">Partial amount</span>
                        </label>
                    </div>
                </div>

                {{-- Partial amount (shown/hidden by JS) --}}
                <div id="partial-amount-field" class="hidden">
                    <label class="form-label">Amount ({{ $currency }})</label>
                    <input type="number" name="amount" min="0.01" step="0.01" max="{{ $order->total / 100 }}"
                        class="form-input w-full" placeholder="0.00">
                </div>

                {{-- Sub-order (optional) --}}
                <div>
                    <label class="form-label">Sub-order (optional)</label>
                    <select name="sub_order_id" class="form-select w-full">
                        <option value="">Entire order</option>
                        @foreach($order->subOrders as $so)
                            <option value="{{ $so->id }}">
                                {{ $so->sub_order_number }} — {{ $so->vendor->store_name ?? 'Seller' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reason --}}
                <div>
                    <label class="form-label">Reason <span class="text-danger-500">*</span></label>
                    <select name="reason" class="form-select w-full">
                        @foreach($refundReasonLabels as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="reason_notes" rows="2" class="form-textarea w-full"
                        placeholder="Additional context…"></textarea>
                </div>

                {{-- Vendor chargeback --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="vendor_charged_back" value="0">
                    <input type="checkbox" name="vendor_charged_back" value="1"
                        class="rounded text-primary-600 border-gray-300">
                    <span class="text-sm text-gray-700">Charge back to seller</span>
                </label>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" form="refund-form" class="btn btn-secondary">Process Refund</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 3. Force Cancel --}}
    <x-modal id="force-cancel-modal" title="Force Cancel Order" size="sm">
        <form id="force-cancel-form">
            @csrf
            <div class="space-y-4">
                <div
                    class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 flex items-start gap-2">
                    <x-heroicon name="exclamation-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>This will cancel <strong>all sub-orders</strong>. This cannot be undone.</span>
                </div>

                <div>
                    <label class="form-label">Cancellation Reason <span class="text-danger-500">*</span></label>
                    <textarea name="reason" rows="3" class="form-textarea w-full"
                        placeholder="Why is this order being force-cancelled?"></textarea>
                </div>

                <label class="flex items-center gap-2 cursor-pointer" id="force-override-toggle">
                    <input type="hidden" name="force" value="0">
                    <input type="checkbox" name="force" value="1" class="rounded text-danger-600 border-gray-300">
                    <span class="text-sm text-gray-700">Force cancel even shipped / delivered sub-orders</span>
                </label>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Abort</button>
                <button type="submit" form="force-cancel-form" class="btn btn-danger">Force Cancel</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 4. Escalate Dispute --}}
    <x-modal id="dispute-modal" title="Escalate Dispute" size="md">
        <form id="dispute-form">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label">Sub-order <span class="text-danger-500">*</span></label>
                    <select name="sub_order_id" class="form-select w-full">
                        <option value="">Select sub-order…</option>
                        @foreach($order->subOrders as $so)
                            <option value="{{ $so->id }}">
                                {{ $so->sub_order_number }} — {{ $so->vendor->store_name ?? 'Seller' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Reason <span class="text-danger-500">*</span></label>
                    <select name="reason" class="form-select w-full">
                        @foreach($disputeReasonLabels as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Description <span class="text-danger-500">*</span></label>
                    <textarea name="description" rows="4" class="form-textarea w-full"
                        placeholder="Detailed description of the issue…"></textarea>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" form="dispute-form" class="btn btn-danger">Escalate Dispute</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 5. Flag Fraud --}}
    <x-modal id="fraud-modal" title="Flag as Potential Fraud" size="sm">
        <form id="fraud-form">
            @csrf
            <div class="space-y-4">
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800 flex items-start gap-2">
                    <x-heroicon name="shield-exclamation" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>This will set the risk score to <strong>100</strong> and log a fraud flag for review.</span>
                </div>
                <div>
                    <label class="form-label">Reason <span class="text-danger-500">*</span></label>
                    <textarea name="reason" rows="3" class="form-textarea w-full"
                        placeholder="Describe the fraud indicators…"></textarea>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" form="fraud-form" class="btn btn-danger">Flag Fraud</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 6. Assign Shipping Method --}}
    <x-modal id="shipping-assign-modal" title="Assign Shipping Method" size="lg">
        <div id="shipping-assign-zone-warning"
            class="hidden rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 mb-4">
            Cannot determine shipping zone — the delivery address does not contain a resolvable city reference.
            Please assign the shipping method manually below.
        </div>
        <div id="shipping-assign-loading" class="text-sm text-gray-500 py-8 text-center">Loading available methods…</div>
        <div id="shipping-assign-methods" class="space-y-3 hidden"></div>
        <div id="shipping-assign-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3 mt-4"></div>

        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="shipping-assign-confirm" class="btn btn-primary" disabled>Confirm Assignment</button>
        </x-slot:footer>
    </x-modal>

@endsection