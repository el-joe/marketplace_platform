<?php

namespace App\Http\Controllers\Customer;

use App\Enums\GlobalSystemType;
use App\Enums\VendorListingStatus;
use App\Events\SubOrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutPrepareRequest;
use App\Http\Requests\Customer\PlaceOrderRequest;
use App\Http\Requests\Customer\ShippingMethodsRequest;
use App\Http\Resources\Customer\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\AutoAssignShippingMethodJob;
use App\Jobs\FraudDetectionJob;
use App\Jobs\NotifyVendorJob;
use App\Jobs\OrderConfirmationEmailJob;
use App\Models\Address;
use App\Models\CountryPaymentMethod;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\ShippingMethod;
use App\Models\SubOrder;
use App\Models\VendorListing;
use App\Models\WarehouseInventory;
use App\Models\WarrantyPurchase;
use App\Services\Customer\CartService;
use App\Services\Customer\CheckoutCalculationService;
use App\Services\Customer\ListingIdentifierService;
use App\Services\GiftCardService;
use App\Services\PaymentService;
use App\Services\ShippingCalculationService;
use App\Services\WarrantyPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutCalculationService $calculationService,
        private readonly ListingIdentifierService $listingIdentifierService,
        private readonly PaymentService $paymentService,
        private readonly GiftCardService $giftCardService,
        private readonly WarrantyPlanService $warrantyPlanService,
        private readonly ShippingCalculationService $shippingCalculationService,
    ) {}

    public function shippingMethods(ShippingMethodsRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');

        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);
        $cart->load('items.vendorListing.productVariant');

        $address = $customer->addresses()
            ->where('is_default', 1)
            ->whereIn('address_type', ['shipping', 'both'])
            ->first();

        if (! $address) {
            $methods = ShippingMethod::where('is_active', 1)
                ->orderBy('display_priority')
                ->get();

            return ApiResponse::success([
                'shipping_methods' => $methods->map(fn (ShippingMethod $method) => [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'badge_label_en' => $method->badge_label_en,
                    'badge_label_ar' => $method->badge_label_ar,
                    'badge_color_hex' => $method->badge_color_hex,
                    'badge_text_color_hex' => $method->badge_text_color_hex,
                    'delivery_days_min' => $method->min_delivery_days,
                    'delivery_days_max' => $method->max_delivery_days,
                    'fee_cents' => 0,
                    'is_free' => true,
                    'cod_extra_fee_cents' => 0,
                    'cod_available' => false,
                ])->values(),
                'destination_zone' => null,
                'cod_available_for_address' => false,
            ], 'Shipping methods retrieved');
        }

        $address->load('city.shippingZone');
        $zoneId = $address->city?->shipping_zone_id;

        $methodsQuery = ShippingMethod::where('is_active', 1);
        if ($zoneId) {
            $methodsQuery->whereHas('shippingRates', fn ($q) => $q->where('destination_zone_id', $zoneId)->where('is_active', 1))
                ->with(['shippingRates' => fn ($q) => $q->where('destination_zone_id', $zoneId)->where('is_active', 1)]);
        }
        $methods = $methodsQuery->orderBy('display_priority')->get();

        $codAvailableForAddress = (bool) ($address->city?->cod_available && $country->cod_available);
        $cartItems = $cart->items->all();

        $shippingMethods = $methods->map(function (ShippingMethod $method) use ($address, $country, $cartItems) {
            $calc = $this->calculationService->calculateShipping($address, $country, $method->id, $cartItems, false);
            $codCalc = $this->calculationService->calculateShipping($address, $country, $method->id, $cartItems, true);

            return [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'badge_label_en' => $method->badge_label_en,
                'badge_label_ar' => $method->badge_label_ar,
                'badge_color_hex' => $method->badge_color_hex,
                'badge_text_color_hex' => $method->badge_text_color_hex,
                'delivery_days_min' => $method->min_delivery_days,
                'delivery_days_max' => $method->max_delivery_days,
                'fee_cents' => $calc['fee'],
                'is_free' => $calc['is_free'],
                'cod_extra_fee_cents' => $codCalc['cod_extra_fee'],
                'cod_available' => $calc['cod_available'],
            ];
        })->values();

        return ApiResponse::success([
            'shipping_methods' => $shippingMethods,
            'destination_zone' => $address->city?->shippingZone?->name,
            'cod_available_for_address' => $codAvailableForAddress,
        ], 'Shipping methods retrieved');
    }

    public function prepare(CheckoutPrepareRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');
        $validated = $request->validated();

        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);
        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product']);

        if ($cart->items->isEmpty()) {
            return ApiResponse::error('Cart is empty.', [], 422);
        }

        $address = $customer->addresses()->find($validated['address_id']);
        if (! $address) {
            return ApiResponse::error('Address not found.', [], 404);
        }

        $isCod = $validated['payment_method'] === 'cod';
        if ($isCod && ! $this->codAvailable($address, $country)) {
            return ApiResponse::error('Cash on delivery is not available for your location.', [], 422);
        }

        $cartItems = $cart->items->all();

        $shippingResult = $this->calculationService->calculateShipping(
            $address,
            $country,
            $validated['shipping_method_id'],
            $cartItems,
            $isCod
        );

        $shippingMethod = ShippingMethod::find($validated['shipping_method_id']);

        $codFeeCents = $isCod ? $shippingResult['cod_extra_fee'] : 0;

        $warrantyResult = $this->calculationService->resolveWarrantySelections(
            $cartItems,
            $validated['warranty_selections'] ?? [],
            $country,
            $cart->currency,
            $this->warrantyPlanService,
        );
        $warrantyTotalCents = $warrantyResult['total'];

        $couponResponse = null;
        $discountCents = 0;
        if (! empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', $validated['coupon_code'])->first();
            if (! $coupon) {
                return ApiResponse::error('Invalid coupon code.', [], 422);
            }

            $subtotal = (int) collect($cartItems)->sum(fn ($i) => $i->unit_price * $i->quantity);
            $couponResult = $this->calculationService->applyCoupon($coupon, $customer, $subtotal, $cart->currency, $cartItems);

            if ($couponResult['error']) {
                return ApiResponse::error($couponResult['error'], [], 422);
            }

            $discountCents = $couponResult['discount'];
            $couponResponse = [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'discount_cents' => $discountCents,
            ];
        }

        $preGiftCardSummary = $this->calculationService->buildOrderSummary(
            $cartItems,
            $shippingResult['fee'],
            $codFeeCents,
            $discountCents,
            $country,
            0,
            $warrantyTotalCents
        );

        $giftCardResponse = null;
        $giftCardAppliedCents = 0;
        if (! empty($validated['gift_card_code'])) {
            $giftCardResult = $this->calculationService->applyGiftCard(
                $validated['gift_card_code'],
                $cart->currency,
                $preGiftCardSummary['total_cents']
            );

            if ($giftCardResult['error']) {
                return ApiResponse::error($giftCardResult['error'], [], 422);
            }

            $giftCardAppliedCents = $giftCardResult['applied_cents'];
            $giftCardResponse = [
                'code' => $validated['gift_card_code'],
                'applied_cents' => $giftCardAppliedCents,
            ];
        }

        $summary = $this->calculationService->buildOrderSummary(
            $cartItems,
            $shippingResult['fee'],
            $codFeeCents,
            $discountCents,
            $country,
            $giftCardAppliedCents,
            $warrantyTotalCents
        );

        $availablePaymentMethods = CountryPaymentMethod::where('country_id', $country->id)
            ->where('is_active', true)
            ->pluck('method_type')
            ->values()
            ->all();
        if ($country->cod_available && ! in_array('cod', $availablePaymentMethods, true)) {
            $availablePaymentMethods[] = 'cod';
        }

        $items = collect($cartItems)->map(function ($item) {
            $listing = $item->vendorListing;
            $isAdminListing = $listing->global_system_type === GlobalSystemType::ExpressFbn;
            $product = $listing->productVariant->product;

            return [
                'listing_id' => $listing->id,
                'listing_ref' => $this->listingIdentifierService->buildListingRef($listing),
                'sku' => $listing->productVariant->sku,
                'name_en' => $product->name_en,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price,
                'line_total' => $item->unit_price * $item->quantity,
                'thumbnail' => $product->images->firstWhere('is_primary', true)?->url ?? $product->images->first()?->url,
                'vendor_name' => $isAdminListing ? 'noon' : $listing->vendor?->store_name,
                'is_admin_listing' => $isAdminListing,
            ];
        })->values();

        $deliveryDisplay = $this->buildDeliveryDisplay($cartItems, $shippingResult['fee'], $address, $country);

        return ApiResponse::success([
            'order_summary' => $summary,
            'shipping' => [
                'method_id' => $validated['shipping_method_id'],
                'method_name' => $shippingMethod?->name,
                'fee_cents' => $shippingResult['fee'],
                'is_free' => $shippingResult['is_free'],
                'estimated_delivery_days_min' => $shippingMethod?->min_delivery_days,
                'estimated_delivery_days_max' => $shippingMethod?->max_delivery_days,
                'delivery_fee' => $deliveryDisplay['delivery_fee'],
                'delivery_label_en' => $deliveryDisplay['delivery_label_en'],
                'delivery_label_ar' => $deliveryDisplay['delivery_label_ar'],
                'is_free_delivery' => $deliveryDisplay['is_free_delivery'],
                'vendor_delivery' => $deliveryDisplay['vendor_delivery'],
            ],
            'address' => [
                'id' => $address->id,
                'recipient_name' => $address->recipient_name,
                'street_address' => $address->street_address,
                'city' => $address->city?->name_en,
                'country' => $country->name_en,
            ],
            'payment_method' => $validated['payment_method'],
            'available_payment_methods' => $availablePaymentMethods,
            'coupon' => $couponResponse,
            'gift_card' => $giftCardResponse,
            'items' => $items,
        ], 'Checkout preview ready');
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');
        $validated = $request->validated();

        $existingTransaction = PaymentTransaction::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingTransaction && in_array($existingTransaction->status->value, ['pending', 'succeeded'], true)) {
            $order = Order::where('id', $existingTransaction->order_id)->first();
            if ($order) {
                return ApiResponse::error('Order already placed.', ['order_number' => $order->order_number], 409);
            }
        }

        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);
        $cart->load([
            'items.vendorListing.vendor',
            'items.vendorListing.productVariant.product.category',
            'items.vendorListing.productVariant.product.brand',
            'items.vendorListing.productVariant.product.images',
            'items.vendorListing.warehouseInventories',
        ]);

        if ($cart->items->isEmpty()) {
            return ApiResponse::error('Cart is empty.', [], 422);
        }

        foreach ($cart->items as $item) {
            if ($item->vendorListing->status !== VendorListingStatus::Active) {
                return ApiResponse::error("Listing {$item->vendorListing->id} is no longer available.", [], 422);
            }

            $available = $item->vendorListing->warehouseInventories->sum('quantity_available');
            if ($available < $item->quantity) {
                return ApiResponse::error("Insufficient stock for one or more items. Only {$available} unit(s) available.", [], 422);
            }
        }

        $address = $customer->addresses()->find($validated['address_id']);
        if (! $address) {
            return ApiResponse::error('Address not found.', [], 404);
        }

        $isCod = $validated['payment_method'] === 'cod';
        if ($isCod && ! $this->codAvailable($address, $country)) {
            return ApiResponse::error('Cash on delivery is not available for your location.', [], 422);
        }

        $cartItems = $cart->items->all();

        $shippingResult = $this->calculationService->calculateShipping(
            $address,
            $country,
            $validated['shipping_method_id'],
            $cartItems,
            $isCod
        );
        $shippingFeeCents = $shippingResult['fee'];
        $codFeeCents = $isCod ? $shippingResult['cod_extra_fee'] : 0;

        $warrantyResult = $this->calculationService->resolveWarrantySelections(
            $cartItems,
            $validated['warranty_selections'] ?? [],
            $country,
            $cart->currency,
            $this->warrantyPlanService,
        );
        $warrantyTotalCents = $warrantyResult['total'];
        $warrantySelections = $warrantyResult['selections'];

        $coupon = null;
        $discountCents = 0;
        if (! empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', $validated['coupon_code'])->first();
            if (! $coupon) {
                return ApiResponse::error('Invalid coupon code.', [], 422);
            }

            $subtotal = (int) collect($cartItems)->sum(fn ($i) => $i->unit_price * $i->quantity);
            $couponResult = $this->calculationService->applyCoupon($coupon, $customer, $subtotal, $cart->currency, $cartItems);

            if ($couponResult['error']) {
                return ApiResponse::error($couponResult['error'], [], 422);
            }

            $discountCents = $couponResult['discount'];
        }

        $preGiftCardSummary = $this->calculationService->buildOrderSummary(
            $cartItems,
            $shippingFeeCents,
            $codFeeCents,
            $discountCents,
            $country,
            0,
            $warrantyTotalCents
        );

        $giftCard = null;
        $giftCardAppliedCents = 0;
        if (! empty($validated['gift_card_code'])) {
            $giftCardResult = $this->calculationService->applyGiftCard(
                $validated['gift_card_code'],
                $cart->currency,
                $preGiftCardSummary['total_cents']
            );

            if ($giftCardResult['error']) {
                return ApiResponse::error($giftCardResult['error'], [], 422);
            }

            $giftCard = $giftCardResult['gift_card'];
            $giftCardAppliedCents = $giftCardResult['applied_cents'];
        }

        $summary = $this->calculationService->buildOrderSummary(
            $cartItems,
            $shippingFeeCents,
            $codFeeCents,
            $discountCents,
            $country,
            $giftCardAppliedCents,
            $warrantyTotalCents
        );

        $attribution = session('marketer_attribution', []);

        $address->loadMissing('city');
        $shippingZoneId = $address->city?->shipping_zone_id;

        try {
            $result = DB::transaction(function () use (
                $customer, $country, $address, $validated, $coupon,
                $cartItems, $summary, $shippingFeeCents, $attribution,
                $giftCard, $giftCardAppliedCents, $warrantySelections, $shippingZoneId
            ) {
                $order = Order::create([
                    'order_number' => $this->generateOrderNumber(),
                    'customer_id' => $customer->id,
                    'country_id' => $country->id,
                    'status' => 'placed',
                    'currency' => $country->currency_code,
                    'subtotal' => $summary['subtotal_cents'],
                    'discount' => $summary['discount_cents'],
                    'shipping' => $summary['shipping_cents'],
                    'tax' => $summary['tax_cents'],
                    'cod_fee' => $summary['cod_fee'],
                    'warranty_total' => $summary['warranty_total_cents'],
                    'total' => $summary['total_cents'],
                    'coupon_id' => $coupon?->id,
                    'coupon_code_used' => $coupon?->code,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'shipping_address_snapshot' => $this->buildAddressSnapshot($address),
                    'customer_notes' => $validated['customer_notes'] ?? null,
                    'ip_address' => request()->ip() ?? '0.0.0.0',
                    'user_agent' => request()->userAgent(),
                    'placed_at' => now(),
                    'marketer_id' => $attribution['marketer_id'] ?? null,
                    'marketer_campaign_id' => $attribution['campaign_id'] ?? null,
                ]);

                $grouped = collect($cartItems)->groupBy(fn ($item) => $item->vendorListing->vendor_id);
                $subtotalAll = max(1, $summary['subtotal_cents']);
                $subOrders = [];
                $subOrderDeliveryDisplay = [];
                $idx = 0;

                foreach ($grouped as $vendorId => $items) {
                    $idx++;
                    $vendorSubtotal = (int) $items->sum(fn ($i) => $i->unit_price * $i->quantity);
                    $firstListing = $items->first()->vendorListing;
                    $isFbn = $firstListing->global_system_type === GlobalSystemType::ExpressFbn;

                    $vendorShipping = $isFbn
                        ? 0
                        : (int) round($shippingFeeCents * ($vendorSubtotal / $subtotalAll));

                    $vendorTax = (int) round($vendorSubtotal * ((float) $country->vat_rate / 100));

                    $itemCommissions = [];
                    $reservedInventories = [];
                    $totalCommission = 0;
                    foreach ($items as $cartItem) {
                        $commission = $this->calculationService->calculateCommission(
                            $cartItem->vendorListing,
                            $cartItem->quantity,
                            $cartItem->unit_price
                        );
                        $itemCommissions[$cartItem->id] = $commission;
                        $totalCommission += $commission['commission_amount'];

                        $inventory = WarehouseInventory::where('vendor_listing_id', $cartItem->vendor_listing_id)
                            ->lockForUpdate()
                            ->orderBy('id')
                            ->first();

                        if (! $inventory || $inventory->quantity_available < $cartItem->quantity) {
                            throw new \DomainException(
                                'Insufficient stock for one or more items. Please update your cart.'
                            );
                        }

                        $inventory->increment('quantity_reserved', $cartItem->quantity);
                        $inventory->refresh();

                        InventoryMovement::create([
                            'warehouse_inventory_id' => $inventory->id,
                            'movement_type' => 'reservation',
                            'quantity_delta' => $cartItem->quantity,
                            'quantity_after' => $inventory->quantity_on_hand,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'reason' => 'Checkout reservation',
                            'created_by_user_id' => $customer->id,
                        ]);

                        $reservedInventories[$cartItem->id] = $inventory;
                    }

                    $warehouseId = $reservedInventories[$items->first()->id]->warehouse_id;

                    $deliveryDisplay = $this->shippingCalculationService->calculate(
                        $vendorShipping,
                        $firstListing,
                        $shippingZoneId,
                        $country->currency_code
                    );

                    $subOrder = SubOrder::create([
                        'order_id' => $order->id,
                        'sub_order_number' => $order->order_number.'-'.str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                        'vendor_id' => $vendorId,
                        'warehouse_id' => $warehouseId,
                        'status' => 'placed',
                        'fulfillment_model' => $firstListing->fulfillment_model,
                        'subtotal' => $vendorSubtotal,
                        'shipping' => $vendorShipping,
                        'admin_subsidy' => $deliveryDisplay['admin_subsidy'],
                        'vendor_deduction' => $deliveryDisplay['vendor_deduction'],
                        'tax' => $vendorTax,
                        'platform_commission' => $totalCommission,
                        'gateway_fee' => 0,
                        'gateway_fee_rate' => 0,
                        'vendor_payout' => $vendorSubtotal - $totalCommission,
                        'shipping_method_id' => $validated['shipping_method_id'],
                        'sla_ship_deadline' => now()->addHours(24),
                    ]);

                    $subOrderDeliveryDisplay[$subOrder->id] = $deliveryDisplay;

                    foreach ($items as $cartItem) {
                        $listing = $cartItem->vendorListing;
                        $commission = $itemCommissions[$cartItem->id];
                        $lineSubtotal = $cartItem->unit_price * $cartItem->quantity;
                        $lineTax = (int) round($cartItem->unit_price * $cartItem->quantity * ((float) $country->vat_rate / 100));

                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'sub_order_id' => $subOrder->id,
                            'product_variant_id' => $listing->product_variant_id,
                            'vendor_listing_id' => $listing->id,
                            'product_snapshot' => $this->buildProductSnapshot($listing),
                            'vendor_id' => $listing->vendor_id,
                            'sku' => $listing->productVariant->sku,
                            'quantity' => $cartItem->quantity,
                            'unit_price' => $cartItem->unit_price,
                            'unit_cost_price' => $listing->cost_price,
                            'line_subtotal' => $lineSubtotal,
                            'line_discount' => 0,
                            'line_tax' => $lineTax,
                            'line_total' => $lineSubtotal + $lineTax,
                            'commission_rate_pct' => $commission['commission_rate_pct'],
                            'commission_fixed' => $commission['commission_fixed'],
                            'commission_category_id' => $commission['commission_category_id'],
                            'commission_amount' => $commission['commission_amount'],
                            'fulfillment_status' => 'pending',
                            'return_eligible_until' => null,
                        ]);

                        if (isset($warrantySelections[$cartItem->id])) {
                            $plan = $warrantySelections[$cartItem->id]['plan'];

                            WarrantyPurchase::create([
                                'customer_id' => $customer->id,
                                'order_id' => $order->id,
                                'order_item_id' => $orderItem->id,
                                'warranty_plan_id' => $plan->id,
                                'plan_snapshot' => [
                                    'name_en' => $plan->name_en,
                                    'name_ar' => $plan->name_ar,
                                    'duration_months' => $plan->duration_months,
                                    'features_en' => $plan->features_en,
                                    'features_ar' => $plan->features_ar,
                                    'price' => $plan->price,
                                    'currency' => $plan->currency,
                                ],
                                'price_paid' => $warrantySelections[$cartItem->id]['price'],
                                'currency' => $order->currency,
                                'status' => 'pending',
                                'coverage_starts_at' => null,
                                'coverage_ends_at' => null,
                            ]);
                        }
                    }

                    $subOrders[] = $subOrder;
                }

                if ($giftCard && $giftCardAppliedCents > 0) {
                    $this->giftCardService->redeem($giftCard, $giftCardAppliedCents, $order, $customer);
                }

                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'customer_id' => $customer->id,
                        'order_id' => $order->id,
                        'discount_amount' => $summary['discount_cents'],
                        'used_at' => now(),
                    ]);
                    $coupon->increment('times_used');
                }

                return ['order' => $order, 'sub_orders' => $subOrders, 'delivery_display' => $subOrderDeliveryDisplay];
            });
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $subOrders = $result['sub_orders'];
        $order = $result['order'];
        $deliveryDisplayBySubOrder = $result['delivery_display'];

        if ($isCod) {
            // Cash hasn't changed hands yet — this transaction (and order.payment_status,
            // already 'pending' from creation above) only becomes 'succeeded'/'captured' once
            // the delivery agent actually collects payment (see AssignmentController::confirmDelivery).
            PaymentTransaction::create([
                'id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'type' => 'sale',
                'gateway' => 'cod',
                'gateway_transaction_id' => 'COD-'.$order->order_number,
                'idempotency_key' => $validated['idempotency_key'],
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => 'pending',
                'processed_at' => null,
            ]);
        } else {
            $methodConfig = CountryPaymentMethod::where('method_type', $validated['payment_method'])
                ->where('country_id', $country->id)
                ->where('is_active', true)
                ->first();

            if (! $methodConfig) {
                $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                $this->releaseReservedInventory($order);
            } else {
                try {
                    $paymentResult = $this->paymentService->initiatePayment($order, $methodConfig, $validated['idempotency_key']);
                    if (! $paymentResult->success) {
                        $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                        $this->releaseReservedInventory($order);
                    }
                } catch (\Throwable $e) {
                    $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                    $this->releaseReservedInventory($order);
                }
            }
        }

        $this->cartService->clearCart($cart);

        foreach ($subOrders as $subOrder) {
            dispatch(new AutoAssignShippingMethodJob($subOrder->id))->delay(now()->addHours(12));
            SubOrderPlaced::dispatch($subOrder);
            NotifyVendorJob::dispatch($order->id, $subOrder->vendor_id);
        }
        OrderConfirmationEmailJob::dispatch($order->id);
        FraudDetectionJob::dispatch($order->id);

        $order = $order->fresh();
        $order->load('subOrders.items.vendorListing');

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'total_cents' => $order->total,
            'warranty_total_cents' => $order->warranty_total,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'sub_orders' => $order->subOrders->map(function (SubOrder $so) use ($deliveryDisplayBySubOrder) {
                $display = $deliveryDisplayBySubOrder[$so->id] ?? null;

                return [
                'sub_order_number' => $so->sub_order_number,
                'vendor' => $so->vendor?->store_name,
                'status' => $so->status->value,
                'fulfillment_model' => $so->fulfillment_model,
                'delivery_fee' => $display['customer_display_cents'] ?? $so->shipping,
                'delivery_label_en' => $display['subsidy_label_en'] ?? null,
                'delivery_label_ar' => $display['subsidy_label_ar'] ?? null,
                'is_free_delivery' => $display ? $display['customer_display_cents'] === 0 : false,
                'items' => $so->items->map(fn (OrderItem $item) => [
                    'listing_ref' => $item->vendorListing
                        ? $this->listingIdentifierService->buildListingRef($item->vendorListing)
                        : null,
                    'sku' => $item->sku,
                    'name_en' => $item->product_snapshot['name_en'] ?? null,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]),
                ];
            }),
        ], 'Order placed successfully', 201);
    }

    public function confirmation(Request $request,$country, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();

        $order = Order::with(['subOrders.items.vendorListing', 'subOrders.vendor'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return ApiResponse::success(new OrderResource($order));
    }

    private function codAvailable(Address $address, $country): bool
    {
        $address->loadMissing('city');

        return (bool) ($address->city?->cod_available && $country->cod_available);
    }

    /**
     * Build the customer-facing delivery display (per vendor + aggregate) using
     * ShippingCalculationService, without altering the actual shipping fee used
     * for the order total.
     *
     * @param  array<\App\Models\CartItem>  $cartItems
     */
    private function buildDeliveryDisplay(array $cartItems, int $totalShippingFeeCents, Address $address, $country): array
    {
        $address->loadMissing('city');
        $shippingZoneId = $address->city?->shipping_zone_id;

        $subtotalAll = max(1, (int) collect($cartItems)->sum(fn ($i) => $i->unit_price * $i->quantity));
        $grouped = collect($cartItems)->groupBy(fn ($item) => $item->vendorListing->vendor_id);

        $vendorDelivery = [];
        $aggregateCustomerDisplayCents = 0;

        foreach ($grouped as $vendorId => $items) {
            $vendorSubtotal = (int) $items->sum(fn ($i) => $i->unit_price * $i->quantity);
            $firstListing = $items->first()->vendorListing;
            $isFbn = $firstListing->global_system_type === GlobalSystemType::ExpressFbn;

            $vendorShippingCents = $isFbn
                ? 0
                : (int) round($totalShippingFeeCents * ($vendorSubtotal / $subtotalAll));

            $display = $this->shippingCalculationService->calculate(
                $vendorShippingCents,
                $firstListing,
                $shippingZoneId,
                $country->currency_code
            );

            $aggregateCustomerDisplayCents += $display['customer_display_cents'];

            // Customer-safe subset only — admin_subsidy / vendor_deduction
            // are internal ledger figures and must never reach the customer API response.
            $vendorDelivery[] = [
                'vendor_id' => $vendorId,
                'display_mode' => $display['display_mode'],
                'delivery_fee' => $display['customer_display_cents'],
                'delivery_label_en' => $display['subsidy_label_en'],
                'delivery_label_ar' => $display['subsidy_label_ar'],
                'is_free_delivery' => $display['customer_display_cents'] === 0,
            ];
        }

        if (count($vendorDelivery) === 1) {
            $labelEn = $vendorDelivery[0]['subsidy_label_en'];
            $labelAr = $vendorDelivery[0]['subsidy_label_ar'];
            $displayMode = $vendorDelivery[0]['display_mode'];
        } else {
            $labelEn = $aggregateCustomerDisplayCents === 0 ? 'Free Delivery' : 'Delivery charges apply';
            $labelAr = $aggregateCustomerDisplayCents === 0 ? 'توصيل مجاني' : 'يتم تطبيق رسوم التوصيل';
            // Multiple vendors can land on different display modes; when they disagree,
            // 'subsidized' is the safest umbrella label (never overstates "free").
            $modes = array_unique(array_column($vendorDelivery, 'display_mode'));
            $displayMode = count($modes) === 1 ? $modes[0] : ($aggregateCustomerDisplayCents === 0 ? 'free' : 'subsidized');
        }

        return [
            'delivery_fee' => $aggregateCustomerDisplayCents,
            'delivery_display_mode' => $displayMode,
            'delivery_label_en' => $labelEn,
            'delivery_label_ar' => $labelAr,
            'is_free_delivery' => $aggregateCustomerDisplayCents === 0,
            'vendor_delivery' => $vendorDelivery,
        ];
    }

    private function releaseReservedInventory(Order $order): void
    {
        $order->loadMissing('subOrders.items');

        DB::transaction(function () use ($order) {
            foreach ($order->subOrders as $subOrder) {
                foreach ($subOrder->items as $item) {
                    $inventory = WarehouseInventory::where('vendor_listing_id', $item->vendor_listing_id)
                        ->where('warehouse_id', $subOrder->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $inventory) {
                        continue;
                    }

                    $inventory->decrement('quantity_reserved', $item->quantity);
                    $inventory->refresh();

                    InventoryMovement::create([
                        'warehouse_inventory_id' => $inventory->id,
                        'movement_type' => 'reservation_release',
                        'quantity_delta' => -$item->quantity,
                        'quantity_after' => $inventory->quantity_on_hand,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'reason' => 'Payment failed',
                        'created_by_user_id' => $order->customer_id,
                    ]);
                }
            }
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'NOON-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }

    private function buildAddressSnapshot(Address $address): array
    {
        $address->loadMissing('city');

        return [
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'country_id' => $address->country_id,
            'city_id' => $address->city_id,
            'city' => [
                'en' => $address->city?->name_en,
                'ar' => $address->city?->name_ar,
            ],
            'area' => $address->area,
            'street_address' => $address->street_address,
            'building' => $address->building,
            'floor' => $address->floor,
            'apartment' => $address->apartment,
            'postal_code' => $address->postal_code,
            'landmark' => $address->landmark,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
    }

    private function buildProductSnapshot(VendorListing $listing): array
    {
        $variant = $listing->productVariant;
        $product = $variant->product;
        $thumbnail = $product->images->firstWhere('is_primary', true)?->url ?? $product->images->first()?->url;

        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->listingIdentifierService->buildListingRef($listing),
            'sku' => $variant->sku,
            'vendor_sku' => $listing->vendor_sku,
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar,
            'price' => $listing->price,
            'currency' => $listing->currency,
            'condition' => $listing->condition,
            'global_system_type' => $listing->global_system_type?->value,
            'thumbnail_url' => $thumbnail,
            'brand_name' => $product->brand?->name_en,
            'category_name' => $product->category?->name_en,
        ];
    }
}
