<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\OrderCancelRequest;
use App\Http\Requests\Customer\Order\OrderListRequest;
use App\Http\Resources\Customer\OrderDetailResource;
use App\Http\Resources\Customer\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Services\Customer\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(OrderListRequest $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $paginator = $this->orderService->listForCustomer($customer, $request->validated());

        return ApiResponse::paginated($paginator, OrderResource::class);
    }

    public function show(string $country, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->orderService->findForCustomer($customer, $orderNumber);

        if (!$order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        return ApiResponse::success(new OrderDetailResource($order));
    }

    public function cancel(OrderCancelRequest $request, string $country, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $order = $this->orderService->findForCustomer($customer, $orderNumber);

        if (!$order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        if (!$this->orderService->canCancel($order)) {
            return ApiResponse::error('This order cannot be cancelled in its current status.', [], 422);
        }

        $this->orderService->cancel($order, $request->validated('reason'));

        return ApiResponse::success(null, 'Order cancelled successfully.');
    }

    public function trackSubOrder(string $country, string $subOrderId): JsonResponse
    {
        $customer = auth('customer')->user();
        $subOrder = $this->orderService->trackSubOrder($customer, $subOrderId);

        if (!$subOrder) {
            return ApiResponse::error('Sub-order not found.', [], 404);
        }

        return ApiResponse::success([
            'sub_order_number'       => $subOrder->sub_order_number,
            'status'                 => $subOrder->status,
            'tracking_number'        => $subOrder->tracking_number,
            'carrier'                => $subOrder->carrier?->name,
            'estimated_delivery_date' => $subOrder->estimated_delivery_date?->toDateString(),
            'shipped_at'             => $subOrder->shipped_at?->toIso8601String(),
            'delivered_at'           => $subOrder->delivered_at?->toIso8601String(),
            'shipments'              => $subOrder->shipments->map(fn ($s) => [
                'id'             => $s->id,
                'tracking_events' => $s->trackingEvents->map(fn ($e) => [
                    'status'     => $e->status,
                    'location'   => $e->location ?? null,
                    'occurred_at' => $e->occurred_at?->toIso8601String(),
                ]),
            ]),
        ]);
    }
}
