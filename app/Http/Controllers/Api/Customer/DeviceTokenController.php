<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// TODO: implement flat POST /device-tokens.
// Country-scoped device registration already exists at
// notifications/device-token in App\Http\Controllers\Customer\NotificationController.
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return ApiResponse::error('Not implemented.', [], 501);
    }
}
