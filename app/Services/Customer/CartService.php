<?php

namespace App\Services\Customer;

use App\Enums\AdminProductListingStatus;
use App\Enums\GlobalSystemType;
use App\Enums\VendorListingStatus;
use App\Models\AdminProductListing;
use App\Models\AffiliatePromoCode;
use App\Models\Cart;
use App\Models\CartInventoryLock;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Country;
use App\Models\Customer;
use App\Models\VendorListing;
use App\Services\AppContextService;
use Illuminate\Support\Facades\DB;

class CartService
{
    private const MAX_ITEMS = 50;

    private const ITEM_EAGER_LOADS = [
        'items.vendorListing.vendor',
        'items.vendorListing.productVariant.product.images',
        'items.vendorListing.primaryShippingMethod',
        'items.vendorListing.warehouseInventories',
        'items.adminProductListing.productVariant.product.images',
    ];

    public function __construct(
        private readonly CheckoutCalculationService $calculationService,
        private readonly AppContextService $appContextService,
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

    public function getOrCreateGuestCart(string $sessionToken, string $countryId, string $currency): Cart
    {
        $cart = Cart::where('session_token', $sessionToken)
            ->whereNull('user_id')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with(['items.vendorListing', 'coupon'])
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'session_token'      => $sessionToken,
                'user_id'            => null,
                'country_id'         => $countryId,
                'currency'           => $currency,
                'subtotal'           => 0,
                'discount'           => 0,
                'estimated_shipping' => 0,
                'estimated_tax'      => 0,
                'estimated_total'    => 0,
                'expires_at'         => now()->addDays(30),
            ]);
        }

        return $cart;
    }

    public function mergeGuestCart(string $sessionToken, Customer $customer, string $countryId, string $currency): Cart
    {
        $guestCart = Cart::where('session_token', $sessionToken)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        $customerCart = $this->getOrCreateCart($customer, $countryId, $currency);

        if (!$guestCart || $guestCart->items->isEmpty()) {
            return $customerCart;
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $customerCart->items
                ->firstWhere('vendor_listing_id', $guestItem->vendor_listing_id);

            if ($existing) {
                $existing->update([
                    'quantity' => max($existing->quantity, $guestItem->quantity),
                ]);
            } else {
                $guestItem->update(['cart_id' => $customerCart->id]);
            }
        }

        CartInventoryLock::where('cart_id', $guestCart->id)->delete();
        $guestCart->items()->whereNot('cart_id', $customerCart->id)->delete();
        $guestCart->delete();

        $customerCart->load(['items.vendorListing', 'coupon']);
        $this->recalculateCart($customerCart);

        return $customerCart->fresh(['items.vendorListing.productVariant.product.images', 'coupon']);
    }

    public function addItem(Cart $cart, string $vendorListingId, int $quantity, ?string $shippingMethodId = null): CartItem
    {
        $listing = VendorListing::with(['warehouseInventories', 'productVariant.product'])->findOrFail($vendorListingId);

        if ($shippingMethodId) {
            $this->assertShippingMethodAvailable($listing, $shippingMethodId);
        }

        $available = $listing->warehouseInventories->sum('quantity_available');
        if ($available < $quantity) {
            throw new \DomainException(__('common.exceptions.cart.insufficient_stock', ['available' => $available]));
        }

        $currentCount = $cart->items()->count();

        $existingItem = $cart->items()->where('vendor_listing_id', $vendorListingId)->first();

        if ($existingItem) {
            $newQty = $existingItem->quantity + $quantity;
            if ($newQty > ($listing->max_order_quantity ?? PHP_INT_MAX)) {
                throw new \DomainException(__('common.exceptions.cart.exceeds_max_order_quantity'));
            }
            if ($available < $newQty) {
                throw new \DomainException(__('common.exceptions.cart.insufficient_stock', ['available' => $available]));
            }
            $updates = ['quantity' => $newQty];
            if ($shippingMethodId !== null) {
                $updates['selected_shipping_method_id'] = $shippingMethodId;
            }
            $existingItem->update($updates);
            $item = $existingItem;
        } else {
            if ($currentCount >= self::MAX_ITEMS) {
                throw new \DomainException(__('common.exceptions.cart.max_items', ['max' => self::MAX_ITEMS]));
            }
            $item = $cart->items()->create([
                'vendor_listing_id' => $vendorListingId,
                'quantity'          => $quantity,
                'unit_price'        => $listing->price,
                'added_at'          => now(),
                'selected_shipping_method_id' => $shippingMethodId,
            ]);
        }

        $this->recalculateCart($cart);

        return $item->fresh();
    }

    /**
     * @param array<int, array{vendor_listing_id?: string, admin_product_listing_id?: string, listing_type?: string, quantity: int, shipping_method_id?: ?string}> $items
     * @return array<int, CartItem>
     */
    public function addItems(Cart $cart, array $items): array
    {
        $added = [];

        foreach ($items as $item) {
            if (($item['listing_type'] ?? null) === 'admin') {
                $added[] = $this->addAdminItem($cart, $item['admin_product_listing_id'], $item['quantity'], $item['shipping_method_id'] ?? null);
            } else {
                $added[] = $this->addItem($cart, $item['vendor_listing_id'], $item['quantity'], $item['shipping_method_id'] ?? null);
            }
        }

        return $added;
    }

    /**
     * Adds a platform-owned admin listing to the cart. Only valid in the
     * nawy_now app context, and only for listings explicitly featured there.
     */
    public function addAdminItem(Cart $cart, string $adminProductListingId, int $quantity, ?string $shippingMethodId = null): CartItem
    {
        if (! $this->appContextService->isNawyNow()) {
            throw new \DomainException(__('common.exceptions.cart.admin_listing_not_allowed'));
        }

        $listing = AdminProductListing::with(['warehouseInventories', 'productVariant.product'])->findOrFail($adminProductListingId);

        if ($listing->status !== AdminProductListingStatus::Active || ! $listing->featured_in_nawy) {
            throw new \DomainException(__('common.exceptions.cart.admin_listing_not_allowed'));
        }

        $available = $listing->warehouseInventories->sum('quantity_available');
        if ($available < $quantity) {
            throw new \DomainException(__('common.exceptions.cart.insufficient_stock', ['available' => $available]));
        }

        $currentCount = $cart->items()->count();

        $existingItem = $cart->items()->where('admin_product_listing_id', $adminProductListingId)->first();

        if ($existingItem) {
            $newQty = $existingItem->quantity + $quantity;
            if ($newQty > ($listing->max_order_quantity ?? PHP_INT_MAX)) {
                throw new \DomainException(__('common.exceptions.cart.exceeds_max_order_quantity'));
            }
            if ($available < $newQty) {
                throw new \DomainException(__('common.exceptions.cart.insufficient_stock', ['available' => $available]));
            }
            $updates = ['quantity' => $newQty];
            if ($shippingMethodId !== null) {
                $updates['selected_shipping_method_id'] = $shippingMethodId;
            }
            $existingItem->update($updates);
            $item = $existingItem;
        } else {
            if ($currentCount >= self::MAX_ITEMS) {
                throw new \DomainException(__('common.exceptions.cart.max_items', ['max' => self::MAX_ITEMS]));
            }
            $item = $cart->items()->create([
                'vendor_listing_id'           => null,
                'admin_product_listing_id'    => $adminProductListingId,
                'quantity'                    => $quantity,
                'unit_price'                  => $listing->getRawOriginal('price'),
                'added_at'                    => now(),
                'selected_shipping_method_id' => $shippingMethodId,
            ]);
        }

        $this->recalculateCart($cart);

        return $item->fresh();
    }

    public function updateItem(Cart $cart, string $itemId, int $quantity, ?string $shippingMethodId = null): CartItem
    {
        $item = $cart->items()->findOrFail($itemId);
        $listing = VendorListing::with(['warehouseInventories', 'productVariant.product'])->findOrFail($item->vendor_listing_id);

        if ($shippingMethodId) {
            $this->assertShippingMethodAvailable($listing, $shippingMethodId);
        }

        $available = $listing->warehouseInventories->sum('quantity_available');
        if ($available < $quantity) {
            throw new \DomainException(__('common.exceptions.cart.insufficient_stock', ['available' => $available]));
        }
        if ($quantity > ($listing->max_order_quantity ?? PHP_INT_MAX)) {
            throw new \DomainException(__('common.exceptions.cart.exceeds_max_order_quantity'));
        }

        $updates = ['quantity' => $quantity];
        if ($shippingMethodId !== null) {
            $updates['selected_shipping_method_id'] = $shippingMethodId;
        }
        $item->update($updates);
        $this->recalculateCart($cart);

        return $item->fresh();
    }

    /**
     * Guards against a customer picking a shipping method that isn't
     * actually offered for this listing's category, or that's offered
     * only for the other global_system_type (express_fbn vs merchant_fbp).
     */
    private function assertShippingMethodAvailable(VendorListing $listing, string $shippingMethodId): void
    {
        $categoryId = $listing->productVariant?->product?->category_id;
        if (!$categoryId) {
            throw new \DomainException(__('common.exceptions.cart.invalid_shipping_method'));
        }

        $fbnField = $listing->global_system_type === GlobalSystemType::ExpressFbn
            ? 'is_available_for_express_fbn'
            : 'is_available_for_merchant_fbp';

        $isAvailable = DB::table('category_shipping_methods')
            ->where('category_id', $categoryId)
            ->where('shipping_method_id', $shippingMethodId)
            ->where($fbnField, true)
            ->exists();

        if (!$isAvailable) {
            throw new \DomainException(__('common.exceptions.cart.invalid_shipping_method'));
        }
    }

    public function removeItem(Cart $cart, string $itemId): void
    {
        $cart->items()->findOrFail($itemId)->delete();
        $this->recalculateCart($cart);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null, 'affiliate_promo_code_id' => null]);
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
            throw new \DomainException(__('common.exceptions.cart.coupon_usage_limit_reached'));
        }

        $customerUsageCount = CouponUsage::where('coupon_id', $coupon->id)
            ->where('customer_id', $customer->id)
            ->count();

        if ($customerUsageCount >= $coupon->usage_limit_per_customer) {
            throw new \DomainException(__('common.exceptions.cart.coupon_customer_limit_reached'));
        }

        $subtotal = (int) $cart->items()->get()->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            $minFormatted = number_format($coupon->min_order_amount / 100, 2);
            throw new \DomainException(__('common.exceptions.cart.coupon_min_order_required', ['amount' => $minFormatted, 'currency' => $cart->currency]));
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

    public function applyAffiliatePromoCode(Cart $cart, string $code): AffiliatePromoCode
    {
        $subtotal = (int) $cart->items()->get()->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);

        $cart->setAttribute('subtotal', $subtotal);
        $cart->setAttribute('estimated_shipping', 0);

        $result = $this->calculationService->applyAffiliatePromoCode($cart, $code, $cart->coupon);

        if (! $result['applied']) {
            throw new \DomainException($result['message']);
        }

        $cart->update(['affiliate_promo_code_id' => $result['promo_code_id']]);
        $this->recalculateCart($cart);

        return AffiliatePromoCode::findOrFail($result['promo_code_id']);
    }

    public function removeAffiliatePromoCode(Cart $cart): void
    {
        $cart->update(['affiliate_promo_code_id' => null]);
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
        $cart->load(array_merge(self::ITEM_EAGER_LOADS, ['coupon', 'affiliatePromoCode', 'customer']));

        $priceChanges = [];

        foreach ($cart->items as $item) {
            if ($item->admin_product_listing_id !== null) {
                $listing = $item->adminProductListing;

                if (! $listing || $listing->status !== AdminProductListingStatus::Active || ! $listing->featured_in_nawy) {
                    $item->delete();
                    continue;
                }

                $livePrice = (int) $listing->getRawOriginal('price');
            } else {
                $listing = $item->vendorListing;

                if (! $listing || $listing->status !== VendorListingStatus::Active) {
                    $item->delete();
                    continue;
                }

                $livePrice = (int) $listing->price;
            }

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

        if ($cart->affiliatePromoCode) {
            $cart->setAttribute('subtotal', $subtotal);
            $cart->setAttribute('estimated_shipping', 0);

            $promoResult = $this->calculationService->applyAffiliatePromoCode(
                $cart,
                $cart->affiliatePromoCode->code,
                $cart->coupon,
            );

            if ($promoResult['applied']) {
                $discount += $promoResult['discount_amount'];
            } else {
                // Promo code became invalid (expired, limit reached, no longer
                // stackable with the applied coupon) — drop it silently so
                // totals stay consistent without throwing mid-recalculation.
                $cart->affiliate_promo_code_id = null;
            }
        }

        $country = Country::find($cart->country_id);
        $taxable = max(0, $subtotal - $discount);
        $estimatedTax = $country ? $this->calculationService->calculateTax($taxable, $country) : 0;

        $cart->update([
            'subtotal'                 => $subtotal,
            'discount'                 => $discount,
            'estimated_shipping'       => 0,
            'estimated_tax'            => $estimatedTax,
            'estimated_total'          => max(0, $subtotal - $discount + $estimatedTax),
            'affiliate_promo_code_id'  => $cart->affiliate_promo_code_id,
            'expires_at'               => now()->addDays(30),
        ]);

        foreach ($cart->items as $item) {
            $item->setAttribute('price_changed', $priceChanges[$item->id] ?? false);
        }
    }
}
