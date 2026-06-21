<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Return\ReturnStoreRequest;
use App\Http\Resources\Customer\ReturnRequestResource;
use App\Http\Responses\ApiResponse;
use App\Services\Customer\ReturnService;
use Illuminate\Http\JsonResponse;

class ReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returnService) {}

    public function store(ReturnStoreRequest $request, string $country, string $orderNumber): JsonResponse
    {
        $customer = auth('customer')->user();

        $order = $customer->orders()->where('order_number', $orderNumber)->first();

        if (!$order) {
            return ApiResponse::error('Order not found.', [], 404);
        }

        $returnRequest = $this->returnService->store($customer, $order, $request->validated());

        return ApiResponse::success(new ReturnRequestResource($returnRequest), 'Return request submitted.', 201);
    }

    public function index(string $country): JsonResponse
    {
        $customer = auth('customer')->user();
        $paginator = $this->returnService->listForCustomer($customer);

        return ApiResponse::paginated($paginator, ReturnRequestResource::class);
    }

    public function show(string $country, string $returnNumber): JsonResponse
    {
        $customer = auth('customer')->user();
        $returnRequest = $this->returnService->findForCustomer($customer, $returnNumber);

        if (!$returnRequest) {
            return ApiResponse::error('Return request not found.', [], 404);
        }

        return ApiResponse::success(new ReturnRequestResource($returnRequest));
    }
}
