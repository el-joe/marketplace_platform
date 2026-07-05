<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutPrepareRequest;
use App\Http\Requests\Customer\PlaceOrderRequest;
use App\Http\Requests\Customer\ShippingMethodsRequest;
use App\Http\Resources\Customer\CheckoutPreviewResource;
use App\Http\Resources\Customer\OrderResource;
use App\Http\Resources\Customer\ShippingMethodOptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\Customer\CartService;
use App\Services\Customer\CustomerCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CustomerCheckoutService $checkoutService,
    ) {}

    public function prepare(CheckoutPrepareRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        try {
            $preview = $this->checkoutService->prepare($cart, $customer, $request->validated());
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Address or shipping method not found.', [], 404);
        }

        return ApiResponse::success(new CheckoutPreviewResource($preview), 'Checkout preview ready');
    }

    public function shippingMethods(ShippingMethodsRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        try {
            $options = $this->checkoutService->availableShippingMethods(
                $cart,
                $customer,
                $request->validated('address_id')
            );
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Address not found.', [], 404);
        }

        return ApiResponse::success(ShippingMethodOptionResource::collection($options), 'Shipping methods retrieved');
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $country  = $request->attributes->get('country');
        $cart = $this->cartService->getOrCreateCart($customer, $country->id, $country->currency_code);

        try {
            $order = $this->checkoutService->placeOrder(
                $cart,
                $customer,
                $request->validated(),
                $request->idempotency_key
            );
        } catch (\DomainException $e) {
            $code = $e->getCode() === 409 ? 409 : 422;
            return ApiResponse::error($e->getMessage(), [], $code);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::error('Resource not found.', [], 404);
        } catch (\Throwable $e) {
            return ApiResponse::error('Payment failed: ' . $e->getMessage(), [], 422);
        }

        $order->load('subOrders.items.vendorListing');

        return ApiResponse::success(new OrderResource($order), 'Order placed successfully', 201);
    }

    public function confirmation(Request $request, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();

        $order = Order::with(['subOrders.items.vendorListing', 'subOrders.vendor'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return ApiResponse::success(new OrderResource($order));
    }
}
