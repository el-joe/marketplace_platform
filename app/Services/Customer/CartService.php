<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\VendorListing;
use Illuminate\Support\Facades\DB;

class CartService
{
    private const MAX_ITEMS = 50;

    public function getOrCreateCart(Customer $customer, string $countryId, string $currency): Cart
    {
        return Cart::firstOrCreate(
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

        $this->recalcTotals($cart);

        return $item->fresh();
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
        $this->recalcTotals($cart);

        return $item->fresh();
    }

    public function removeItem(Cart $cart, string $itemId): void
    {
        $cart->items()->findOrFail($itemId)->delete();
        $this->recalcTotals($cart);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'coupon_id'          => null,
            'subtotal'           => 0,
            'discount'           => 0,
            'estimated_shipping' => 0,
            'estimated_tax'      => 0,
            'estimated_total'    => 0,
        ]);
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

        $subtotal = $this->computeSubtotal($cart);

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            $minFormatted = number_format($coupon->min_order_amount / 100, 2);
            throw new \DomainException("A minimum order of {$minFormatted} {$cart->currency} is required for this coupon.");
        }

        $cart->update(['coupon_id' => $coupon->id]);
        $this->recalcTotals($cart);

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null, 'discount' => 0]);
        $this->recalcTotals($cart);
    }

    public function recalcTotals(Cart $cart): void
    {
        $cart->load('items', 'coupon');

        $subtotal = $this->computeSubtotal($cart);
        $discount = 0;

        if ($cart->coupon) {
            $discount = $this->computeDiscount($cart->coupon, $subtotal);
        }

        $estimatedTotal = max(0, $subtotal - $discount + $cart->estimated_shipping + $cart->estimated_tax);

        $cart->update([
            'subtotal'        => $subtotal,
            'discount'        => $discount,
            'estimated_total' => $estimatedTotal,
        ]);
    }

    public function computeDiscount(Coupon $coupon, int $subtotal): int
    {
        $discount = match ($coupon->type) {
            'percentage'   => (int) round($subtotal * ($coupon->value / 100)),
            'fixed_amount' => (int) round($coupon->value * 100),
            'free_shipping' => 0,
            default        => 0,
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (int) $coupon->max_discount);
        }

        return min($discount, $subtotal);
    }

    private function computeSubtotal(Cart $cart): int
    {
        return (int) $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);
    }
}
