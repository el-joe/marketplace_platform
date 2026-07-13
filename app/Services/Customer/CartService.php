<?php

namespace App\Services\Customer;

use App\Enums\VendorListingStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Country;
use App\Models\Customer;
use App\Models\VendorListing;

class CartService
{
    private const MAX_ITEMS = 50;

    private const ITEM_EAGER_LOADS = [
        'items.vendorListing.vendor',
        'items.vendorListing.productVariant.product.images',
        'items.vendorListing.primaryShippingMethod',
        'items.vendorListing.warehouseInventories',
    ];

    public function __construct(
        private readonly CheckoutCalculationService $calculationService,
    ) {}

    public function getOrCreateCart(Customer $customer, string $countryId, string $currency): Cart
    {
        $cart = Cart::firstOrCreate(
            ['user_id' => $customer->id, 'country_id' => $countryId],
            [
                'currency'           => $currency,
                'subtotal'           => 0,
                'discount'           => 0,
                'estimated_shipping' => 0,
                'estimated_tax'      => 0,
                'estimated_total'    => 0,
            ]
        );

        $this->recalculateCart($cart);

        return $cart;
    }

    public function addItem(Cart $cart, string $vendorListingId, int $quantity): CartItem
    {
        $listing = VendorListing::with('warehouseInventories')->findOrFail($vendorListingId);

        $available = $listing->warehouseInventories->sum('quantity_available');
        if ($available < $quantity) {
            throw new \DomainException("Insufficient stock. Only {$available} unit(s) available.");
        }

        $currentCount = $cart->items()->count();

        $existingItem = $cart->items()->where('vendor_listing_id', $vendorListingId)->first();

        if ($existingItem) {
            $newQty = $existingItem->quantity + $quantity;
            if ($newQty > ($listing->max_order_quantity ?? PHP_INT_MAX)) {
                throw new \DomainException('Exceeds maximum order quantity for this listing.');
            }
            if ($available < $newQty) {
                throw new \DomainException("Insufficient stock. Only {$available} unit(s) available.");
            }
            $existingItem->update(['quantity' => $newQty]);
            $item = $existingItem;
        } else {
            if ($currentCount >= self::MAX_ITEMS) {
                throw new \DomainException('Cart cannot exceed ' . self::MAX_ITEMS . ' items.');
            }
            $item = $cart->items()->create([
                'vendor_listing_id' => $vendorListingId,
                'quantity'          => $quantity,
                'unit_price'        => $listing->price,
                'added_at'          => now(),
            ]);
        }

        $this->recalculateCart($cart);

        return $item->fresh();
    }

    /**
     * @param array<int, array{vendor_listing_id: string, quantity: int}> $items
     * @return array<int, CartItem>
     */
    public function addItems(Cart $cart, array $items): array
    {
        $added = [];

        foreach ($items as $item) {
            $added[] = $this->addItem($cart, $item['vendor_listing_id'], $item['quantity']);
        }

        return $added;
    }

    public function updateItem(Cart $cart, string $itemId, int $quantity): CartItem
    {
        $item = $cart->items()->findOrFail($itemId);
        $listing = VendorListing::with('warehouseInventories')->findOrFail($item->vendor_listing_id);

        $available = $listing->warehouseInventories->sum('quantity_available');
        if ($available < $quantity) {
            throw new \DomainException("Insufficient stock. Only {$available} unit(s) available.");
        }
        if ($quantity > ($listing->max_order_quantity ?? PHP_INT_MAX)) {
            throw new \DomainException('Exceeds maximum order quantity for this listing.');
        }

        $item->update(['quantity' => $quantity]);
        $this->recalculateCart($cart);

        return $item->fresh();
    }

    public function removeItem(Cart $cart, string $itemId): void
    {
        $cart->items()->findOrFail($itemId)->delete();
        $this->recalculateCart($cart);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
        $this->recalculateCart($cart);
    }

    public function applyCoupon(Cart $cart, Customer $customer, string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->firstOrFail();

        if ($coupon->usage_limit_total !== null && $coupon->times_used >= $coupon->usage_limit_total) {
            throw new \DomainException('This coupon has reached its total usage limit.');
        }

        $customerUsageCount = CouponUsage::where('coupon_id', $coupon->id)
            ->where('customer_id', $customer->id)
            ->count();

        if ($customerUsageCount >= $coupon->usage_limit_per_customer) {
            throw new \DomainException('You have already used this coupon the maximum number of times.');
        }

        $subtotal = (int) $cart->items()->get()->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            $minFormatted = number_format($coupon->min_order_amount / 100, 2);
            throw new \DomainException("A minimum order of {$minFormatted} {$cart->currency} is required for this coupon.");
        }

        $cart->update(['coupon_id' => $coupon->id]);
        $this->recalculateCart($cart);

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
        $this->recalculateCart($cart);
    }

    /**
     * Recalculates cart totals from scratch using live vendor_listing prices.
     * Called after every cart mutation so totals can never go stale.
     *
     * Syncs unit_price to the listing's current price, drops items whose
     * listing is no longer active, recomputes discount via
     * CheckoutCalculationService (coupon scope/eligibility can change between
     * requests), and recomputes tax from the cart's country VAT rate.
     *
     * Annotates each surviving CartItem with a transient (non-persisted)
     * `price_changed` attribute so the API response can flag "price updated"
     * items to the client.
     */
    private function recalculateCart(Cart $cart): void
    {
        $cart->load(array_merge(self::ITEM_EAGER_LOADS, ['coupon', 'customer']));

        $priceChanges = [];

        foreach ($cart->items as $item) {
            $listing = $item->vendorListing;

            if (! $listing || $listing->status !== VendorListingStatus::Active) {
                $item->delete();
                continue;
            }

            $livePrice = (int) $listing->price;
            if ((int) $item->unit_price !== $livePrice) {
                $priceChanges[$item->id] = true;
                $item->update(['unit_price' => $livePrice]);
            }
        }

        $cart->unsetRelation('items');
        $cart->load(self::ITEM_EAGER_LOADS);

        $subtotal = (int) $cart->items->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);

        $discount = 0;
        if ($cart->coupon && $cart->customer) {
            $result = $this->calculationService->applyCoupon(
                $cart->coupon,
                $cart->customer,
                $subtotal,
                $cart->currency,
                $cart->items->all(),
            );
            $discount = $result['error'] ? 0 : $result['discount'];
        }

        $country = Country::find($cart->country_id);
        $taxable = max(0, $subtotal - $discount);
        $estimatedTax = $country ? $this->calculationService->calculateTax($taxable, $country) : 0;

        $cart->update([
            'subtotal'           => $subtotal,
            'discount'           => $discount,
            'estimated_shipping' => 0,
            'estimated_tax'      => $estimatedTax,
            'estimated_total'    => max(0, $subtotal - $discount + $estimatedTax),
            'expires_at'         => now()->addDays(30),
        ]);

        foreach ($cart->items as $item) {
            $item->setAttribute('price_changed', $priceChanges[$item->id] ?? false);
        }
    }
}
