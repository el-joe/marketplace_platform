<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ReturnRespondRequest;
use App\Http\Resources\Vendor\ReturnRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\ReturnRequest;

class ReturnController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $vendorId = auth('vendor')->user()->vendor_id;

        $returns = ReturnRequest::where('vendor_id', $vendorId)
            ->latest()
            ->paginate(20);

        return ApiResponse::paginated($returns, ReturnRequestResource::class);
    }

    public function show(string $returnNumber): \Illuminate\Http\JsonResponse
    {
        $return = $this->resolveReturn($returnNumber);

        $return->load([
            'subOrder:id,sub_order_number',
            'items.orderItem:id,product_snapshot',
        ]);

        return ApiResponse::success(new ReturnRequestResource($return));
    }

    public function respond(ReturnRespondRequest $request, string $returnNumber): \Illuminate\Http\JsonResponse
    {
        $return = $this->resolveReturn($returnNumber);

        if (! in_array($return->status, ['requested'])) {
            return ApiResponse::error("Cannot respond to a return in '{$return->status}' status.", [], 422);
        }

        if ($request->decision === 'approve') {
            $return->update([
                'status'           => 'approved',
                'rejection_reason' => null,
            ]);
            $message = 'Return approved.';
        } else {
            $return->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->notes,
            ]);
            $message = 'Return rejected.';
        }

        return ApiResponse::success(new ReturnRequestResource($return), $message);
    }

    private function resolveReturn(string $returnNumber): ReturnRequest
    {
        $vendorId = auth('vendor')->user()->vendor_id;

        return ReturnRequest::where('return_number', $returnNumber)
            ->where('vendor_id', $vendorId)
            ->firstOrFail();
    }
}
