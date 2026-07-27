<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddCartItemRequest;
use App\Http\Requests\Customer\AddCartItemsRequest;
use App\Http\Requests\Customer\ApplyCouponRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Http\Resources\Customer\BannerResource;
use App\Http\Resources\Customer\CartItemResource;
use App\Http\Resources\Customer\CartResource;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CountryShippingSetting;
use App\Models\Coupon;
use App\Models\CustomerWallet;
use App\Services\BannerService;
use App\Services\Customer\CartService;
use App\Services\Customer\ListingIdentifierService;
use App\Services\SavingsBenefitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ListingIdentifierService $listingIdentifierService,
        private readonly BannerService $bannerService,
        private readonly SavingsBenefitsService $savingsBenefitsService,
    ) {
    }

    private function resolveCart(Request $request): Cart
    {
        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');

        if ($customer) {
            return $this->cartService->getOrCreateCart(
                $customer,
                $country->id,
                $country->currency_code
            );
        }

        $token = $request->attributes->get('guest_cart_token');
        if (!$token) {
            $token = (string) Str::uuid();
            $request->attributes->set('guest_cart_token', $token);
        }

        return $this->cartService->getOrCreateGuestCart(
            $token,
            $country->id,
            $country->currency_code
        );
    }

    private function cartResponse(Cart $cart, array $extra = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        $data = array_merge(['cart' => new CartResource($cart)], $extra);

        if ($cart->session_token) {
            $data['guest_cart_token'] = $cart->session_token;
        }

        return ApiResponse::success($data, $message, $code);
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        $country = $request->attributes->get('country');

        return $this->cartResponse($cart, [
            'shipping_groups' => $this->buildShippingGroups($cart, $country?->id),
            'cart_banner' => $this->resolveCartBanner($request),
            'savings_and_benefits' => $this->savingsBenefitsService->get(
                (int) $cart->estimated_total,
                $cart->country_id,
                $cart->currency,
            ),
            'wallet' => $this->resolveWalletInfo($cart),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveWalletInfo(Cart $cart): array
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return [
                'balance' => 0,
                'currency_code' => $cart->currency,
                'applicable' => false,
                'max_usable' => 0,
                'remaining_after_wallet' => (int) $cart->estimated_total,
            ];
        }

        $customerCurrency = $customer->country?->currency_code;

        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();
        $walletBalance = $wallet?->balance ?? 0;
        $walletCurrency = $wallet?->currency_code ?? $customerCurrency;

        $walletApplicable = $walletBalance > 0 && $walletCurrency === $cart->currency;

        $maxUsable = $walletApplicable ? min($walletBalance, (int) $cart->estimated_total) : 0;

        return [
            'balance' => $walletBalance,
            'currency_code' => $walletCurrency,
            'applicable' => $walletApplicable,
            'max_usable' => $maxUsable,
            'remaining_after_wallet' => $walletApplicable
                ? ((int) $cart->estimated_total - $maxUsable)
                : (int) $cart->estimated_total,
        ];
    }

    /**
     * Groups cart items by their selected_shipping_method_id so the app can
     * render a Noon-style cart with items grouped under their delivery
     * method. Items with no selection yet fall into a shared "unassigned"
     * group (null method).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildShippingGroups(Cart $cart, ?string $countryId): array
    {
        $items = CartItem::where('cart_id', $cart->id)
            ->with([
                'vendorListing.vendor',
                'adminProductListing',
                'selectedShippingMethod',
                'vendorListing.productVariant.product',
                'vendorListing.productVariant.images',
                'adminProductListing.productVariant.product',
                'adminProductListing.productVariant.images',
            ])
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $groups = $items->groupBy('selected_shipping_method_id');

        return $groups->map(function ($groupItems) use ($countryId) {
            $method = $groupItems->first()->selectedShippingMethod;

            $groupSubtotal = $groupItems->sum(fn ($item) => $item->unit_price * $item->quantity);

            $threshold = ($method && $countryId)
                ? CountryShippingSetting::where('country_id', $countryId)
                    ->where('shipping_method_id', $method->id)
                    ->value('free_shipping_threshold')
                : null;

            $isFreeShipping = $threshold !== null && $groupSubtotal >= $threshold;

            return [
                'shipping_method' => $method ? [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'badge_label_en' => $method->badge_label_en,
                    'badge_label_ar' => $method->badge_label_ar,
                    'badge_color_hex' => $method->badge_color_hex,
                    'delivery_label_en' => $method->delivery_label_en,
                    'delivery_label_ar' => $method->delivery_label_ar,
                    'is_express_type' => (bool) $method->is_express_type,
                ] : null,
                'is_free_shipping' => $isFreeShipping,
                'group_subtotal' => $groupSubtotal,
                'items_count' => $groupItems->count(),
                'items' => $groupItems->map(function ($item) use ($method) {
                    $isVendor = (bool) $item->vendor_listing_id;
                    $listing = $isVendor ? $item->vendorListing : $item->adminProductListing;
                    $variant = $listing?->productVariant;
                    $product = $variant?->product;
                    $primaryImage = $variant?->images?->firstWhere('is_primary', true) ?? $variant?->images?->first();

                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->unit_price * $item->quantity,
                        'product_url' => "/products/{$variant?->id}/" . ($isVendor ? $item->vendor_listing_id : $item->admin_product_listing_id),
                        'product_name_en' => $product?->name_en,
                        'product_name_ar' => $product?->name_ar,
                        'variant_name' => $variant?->variant_name,
                        'primary_image' => $primaryImage?->path,
                        'listing_id' => $isVendor ? $item->vendor_listing_id : $item->admin_product_listing_id,
                        'listing_type' => $isVendor ? 'vendor' : 'admin',
                        'vendor' => $isVendor ? [
                            'id' => $item->vendorListing->vendor->id,
                            'store_name' => $item->vendorListing->vendor->store_name,
                        ] : null,
                        'selected_shipping_method' => [
                            'id' => $method?->id,
                            'name' => $method?->name,
                            'code' => $method?->code,
                        ],
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    private function resolveCartBanner(Request $request): ?BannerResource
    {
        $country = $request->attributes->get('country');
        $audience = auth('customer')->check() ? 'logged_in' : 'guest';

        $banner = $this->bannerService->getActivePlacement('cart_banner', $country?->id, $audience);

        return $banner ? new BannerResource($banner) : null;
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $countryId = $request->attributes->get('country')->id;

        $isAdmin = $request->input('listing_type') === 'admin';

        try {
            if ($isAdmin) {
                $item = $this->cartService->addAdminItem($cart, $request->admin_product_listing_id, $request->quantity, $request->shipping_method_id, $countryId);
            } else {
                $item = $this->cartService->addItem($cart, $request->vendor_listing_id, $request->quantity, $request->shipping_method_id, $countryId);
            }
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $item->load([
            'vendorListing.vendor',
            'vendorListing.productVariant.product.images',
            'vendorListing.primaryShippingMethod',
            'vendorListing.warehouseInventories',
            'adminProductListing.productVariant.product.images',
            'selectedShippingMethod',
        ]);

        return $this->cartResponse($cart, [
            'item' => new CartItemResource($item),
            'listing_ref' => $isAdmin ? null : $this->listingIdentifierService->buildListingRef($item->vendorListing),
        ], 'Item added to cart', 201);
    }

    public function addItems(AddCartItemsRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $countryId = $request->attributes->get('country')->id;

        try {
            $this->cartService->addItems($cart, $request->items, $countryId);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return $this->cartResponse($cart, [], 'Items added to cart', 201);
    }

    public function updateItem(UpdateCartItemRequest $request, string $id): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $countryId = $request->attributes->get('country')->id;

        try {
            $item = $this->cartService->updateItem($cart, $id, $request->quantity, $request->shipping_method_id, $request->has('shipping_method_id'), $countryId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Cart item not found.', [], 404);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $item->load([
            'vendorListing.vendor',
            'vendorListing.productVariant.product.images',
            'vendorListing.primaryShippingMethod',
            'vendorListing.warehouseInventories',
            'selectedShippingMethod',
        ]);

        return ApiResponse::success([
            'cart' => new CartResource($cart),
            'item' => new CartItemResource($item),
            'listing_ref' => $this->listingIdentifierService->buildListingRef($item->vendorListing),
        ], 'Cart item updated');
    }

    public function removeItem(Request $request, string $id): JsonResponse
    {
        $cart = $this->resolveCart($request);

        try {
            $this->cartService->removeItem($cart, $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Cart item not found.', [], 404);
        }

        return ApiResponse::success(new CartResource($cart), 'Item removed from cart');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        $this->cartService->clearCart($cart);

        return ApiResponse::success(null, 'Cart cleared');
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $cart = $this->resolveCart($request);

        try {
            $coupon = $this->cartService->applyCoupon($cart, $customer, $request->code);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Coupon not found or is invalid.', [], 404);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(new CartResource($cart), "Coupon \"{$coupon->code}\" applied");
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        $this->cartService->removeCoupon($cart);

        return ApiResponse::success(new CartResource($cart), 'Coupon removed');
    }

    /**
     * Applies a discount code to the cart, trying the coupons table first
     * and falling back to affiliate promo codes.
     */
    public function applyPromoCode(ApplyCouponRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $cart = $this->resolveCart($request);
        $code = $request->code;

        $coupon = Coupon::where('code', $code)->first();
        if ($coupon) {
            try {
                $this->cartService->applyCoupon($cart, $customer, $code);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ApiResponse::error('Coupon not found or is invalid.', [], 404);
            } catch (\DomainException $e) {
                return ApiResponse::error($e->getMessage(), [], 422);
            }

            return ApiResponse::success([
                'success' => true,
                'message' => "Coupon \"{$code}\" applied",
                'data' => [
                    'discount_amount' => $cart->discount,
                    'type' => 'coupon',
                ],
                'cart' => new CartResource($cart),
            ], "Coupon \"{$code}\" applied");
        }

        try {
            $this->cartService->applyAffiliatePromoCode($cart, $code);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success([
            'success' => true,
            'message' => "Promo code \"{$code}\" applied",
            'data' => [
                'discount_amount' => $cart->discount,
                'type' => 'affiliate_promo',
            ],
            'cart' => new CartResource($cart),
        ], "Promo code \"{$code}\" applied");
    }

    public function removePromoCode(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        $this->cartService->removeAffiliatePromoCode($cart);

        return ApiResponse::success(new CartResource($cart), 'Promo code removed');
    }

    public function mergeCart(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');
        $token = $request->input('guest_cart_token');

        if (!$token) {
            return ApiResponse::error('guest_cart_token is required.', [], 422);
        }

        $cart = $this->cartService->mergeGuestCart(
            $token,
            $customer,
            $country->id,
            $country->currency_code
        );

        return ApiResponse::success(new CartResource($cart), 'Cart merged successfully');
    }
}
