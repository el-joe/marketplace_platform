<?php

namespace App\Http\Controllers\Delivery\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Delivery\ShiftStatusResource;
use App\Http\Responses\ApiResponse;
use App\Models\DeliveryAgent;
use App\Services\Delivery\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shiftService) {}

    public function start(): JsonResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        try {
            $shift = $this->shiftService->start($agent);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(
            ['shift' => new ShiftStatusResource($shift)],
            'Shift started.'
        );
    }

    public function end(): JsonResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        try {
            $this->shiftService->end($agent);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(null, 'Shift ended.');
    }

    public function setAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        if ($agent->status !== 'on_shift') {
            return ApiResponse::error('You must be on shift to toggle availability.', [], 422);
        }

        $agent->update(['is_available' => $request->boolean('is_available')]);

        return ApiResponse::success(
            ['is_available' => $agent->is_available],
            $agent->is_available ? 'You are now available for deliveries.' : 'You are now on a break.'
        );
    }
}
