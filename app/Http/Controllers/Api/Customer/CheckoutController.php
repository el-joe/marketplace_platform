<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CheckoutOrderResource;
use App\Http\Resources\Api\Customer\CouponValidationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\SubOrder;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WarehouseInventory;
use App\Models\WarrantyPurchase;
use App\Services\CheckoutCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutCalculationService $calculationService,
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $this->validateCheckoutInput($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        /** @var Customer $customer */
        $customer = auth('customer')->user();

        try {
            $preview = $this->calculationService->calculate($customer, $validated['cart_items'], $validated);
        } catch (ValidationException $e) {
            return ApiResponse::error(__('customer_api.checkout.calculation_failed'), $this->flattenErrors($e), 422);
        }

        return ApiResponse::success($preview, __('customer_api.checkout.preview_calculated'));
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(__('customer_api.validation_failed'), $validator->errors()->toArray(), 422);
        }

        /** @var Customer $customer */
        $customer = auth('customer')->user();
        $country = \App\Models\Country::findOrFail($customer->country_id);

        $coupon = Coupon::where('code', $request->input('coupon_code'))->first();
        if (! $coupon) {
            return ApiResponse::error(__('customer_api.checkout.invalid_coupon_code'), [], 422);
        }

        $now = now();
        if (! $coupon->is_active) {
            return ApiResponse::error(__('customer_api.checkout.coupon_not_active'), [], 422);
        }
        if (($coupon->valid_from && $now->lt($coupon->valid_from)) || ($coupon->valid_until && $now->gt($coupon->valid_until))) {
            return ApiResponse::error(__('customer_api.checkout.coupon_not_valid_now'), [], 422);
        }
        if ($coupon->usage_limit_total !== null && $coupon->times_used >= $coupon->usage_limit_total) {
            return ApiResponse::error(__('customer_api.checkout.coupon_usage_limit_reached'), [], 422);
        }
        if ($coupon->usage_limit_per_customer !== null) {
            $used = CouponUsage::where('coupon_id', $coupon->id)->where('customer_id', $customer->id)->count();
            if ($used >= $coupon->usage_limit_per_customer) {
                return ApiResponse::error(__('customer_api.checkout.coupon_max_uses_reached'), [], 422);
            }
        }
        if ($coupon->currency !== null && $coupon->currency !== $country->currency_code) {
            return ApiResponse::error(__('customer_api.checkout.coupon_currency_mismatch'), [], 422);
        }

        return ApiResponse::success(
            (new CouponValidationResource($coupon))->toArray($request),
            __('customer_api.checkout.coupon_valid'),
        );
    }

    public function place(Request $request): JsonResponse
    {
        $validated = $this->validateCheckoutInput($request, forPlace: true);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        /** @var Customer $customer */
        $customer = auth('customer')->user();
        $country = \App\Models\Country::findOrFail($customer->country_id);

        $existing = \App\Models\PaymentTransaction::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existing) {
            $order = Order::find($existing->order_id);

            return ApiResponse::error(__('customer_api.checkout.order_already_placed'), ['order_number' => $order?->order_number], 409);
        }

        try {
            $preview = $this->calculationService->calculate($customer, $validated['cart_items'], $validated);
        } catch (ValidationException $e) {
            return ApiResponse::error(__('customer_api.checkout.calculation_failed'), $this->flattenErrors($e), 422);
        }

        $address = Address::find($validated['address_id']);
        $shippingMethod = ShippingMethod::find($validated['shipping_method_id']);
        $items = $this->calculationService->resolveCartItems($validated['cart_items']);
        $warrantySelections = $this->calculationService->resolveWarrantySelections(
            $items,
            $validated['warranty_selections'] ?? [],
            $country,
            $preview['currency'],
        )['selections'];

        $coupon = ! empty($validated['coupon_code']) ? Coupon::where('code', $validated['coupon_code'])->first() : null;
        $giftCard = ! empty($validated['gift_card_code']) ? GiftCard::where('code', $validated['gift_card_code'])->first() : null;

        try {
            $result = DB::transaction(function () use (
                $customer, $country, $address, $shippingMethod, $validated,
                $preview, $items, $warrantySelections, $coupon, $giftCard,
            ) {
                $order = Order::create([
                    'order_number' => $this->generateOrderNumber(),
                    'customer_id' => $customer->id,
                    'country_id' => $country->id,
                    'status' => 'placed',
                    'currency' => $preview['currency'],
                    'subtotal' => $preview['subtotal'],
                    'discount' => $preview['discount'],
                    'shipping' => $preview['shipping'],
                    'tax' => $preview['tax'],
                    'cod_fee' => $preview['cod_fee'],
                    'warranty_total' => $preview['warranty_total'],
                    'total' => $preview['total'],
                    'coupon_id' => $coupon?->id,
                    'coupon_code_used' => $coupon?->code,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'shipping_address_snapshot' => $this->buildAddressSnapshot($address),
                    'placed_at' => now(),
                ]);

                $grouped = collect($items)->groupBy(fn ($i) => $i['is_admin'] ? 'admin' : $i['vendor_id']);
                $subOrders = [];
                $idx = 0;

                foreach ($grouped as $groupKey => $groupItems) {
                    $idx++;
                    $groupShipping = $preview['shipping_breakdown'][$groupKey] ?? [
                        'customer_pays' => 0, 'admin_subsidy' => 0, 'vendor_contribution' => 0, 'billable_weight_grams' => 0,
                    ];

                    $vendorSubtotal = (int) $groupItems->sum(fn ($i) => $i['unit_price'] * $i['quantity']);
                    $vendorTax = (int) round($vendorSubtotal * ((float) $country->vat_rate / 100));

                    $subOrder = SubOrder::create([
                        'order_id' => $order->id,
                        'sub_order_number' => $order->order_number.'-'.str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                        'vendor_id' => $groupItems->first()['vendor_id'],
                        'warehouse_id' => $this->resolveWarehouseId($groupItems->first()),
                        'status' => 'placed',
                        'fulfillment_model' => $groupItems->first()['is_admin'] ? 'admin' : $groupItems->first()['listing']->fulfillment_model,
                        'subtotal' => $vendorSubtotal,
                        'shipping' => $groupShipping['customer_pays'],
                        'carrier_shipping_cost' => $groupShipping['carrier_cost'] ?? 0,
                        'shipping_gap' => $groupShipping['gap'] ?? 0,
                        'admin_subsidy_amount' => $groupShipping['admin_subsidy'],
                        'vendor_contribution_amount' => $groupShipping['vendor_contribution'],
                        'billable_weight_grams' => $groupShipping['billable_weight_grams'],
                        'tax' => $vendorTax,
                        'platform_commission' => 0,
                        'gateway_fee' => 0,
                        'gateway_fee_rate' => 0,
                        'vendor_payout' => $vendorSubtotal - $groupShipping['vendor_contribution'],
                        'shipping_method_id' => $shippingMethod->id,
                        'sla_ship_deadline' => now()->addHours(24),
                    ]);

                    foreach ($groupItems as $cartIndex => $item) {
                        $listing = $item['listing'];
                        $lineSubtotal = $item['unit_price'] * $item['quantity'];
                        $lineTax = (int) round($lineSubtotal * ((float) $country->vat_rate / 100));

                        if (! $item['is_admin']) {
                            $inventory = WarehouseInventory::where('vendor_listing_id', $item['vendor_listing_id'])
                                ->lockForUpdate()
                                ->orderBy('id')
                                ->first();

                            if (! $inventory || $inventory->quantity_available < $item['quantity']) {
                                throw new \DomainException(__('customer_api.checkout.insufficient_stock'));
                            }

                            $inventory->increment('quantity_reserved', $item['quantity']);
                        }

                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'sub_order_id' => $subOrder->id,
                            'product_variant_id' => $item['product_variant_id'],
                            'vendor_listing_id' => $item['vendor_listing_id'],
                            'admin_product_listing_id' => $item['admin_product_listing_id'],
                            'product_snapshot' => $this->buildProductSnapshot($item),
                            'vendor_id' => $item['vendor_id'],
                            'sku' => $listing->productVariant?->sku,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'line_subtotal' => $lineSubtotal,
                            'line_discount' => 0,
                            'line_tax' => $lineTax,
                            'line_total' => $lineSubtotal + $lineTax,
                            'commission_rate_pct' => 0,
                            'commission_fixed' => 0,
                            'commission_amount' => 0,
                            'fulfillment_status' => 'pending',
                            'return_eligible_until' => null,
                        ]);

                        if (isset($warrantySelections[$cartIndex])) {
                            $plan = $warrantySelections[$cartIndex]['plan'];

                            $warrantyPurchase = WarrantyPurchase::create([
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
                                'price_paid' => $warrantySelections[$cartIndex]['price'],
                                'currency' => $order->currency,
                                'status' => 'pending',
                                'coverage_starts_at' => null,
                                'coverage_ends_at' => null,
                            ]);

                            $orderItem->update(['warranty_purchase_id' => $warrantyPurchase->id]);
                        }
                    }

                    $subOrders[] = $subOrder;
                }

                if ($giftCard && $preview['gift_card_deduction'] > 0) {
                    $giftCard = GiftCard::where('id', $giftCard->id)->lockForUpdate()->first();

                    if ($giftCard->getRawOriginal('balance') < $preview['gift_card_deduction']) {
                        throw new \DomainException(__('customer_api.checkout.gift_card_balance_changed'));
                    }

                    $giftCard->decrement('balance', $preview['gift_card_deduction']);
                    $giftCard->refresh();

                    GiftCardTransaction::create([
                        'gift_card_id' => $giftCard->id,
                        'order_id' => $order->id,
                        'amount' => $preview['gift_card_deduction'],
                        'balance_after' => $giftCard->getRawOriginal('balance'),
                        'type' => 'redemption',
                        'performed_by_customer_id' => $customer->id,
                    ]);
                }

                if ($preview['wallet_deduction'] > 0) {
                    $wallet = Wallet::where('owner_type', 'customer')
                        ->where('owner_id', $customer->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $wallet || $wallet->is_frozen || $wallet->getRawOriginal('balance') < $preview['wallet_deduction']) {
                        throw new \DomainException(__('customer_api.checkout.wallet_balance_changed'));
                    }

                    $wallet->decrement('balance', $preview['wallet_deduction']);
                    $wallet->refresh();

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'debit',
                        'amount' => $preview['wallet_deduction'],
                        'balance_after' => $wallet->getRawOriginal('balance'),
                        'source_type' => 'order',
                        'source_id' => $order->id,
                        'description' => 'Wallet payment for order '.$order->order_number,
                        'created_at' => now(),
                    ]);
                }

                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'customer_id' => $customer->id,
                        'order_id' => $order->id,
                        'discount_amount' => $preview['discount'],
                        'used_at' => now(),
                    ]);
                    $coupon->increment('times_used');
                }

                \App\Models\PaymentTransaction::create([
                    'id' => (string) Str::uuid(),
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'type' => 'sale',
                    'gateway' => $validated['payment_method'] === 'cod' ? 'cod' : $validated['payment_method'],
                    'gateway_transaction_id' => $validated['payment_method'] === 'cod' ? 'COD-'.$order->order_number : null,
                    'idempotency_key' => $validated['idempotency_key'],
                    'amount' => $order->total,
                    'currency' => $order->currency,
                    'gateway_fee' => 0,
                    'status' => 'pending',
                    'payment_method_id' => null,
                ]);

                return ['order' => $order, 'sub_orders' => $subOrders];
            });
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $order = $result['order']->fresh();
        $order->load('subOrders.items');

        $sessionId = $request->header('X-Session-Id')
            ?? $request->cookie('session_id')
            ?? session()->getId();
        app(\App\Services\LastClickAttributionService::class)
            ->resolveAndRecordConversion($order, $sessionId);

        $this->clearCustomerCart($customer);

        return ApiResponse::success(
            (new CheckoutOrderResource($order))->toArray($request),
            __('customer_api.checkout.order_placed_successfully'),
            201,
        );
    }

    /**
     * @return array|JsonResponse
     */
    private function validateCheckoutInput(Request $request, bool $forPlace = false)
    {
        $rules = [
            'cart_items' => ['required', 'array', 'min:1'],
            'cart_items.*.listing_id' => ['required', 'uuid'],
            'cart_items.*.listing_type' => ['required', 'in:vendor,admin'],
            'cart_items.*.quantity' => ['required', 'integer', 'min:1'],
            'address_id' => ['required', 'uuid'],
            'shipping_method_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string'],
            'coupon_code' => ['nullable', 'string'],
            'wallet_use' => ['nullable', 'boolean'],
            'gift_card_code' => ['nullable', 'string'],
            'warranty_selections' => ['nullable', 'array'],
        ];

        if ($forPlace) {
            $rules['idempotency_key'] = ['required', 'string'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ApiResponse::error(__('customer_api.validation_failed'), $validator->errors()->toArray(), 422);
        }

        return $validator->validated();
    }

    private function flattenErrors(ValidationException $e): array
    {
        return $e->errors();
    }

    private function resolveWarehouseId(array $item): ?string
    {
        if ($item['is_admin']) {
            return null;
        }

        return WarehouseInventory::where('vendor_listing_id', $item['vendor_listing_id'])
            ->orderBy('id')
            ->value('warehouse_id');
    }

    private function buildAddressSnapshot(Address $address): array
    {
        $address->loadMissing('city');

        return [
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'country_id' => $address->country_id,
            'city_id' => $address->city_id,
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

    private function buildProductSnapshot(array $item): array
    {
        $listing = $item['listing'];
        $variant = $listing->productVariant;
        $product = $variant?->product;

        return [
            'name_en' => $product?->name_en,
            'name_ar' => $product?->name_ar,
            'sku' => $variant?->sku,
            'images' => $product?->images?->pluck('url')->values()->all() ?? [],
        ];
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }

    private function clearCustomerCart(Customer $customer): void
    {
        \App\Models\Cart::where('user_id', $customer->id)->get()->each(function ($cart) {
            $cart->items()->delete();
        });
    }
}
