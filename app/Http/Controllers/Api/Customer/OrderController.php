<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use App\Models\SubOrder;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WarehouseInventory;
use App\Models\WarrantyPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const CANCELLABLE_ORDER_STATUSES = ['placed', 'confirmed'];

    public function index(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $query = Order::where('customer_id', $customer->id)
            ->withCount('subOrders')
            ->orderByDesc('placed_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->input('search').'%');
        }

        $paginator = $query->paginate(15);

        return ApiResponse::success([
            'items' => collect($paginator->items())->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'status' => $order->status?->value,
                'payment_status' => $order->payment_status?->value,
                'currency' => $order->currency,
                'total' => $order->total,
                'sub_orders_count' => $order->sub_orders_count,
                'placed_at' => $order->placed_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Orders retrieved');
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->findOrder($customer, $orderNumber);

        if (! $order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        $order->load([
            'subOrders.items.warrantyPurchase',
            'subOrders.vendor:id,store_name',
            'subOrders.carrier',
            'statusHistories',
        ]);

        return ApiResponse::success($this->buildOrderDetail($order), 'Order retrieved');
    }

    public function showSubOrder(Request $request, string $orderNumber, string $subOrderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->findOrder($customer, $orderNumber);

        if (! $order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        $subOrder = SubOrder::where('order_id', $order->id)
            ->where('sub_order_number', $subOrderNumber)
            ->with(['vendor:id,store_name', 'carrier', 'items', 'shipments.trackingEvents'])
            ->first();

        if (! $subOrder) {
            return ApiResponse::error('Sub-order not found.', [], 404);
        }

        return ApiResponse::success($this->buildSubOrderDetail($subOrder), 'Sub-order retrieved');
    }

    public function tracking(Request $request, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->findOrder($customer, $orderNumber);

        if (! $order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        $order->load(['subOrders.carrier', 'subOrders.shipments.trackingEvents']);

        $subOrders = $order->subOrders->mapWithKeys(fn (SubOrder $subOrder) => [
            $subOrder->sub_order_number => [
                'status' => $subOrder->status?->value,
                'carrier' => $subOrder->carrier?->name,
                'tracking_number' => $subOrder->tracking_number,
                'estimated_delivery_date' => $subOrder->estimated_delivery_date?->toDateString(),
                'shipped_at' => $subOrder->shipped_at?->toIso8601String(),
                'delivered_at' => $subOrder->delivered_at?->toIso8601String(),
                'shipments' => $subOrder->shipments->map(fn (Shipment $shipment) => [
                    'carrier_id' => $shipment->carrier_id,
                    'tracking_number' => $shipment->tracking_number,
                    'awb_label_url' => $shipment->awb_label_url,
                    'status' => $shipment->status?->value,
                    'picked_up_at' => $shipment->picked_up_at?->toIso8601String(),
                    'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                    'events' => $shipment->trackingEvents
                        ->sortBy('occurred_at')
                        ->values()
                        ->map(fn ($event) => [
                            'status' => $event->status?->value,
                            'description' => $event->description,
                            'location' => $event->location,
                            'occurred_at' => $event->occurred_at?->toIso8601String(),
                        ]),
                ]),
            ],
        ]);

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'sub_orders' => $subOrders,
        ], 'Tracking retrieved');
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->findOrder($customer, $orderNumber);

        if (! $order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        if (! in_array($order->status?->value, self::CANCELLABLE_ORDER_STATUSES, true)) {
            return ApiResponse::error('This order cannot be cancelled in its current status.', [], 422);
        }

        $order = DB::transaction(function () use ($order, $customer) {
            $previousStatus = $order->status?->value;

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $order->subOrders()->get()->each(function (SubOrder $subOrder) {
                $subOrder->update(['status' => 'cancelled']);

                foreach ($subOrder->items as $item) {
                    if ($item->vendor_listing_id) {
                        WarehouseInventory::where('vendor_listing_id', $item->vendor_listing_id)
                            ->lockForUpdate()
                            ->orderBy('id')
                            ->first()
                            ?->decrement('quantity_reserved', $item->quantity);
                    }
                }
            });

            $walletTxn = WalletTransaction::whereHas(
                'wallet',
                fn ($q) => $q->where('owner_type', 'customer')->where('owner_id', $customer->id)
            )
                ->where('source_type', 'order')
                ->where('source_id', $order->id)
                ->where('type', 'debit')
                ->first();

            if ($walletTxn) {
                $wallet = Wallet::where('id', $walletTxn->wallet_id)->lockForUpdate()->first();
                $wallet->increment('balance', $walletTxn->amount);
                $wallet->refresh();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $walletTxn->amount,
                    'balance_after' => $wallet->getRawOriginal('balance'),
                    'source_type' => 'order',
                    'source_id' => $order->id,
                    'description' => 'Refund for cancelled order '.$order->order_number,
                    'created_at' => now(),
                ]);
            }

            $giftCardTxn = GiftCardTransaction::where('order_id', $order->id)
                ->where('type', 'redemption')
                ->first();

            if ($giftCardTxn) {
                $giftCard = GiftCard::where('id', $giftCardTxn->gift_card_id)->lockForUpdate()->first();
                $giftCard->increment('balance', $giftCardTxn->amount);
                $giftCard->refresh();

                if ($giftCard->getRawOriginal('balance') > 0 && $giftCard->status !== 'active') {
                    $giftCard->update(['status' => 'active']);
                }

                GiftCardTransaction::create([
                    'gift_card_id' => $giftCard->id,
                    'order_id' => $order->id,
                    'amount' => $giftCardTxn->amount,
                    'balance_after' => $giftCard->getRawOriginal('balance'),
                    'type' => 'refund',
                    'performed_by_customer_id' => $customer->id,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'reason' => 'Cancelled by customer',
            ]);

            return $order->fresh();
        });

        $order->load(['subOrders.items', 'statusHistories']);

        return ApiResponse::success($this->buildOrderDetail($order), 'Order cancelled successfully');
    }

    public function invoice(Request $request, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->findOrder($customer, $orderNumber);

        if (! $order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        $order->load(['subOrders.items', 'subOrders.vendor:id,store_name']);

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'currency' => $order->currency,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status?->value,
            'shipping_address' => $order->shipping_address_snapshot,
            'summary' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping' => $order->shipping,
                'tax' => $order->tax,
                'cod_fee' => $order->cod_fee,
                'warranty_total' => $order->warranty_total,
                'total' => $order->total,
                'coupon_code_used' => $order->coupon_code_used,
            ],
            'sub_orders' => $order->subOrders->map(fn (SubOrder $subOrder) => [
                'sub_order_number' => $subOrder->sub_order_number,
                'vendor_name' => $subOrder->vendor?->store_name,
                'subtotal' => $subOrder->subtotal,
                'shipping' => $subOrder->shipping,
                'tax' => $subOrder->tax,
                'items' => $subOrder->items->map(fn (OrderItem $item) => [
                    'sku' => $item->sku,
                    'name_en' => $item->product_snapshot['name_en'] ?? null,
                    'name_ar' => $item->product_snapshot['name_ar'] ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal,
                    'line_discount' => $item->line_discount,
                    'line_tax' => $item->line_tax,
                    'line_total' => $item->line_total,
                ]),
            ]),
        ], 'Invoice data retrieved');
    }

    private function findOrder(Customer $customer, string $orderNumber): ?Order
    {
        return Order::where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->first();
    }

    private function buildOrderDetail(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status?->value,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status?->value,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'summary' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'shipping' => $order->shipping,
                'tax' => $order->tax,
                'cod_fee' => $order->cod_fee,
                'warranty_total' => $order->warranty_total,
                'total' => $order->total,
                'coupon_code_used' => $order->coupon_code_used,
            ],
            'shipping_address' => $order->shipping_address_snapshot,
            'sub_orders' => $order->subOrders->map(fn (SubOrder $subOrder) => $this->buildSubOrderDetail($subOrder)),
            'status_history' => $order->statusHistories->map(fn (OrderStatusHistory $history) => [
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'reason' => $history->reason,
                'created_at' => $history->created_at?->toIso8601String(),
            ]),
        ];
    }

    private function buildSubOrderDetail(SubOrder $subOrder): array
    {
        return [
            'sub_order_number' => $subOrder->sub_order_number,
            'status' => $subOrder->status?->value,
            'fulfillment_model' => $subOrder->fulfillment_model,
            'vendor' => [
                'id' => $subOrder->vendor?->id,
                'store_name' => $subOrder->vendor?->store_name,
            ],
            'subtotal' => $subOrder->subtotal,
            'shipping' => $subOrder->shipping,
            'tax' => $subOrder->tax,
            'is_exceptional_zone' => (int) ($subOrder->shipping_gap ?? 0) > 0,
            'carrier' => $subOrder->carrier?->name,
            'tracking_number' => $subOrder->tracking_number,
            'estimated_delivery_date' => $subOrder->estimated_delivery_date?->toDateString(),
            'shipped_at' => $subOrder->shipped_at?->toIso8601String(),
            'delivered_at' => $subOrder->delivered_at?->toIso8601String(),
            'items' => $subOrder->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name_en' => $item->product_snapshot['name_en'] ?? null,
                'name_ar' => $item->product_snapshot['name_ar'] ?? null,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_subtotal' => $item->line_subtotal,
                'line_discount' => $item->line_discount,
                'line_tax' => $item->line_tax,
                'line_total' => $item->line_total,
                'fulfillment_status' => $item->fulfillment_status?->value,
                'warranty' => $item->warrantyPurchase ? $this->buildWarranty($item->warrantyPurchase) : null,
            ]),
            'shipments' => $subOrder->relationLoaded('shipments')
                ? $subOrder->shipments->map(fn (Shipment $shipment) => $this->buildShipment($shipment))
                : [],
        ];
    }

    private function buildShipment(Shipment $shipment): array
    {
        return [
            'carrier_id' => $shipment->carrier_id,
            'tracking_number' => $shipment->tracking_number,
            'awb_label_url' => $shipment->awb_label_url,
            'weight_grams' => $shipment->weight_grams,
            'status' => $shipment->status?->value,
            'picked_up_at' => $shipment->picked_up_at?->toIso8601String(),
            'delivered_at' => $shipment->delivered_at?->toIso8601String(),
            'events' => $shipment->relationLoaded('trackingEvents')
                ? $shipment->trackingEvents->sortBy('occurred_at')->values()->map(fn ($event) => [
                    'status' => $event->status?->value,
                    'description' => $event->description,
                    'location' => $event->location,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                ])
                : [],
        ];
    }

    private function buildWarranty(WarrantyPurchase $warranty): array
    {
        return [
            'id' => $warranty->id,
            'plan_snapshot' => $warranty->plan_snapshot,
            'price_paid' => $warranty->price_paid,
            'currency' => $warranty->currency,
            'status' => $warranty->status,
            'coverage_starts_at' => $warranty->coverage_starts_at?->toDateString(),
            'coverage_ends_at' => $warranty->coverage_ends_at?->toDateString(),
        ];
    }
}
