<?php

namespace App\Services\Customer;

use App\Jobs\AutoAssignShippingMethodJob;
use App\Jobs\FraudDetectionJob;
use App\Jobs\NotifyVendorJob;
use App\Jobs\OrderConfirmationEmailJob;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartInventoryLock;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CountryShippingSetting;
use App\Models\ShippingRate;
use App\Models\SubOrder;
use App\Models\VendorListing;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Notifications\Vendor\NewOrderReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CustomerCheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly InventoryLockService $lockService,
        private readonly OrderAssemblerService $assembler,
        private readonly PaymentService $paymentService,
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * List shipping methods available for the cart's current contents at the given
     * address, with the total shipping cost estimated across all vendor sub-orders
     * (mirrors how prepare()/placeOrder() sum per-vendor shipping via the assembler).
     * COD surcharge is reported separately since the payment method isn't chosen yet.
     */
    public function availableShippingMethods(Cart $cart, Customer $customer, string $addressId): array
    {
        $address = $customer->addresses()->findOrFail($addressId);

        $cart->load('items.vendorListing');
        if ($cart->items->isEmpty()) {
            throw new \DomainException('Cart is empty.');
        }

        $destinationZoneId = $this->assembler->resolveDestinationZone($address->city_id);
        if (! $destinationZoneId) {
            return [];
        }

        $vendorSubtotals = $cart->items
            ->groupBy(fn($item) => $item->vendorListing->vendor_id)
            ->map(fn($items) => (int) $items->sum(fn($i) => $i->unit_price * $i->quantity));

        $rates = ShippingRate::where('destination_zone_id', $destinationZoneId)
            ->where('is_active', true)
            ->whereHas('shippingMethod', fn($q) => $q->where('is_active', true))
            ->with('shippingMethod')
            ->get()
            ->unique('shipping_method_id');

        $countrySettings = CountryShippingSetting::where('country_id', $address->country_id)
            ->whereIn('shipping_method_id', $rates->pluck('shipping_method_id'))
            ->get()
            ->keyBy('shipping_method_id');

        $options = [];
        foreach ($rates as $rate) {
            $setting = $countrySettings->get($rate->shipping_method_id);
            if ($setting && ! $setting->is_active) {
                continue;
            }

            $costCents = 0;
            foreach ($vendorSubtotals as $subtotal) {
                $costCents += $this->assembler->computeShipping(
                    $subtotal,
                    $rate->shipping_method_id,
                    $destinationZoneId,
                    false
                );
            }

            $options[] = [
                'method' => $rate->shippingMethod,
                'currency' => $cart->currency,
                'cost_cents' => $costCents,
                'is_free' => $costCents === 0,
                'cod_extra_fee_cents' => (int) $rate->cod_extra_fee,
                'free_shipping_threshold_cents' => $setting?->free_shipping_threshold_cents
                    ?? $rate->free_shipping_threshold,
            ];
        }

        usort(
            $options,
            fn($a, $b) => ($a['method']->display_priority ?? PHP_INT_MAX) <=> ($b['method']->display_priority ?? PHP_INT_MAX)
        );

        return $options;
    }

    /**
     * Prepare checkout: validate stock, acquire 15-min locks, compute totals.
     * READ-MOSTLY — locks are taken but released if the user abandons.
     */
    public function prepare(Cart $cart, Customer $customer, array $params): array
    {
        $cart->load(['items.vendorListing.warehouseInventories', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new \DomainException('Cart is empty.');
        }

        // Release any stale locks first so quantities are accurate
        $this->lockService->releaseAllForCart($cart, $customer->id);

        $address = Address::findOrFail($params['address_id']);
        $shippingMethodId = $params['shipping_method_id'];
        $paymentMethod = $params['payment_method'];
        $isCod = $paymentMethod === 'cod';

        // Lock all warehouse_inventory rows in consistent ascending id order to prevent deadlocks
        $itemsWithInventory = $cart->items->map(function ($item) {
            $inventory = $item->vendorListing->warehouseInventories->first();
            return ['item' => $item, 'inventory_id' => $inventory?->id];
        })->filter(fn($r) => $r['inventory_id'])->sortBy('inventory_id');

        $locks = [];
        foreach ($itemsWithInventory as $row) {
            $locks[] = $this->lockService->lock(
                $cart,
                $row['item']->vendorListing,
                $row['item']->quantity,
                $customer->id
            );
        }

        $blueprints = $this->assembler->buildSubOrderBlueprints(
            $cart,
            $shippingMethodId,
            $address->city_id,
            $isCod,
            $address->country_id,
        );

        $totalShipping = array_sum(array_column($blueprints, 'shipping'));
        $totalTax = array_sum(array_column($blueprints, 'tax'));
        $subtotal = (int) $cart->items->sum(fn($i) => $i->unit_price * $i->quantity);
        $discount = $cart->coupon ? $this->cartService->computeDiscount($cart->coupon, $subtotal) : 0;
        $codFee = $isCod ? $this->computeCodFee($blueprints) : 0;
        $grandTotal = max(0, $subtotal - $discount + $totalShipping + $totalTax + $codFee);

        // Update cart estimated totals
        $cart->update([
            'estimated_shipping' => $totalShipping,
            'estimated_tax'      => $totalTax,
            'subtotal'           => $subtotal,
            'discount'           => $discount,
            'estimated_total'    => $grandTotal,
        ]);

        return [
            'sub_orders'         => $blueprints,
            'subtotal'           => $subtotal,
            'discount'           => $discount,
            'shipping_total'     => $totalShipping,
            'tax_total'          => $totalTax,
            'cod_fee'            => $codFee,
            'grand_total'        => $grandTotal,
            'currency'           => $cart->currency,
            'locks_expire_at'    => now()->addMinutes(15)->toIso8601String(),
            'address'            => $address,
            'shipping_method_id' => $shippingMethodId,
            'payment_method'     => $paymentMethod,
        ];
    }

    /**
     * Place order — fully atomic. Idempotent on idempotency_key.
     */
    public function placeOrder(Cart $cart, Customer $customer, array $params, string $idempKey): Order
    {
        // 1. Idempotency check
        $existingKey = IdempotencyKey::where('key', $idempKey)->first();
        if ($existingKey?->response_body) {
            $orderId = $existingKey->reference_id;
            return Order::findOrFail($orderId);
        }

        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new \DomainException('Cart is empty.');
        }

        // 2. Verify all locks still valid
        $expiredLocks = CartInventoryLock::where('cart_id', $cart->id)
            ->where('expires_at', '<', now())
            ->pluck('vendor_listing_id');

        if ($expiredLocks->isNotEmpty()) {
            $names = VendorListing::whereIn('id', $expiredLocks)
                ->get()
                ->pluck('id')
                ->join(', ');
            throw new \DomainException(
                "Checkout session expired for items: {$names}. Please restart checkout.",
                409
            );
        }

        $locks = CartInventoryLock::where('cart_id', $cart->id)->get();
        if ($locks->isEmpty()) {
            throw new \DomainException('No inventory locks found. Please call /checkout/prepare first.');
        }

        $address = Address::findOrFail($params['address_id']);
        $paymentMethod = $params['payment_method'];
        $isCod = $paymentMethod === 'cod';

        $blueprints = $this->assembler->buildSubOrderBlueprints(
            $cart,
            $params['shipping_method_id'],
            $address->city_id,
            $isCod,
            $address->country_id
        );

        $subtotal = (int) $cart->items->sum(fn($i) => $i->unit_price * $i->quantity);
        $discount = $cart->coupon ? $this->cartService->computeDiscount($cart->coupon, $subtotal) : 0;
        $totalShipping = array_sum(array_column($blueprints, 'shipping'));
        $totalTax = array_sum(array_column($blueprints, 'tax'));
        $codFee = $isCod ? $this->computeCodFee($blueprints) : 0;
        $grandTotal = max(0, $subtotal - $discount + $totalShipping + $totalTax + $codFee);

        // Gateway fees are borne by the vendor, never the platform or customer (policy).
        // COD has no electronic payment processing fee, so methodConfig stays null for COD.
        $methodConfig = $isCod ? null : \App\Models\CountryPaymentMethod::where('method_type', $paymentMethod)
            ->where('country_id', $address->country_id)
            ->where('is_active', true)
            ->first();
        $blueprints = $this->assembler->applyGatewayFee($blueprints, $grandTotal, $methodConfig);

        $order = DB::transaction(function () use (
            $cart, $customer, $params, $idempKey, $blueprints,
            $address, $paymentMethod, $isCod,
            $subtotal, $discount, $totalShipping, $totalTax, $codFee, $grandTotal
        ) {
            // 4. Create order
            $orderNumber = $this->generateOrderNumber();
            $order = Order::create([
                'order_number'              => $orderNumber,
                'customer_id'               => $customer->id,
                'country_id'                => $address->country_id,
                'status'                    => 'placed',
                'currency'                  => $cart->currency,
                'subtotal'                  => $subtotal,
                'discount'                  => $discount,
                'shipping'                  => $totalShipping,
                'tax'                       => $totalTax,
                'cod_fee'                   => $codFee,
                'total'                     => $grandTotal,
                'coupon_id'                 => $cart->coupon_id,
                'coupon_code_used'          => $cart->coupon?->code,
                'payment_method'            => $paymentMethod,
                'payment_status'            => 'pending',
                'shipping_address_snapshot' => $this->snapshotAddress($address),
                'ip_address'                => request()->ip() ?? '0.0.0.0',
                'user_agent'                => request()->userAgent(),
                'placed_at'                 => now(),
            ]);

            // 5-9. Create sub_orders and order_items
            $subOrderSuffix = 'A';
            foreach ($blueprints as $blueprint) {
                $subOrder = SubOrder::create([
                    'order_id'           => $order->id,
                    'sub_order_number'   => $orderNumber . '-' . $subOrderSuffix,
                    'vendor_id'          => $blueprint['vendor_id'],
                    'warehouse_id'       => $blueprint['warehouse_id'],
                    'status'             => 'placed',
                    'fulfillment_model'  => $blueprint['fulfillment_model'],
                    'subtotal'           => $blueprint['subtotal'],
                    'shipping'           => $blueprint['shipping'],
                    'tax'                => $blueprint['tax'],
                    'platform_commission' => $blueprint['platform_commission'],
                    'gateway_fee'        => $blueprint['gateway_fee'],
                    'gateway_fee_rate'   => $blueprint['gateway_fee_rate'],
                    'vendor_payout'      => $blueprint['vendor_payout'],
                    // shipping_method_id is intentionally left unset here — the admin
                    // assigns it post-placement (filtered by destination zone), with
                    // AutoAssignShippingMethodJob falling back to the cheapest eligible
                    // method after 12 hours if the admin hasn't acted.
                    'sla_ship_deadline'  => now()->addHours(48),
                ]);

                $subOrderSuffix++;

                AutoAssignShippingMethodJob::dispatch($subOrder->id)->delay(now()->addHours(12));

                Notification::send($subOrder->vendor->vendorAdmins, new NewOrderReceived($subOrder));

                foreach ($blueprint['items'] as $cartItem) {
                    $listing = $cartItem->vendorListing;
                    $variant = $listing->productVariant;
                    $product = $variant->product;

                    $lineSubtotal = $cartItem->unit_price * $cartItem->quantity;
                    $lineTax = (int) round($lineSubtotal * ($blueprint['tax'] / max(1, $blueprint['subtotal'])));

                    $commissionData = $blueprint['items_commission_data'][$cartItem->id];

                    OrderItem::create([
                        'order_id'                => $order->id,
                        'sub_order_id'            => $subOrder->id,
                        'product_variant_id'      => $variant->id,
                        'vendor_listing_id'       => $listing->id,
                        'product_snapshot'        => [
                            'name'        => $product->name_en ?? $product->name_ar ?? '',
                            'sku'         => $variant->sku ?? $listing->vendor_sku ?? '',
                            'image'       => $product->images?->where('is_primary', true)->first()?->path ?? null,
                            'variant'     => $variant->option_values ?? [],
                            'seller_name' => $listing->vendor?->store_name ?? '',
                        ],
                        'vendor_id'               => $blueprint['vendor_id'],
                        'sku'                     => $variant->sku ?? $listing->vendor_sku ?? '',
                        'quantity'                => $cartItem->quantity,
                        'unit_price'              => $cartItem->unit_price,
                        'unit_cost_price'         => $listing->cost_price,
                        'line_subtotal'           => $lineSubtotal,
                        'line_discount'           => 0,
                        'line_tax'                => $lineTax,
                        'line_total'              => $lineSubtotal + $lineTax,
                        'commission_rate_pct'     => $commissionData['rate_pct'],
                        'commission_fixed_cents'  => $commissionData['fixed_cents'],
                        'commission_amount'       => $commissionData['amount'],
                        'commission_category_id'  => $commissionData['category_id'],
                        'fulfillment_status'      => 'pending',
                        'return_eligible_until'   => now()->addDays(14)->toDateString(),
                    ]);
                }
            }

            // 10. Convert reservation locks → outbound
            $this->lockService->confirmLocks($cart, $order->id, $customer->id);

            // 12. Create ledger entries (double-entry, balanced)
            $this->recordLedgerEntries($order, $blueprints, $grandTotal, $isCod);

            // 13. Coupon usage
            if ($cart->coupon) {
                CouponUsage::create([
                    'coupon_id'       => $cart->coupon->id,
                    'customer_id'     => $customer->id,
                    'order_id'        => $order->id,
                    'discount_amount' => $discount,
                    'used_at'         => now(),
                ]);
                $cart->coupon->increment('times_used');
            }

            // 16. Delete cart items and locks
            CartInventoryLock::where('cart_id', $cart->id)->delete();
            $cart->items()->delete();
            $cart->update([
                'coupon_id' => null, 'subtotal' => 0, 'discount' => 0,
                'estimated_shipping' => 0, 'estimated_tax' => 0, 'estimated_total' => 0,
            ]);

            // 15. Store idempotency key response
            IdempotencyKey::updateOrCreate(
                ['key' => $idempKey],
                [
                    'request_hash'    => hash('sha256', $idempKey),
                    'operation_type'  => 'place_order',
                    'reference_type'  => 'order',
                    'reference_id'    => $order->id,
                    'response_status' => 201,
                    'response_body'   => ['order_id' => $order->id, 'order_number' => $order->order_number],
                    'expires_at'      => now()->addDay(),
                ]
            );

            return $order;
        });

        // Payment is initiated AFTER the transaction commits so a gateway failure
        // can still trigger a clean rollback-equivalent (order stays, payment_status=failed).
        if (! $isCod) {
            $this->initiatePayment($order, $customer, $params);
        }

        // 17. Dispatch jobs
        OrderConfirmationEmailJob::dispatch($order->id);
        foreach ($blueprints as $bp) {
            NotifyVendorJob::dispatch($order->id, $bp['vendor_id']);
        }
        FraudDetectionJob::dispatch($order->id);

        return $order->fresh();
    }

    private function initiatePayment(Order $order, Customer $customer, array $params): void
    {
        $methodConfig = \App\Models\CountryPaymentMethod::where('payment_method', $order->payment_method)
            ->where('country_id', $customer->country_id)
            ->where('is_active', true)
            ->first();

        if (! $methodConfig) {
            $order->update(['payment_status' => 'failed']);
            return;
        }

        try {
            $this->paymentService->initiatePayment($order, $methodConfig);
        } catch (\Throwable $e) {
            $order->update(['payment_status' => 'failed']);
            throw $e;
        }
    }

    private function recordLedgerEntries(Order $order, array $blueprints, int $grandTotal, bool $isCod): void
    {
        $groupId = $this->ledgerService->newGroupId();
        // vendor_payout on each blueprint is already net of gateway_fee (see applyGatewayFee()).
        // The gateway fee is a pass-through to the payment gateway, not platform revenue, so we
        // add it back here to get the gross vendor payout for the platform revenue formula.
        $totalVendorPayout = array_sum(array_column($blueprints, 'vendor_payout'));
        $totalGatewayFee = array_sum(array_column($blueprints, 'gateway_fee'));
        $platformRevenue = $grandTotal - ($totalVendorPayout + $totalGatewayFee) - $order->tax;

        $entries = [
            [
                'account_type'        => 'customer_payment',
                'account_holder_type' => 'customers',
                'account_holder_id'   => $order->customer_id,
                'debit'               => $grandTotal,
                'credit'              => 0,
                'currency'            => $order->currency,
                'reference_type'      => 'order',
                'reference_id'        => $order->id,
                'description'         => "Customer payment for order {$order->order_number}",
            ],
            [
                'account_type'        => 'platform_revenue',
                'account_holder_type' => null,
                'account_holder_id'   => null,
                'debit'               => 0,
                'credit'              => max(0, $platformRevenue),
                'currency'            => $order->currency,
                'reference_type'      => 'order',
                'reference_id'        => $order->id,
                'description'         => "Platform revenue for order {$order->order_number}",
            ],
            [
                'account_type'        => 'tax_payable',
                'account_holder_type' => null,
                'account_holder_id'   => null,
                'debit'               => 0,
                'credit'              => $order->tax,
                'currency'            => $order->currency,
                'reference_type'      => 'order',
                'reference_id'        => $order->id,
                'description'         => "VAT for order {$order->order_number}",
            ],
        ];

        // Per-vendor payable entries (vendor_payout here is already net of gateway_fee)
        foreach ($blueprints as $bp) {
            $entries[] = [
                'account_type'        => 'seller_payable',
                'account_holder_type' => 'vendors',
                'account_holder_id'   => $bp['vendor_id'],
                'debit'               => 0,
                'credit'              => $bp['vendor_payout'],
                'currency'            => $order->currency,
                'reference_type'      => 'order',
                'reference_id'        => $order->id,
                'description'         => "Vendor payout for sub-order (vendor {$bp['vendor_id']})",
            ];

            if ($bp['gateway_fee'] > 0) {
                $entries[] = [
                    'account_type'        => 'gateway_fee',
                    'account_holder_type' => 'vendors',
                    'account_holder_id'   => $bp['vendor_id'],
                    'debit'               => 0,
                    'credit'              => $bp['gateway_fee'],
                    'currency'            => $order->currency,
                    'reference_type'      => 'order',
                    'reference_id'        => $order->id,
                    'description'         => "Payment gateway fee, vendor-borne (vendor {$bp['vendor_id']})",
                ];
            }
        }

        // Balance check: the record() method in LedgerService will throw if unbalanced.
        // We adjust platform_revenue to absorb any cent-level rounding.
        $totalCredit = array_sum(array_column($entries, 'credit'));
        if ($totalCredit !== $grandTotal) {
            $entries[1]['credit'] += ($grandTotal - $totalCredit);
        }

        $this->ledgerService->record($groupId, $entries);
    }

    private function computeCodFee(array $blueprints): int
    {
        return array_sum(array_column($blueprints, 'shipping')) > 0
            ? 0  // COD fee is already baked into shipping computation via cod_extra_fee
            : 0;
    }

    private function generateOrderNumber(): string
    {
        $year = now()->year;
        $seq = DB::selectOne("SELECT COUNT(*) + 1 AS seq FROM orders WHERE YEAR(placed_at) = ?", [$year]);
        return sprintf('ON-%d-%06d', $year, $seq->seq ?? 1);
    }

    private function snapshotAddress(Address $address): array
    {
        return [
            'recipient_name'  => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'area'            => $address->area,
            'street_address'  => $address->street_address,
            'building'        => $address->building,
            'floor'           => $address->floor,
            'apartment'       => $address->apartment,
            'postal_code'     => $address->postal_code,
            'landmark'        => $address->landmark,
            'city_id'         => $address->city_id,
            'country_id'      => $address->country_id,
        ];
    }
}
