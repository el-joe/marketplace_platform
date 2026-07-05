<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddCartItemRequest;
use App\Http\Requests\Customer\ApplyCouponRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Http\Resources\Customer\CartItemResource;
use App\Http\Resources\Customer\CartResource;
use App\Http\Responses\ApiResponse;
use App\Services\Customer\CartService;
use App\Services\Customer\ListingIdentifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ListingIdentifierService $listingIdentifierService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');

        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);
        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);

        return ApiResponse::success(new CartResource($cart));
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        try {
            $item = $this->cartService->addItem($cart, $request->vendor_listing_id, $request->quantity);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);
        $item->load(['vendorListing.vendor', 'vendorListing.productVariant.product.images']);

        return ApiResponse::success([
            'cart'        => new CartResource($cart),
            'item'        => new CartItemResource($item),
            'listing_ref' => $this->listingIdentifierService->buildListingRef($item->vendorListing),
        ], 'Item added to cart', 201);
    }

    public function updateItem(UpdateCartItemRequest $request,$country, string $id): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        // try {
            $item = $this->cartService->updateItem($cart, $id, $request->quantity);
        // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        //     return ApiResponse::error('Cart item not found.', [], 404);
        // } catch (\DomainException $e) {
        //     return ApiResponse::error($e->getMessage(), [], 422);
        // }

        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);
        $item->load(['vendorListing.vendor', 'vendorListing.productVariant.product.images']);

        return ApiResponse::success([
            'cart'        => new CartResource($cart),
            'item'        => new CartItemResource($item),
            'listing_ref' => $this->listingIdentifierService->buildListingRef($item->vendorListing),
        ], 'Cart item updated');
    }

    public function removeItem(Request $request,$country, string $id): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        try {
            $this->cartService->removeItem($cart, $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Cart item not found.', [], 404);
        }

        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);

        return ApiResponse::success(new CartResource($cart), 'Item removed from cart');
    }

    public function clear(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        $this->cartService->clearCart($cart);

        return ApiResponse::success(null, 'Cart cleared');
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);
        $cart->load('items');

        try {
            $coupon = $this->cartService->applyCoupon($cart, $customer, $request->code);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Coupon not found or is invalid.', [], 404);
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);

        return ApiResponse::success(new CartResource($cart), "Coupon \"{$coupon->code}\" applied");
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        $this->cartService->removeCoupon($cart);
        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product.images', 'coupon']);

        return ApiResponse::success(new CartResource($cart), 'Coupon removed');
    }
}
