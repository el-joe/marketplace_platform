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
use App\Models\Coupon;
use App\Services\BannerService;
use App\Services\CartItemEnrichmentService;
use App\Services\Customer\CartService;
use App\Services\Customer\ListingIdentifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ListingIdentifierService $listingIdentifierService,
        private readonly BannerService $bannerService,
        private readonly CartItemEnrichmentService $cartItemEnrichmentService,
    ) {}

    private function resolveCart(Request $request): Cart
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');

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

        $customer = auth('customer')->user();
        $country = $request->attributes->get('country');

        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        $shippingGroups = $cartItems->isEmpty()
            ? []
            : $this->cartItemEnrichmentService->groupByShippingMethod(
                $this->cartItemEnrichmentService->enrich($cartItems, $customer, $country)
            );

        return $this->cartResponse($cart, [
            'shipping_groups' => $shippingGroups,
            'cart_banner' => $this->resolveCartBanner($request),
        ]);
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

        try {
            $item = $this->cartService->addItem($cart, $request->vendor_listing_id, $request->quantity);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $item->load([
            'vendorListing.vendor',
            'vendorListing.productVariant.product.images',
            'vendorListing.primaryShippingMethod',
            'vendorListing.warehouseInventories',
        ]);

        return $this->cartResponse($cart, [
            'item'        => new CartItemResource($item),
            'listing_ref' => $this->listingIdentifierService->buildListingRef($item->vendorListing),
        ], 'Item added to cart', 201);
    }

    public function addItems(AddCartItemsRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        try {
            $this->cartService->addItems($cart, $request->items);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return $this->cartResponse($cart, [], 'Items added to cart', 201);
    }

    public function updateItem(UpdateCartItemRequest $request, string $id): JsonResponse
    {
        $cart = $this->resolveCart($request);

        try {
            $item = $this->cartService->updateItem($cart, $id, $request->quantity);
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
        ]);

        return ApiResponse::success([
            'cart'        => new CartResource($cart),
            'item'        => new CartItemResource($item),
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
        $country  = $request->attributes->get('country');
        $token    = $request->input('guest_cart_token');

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
