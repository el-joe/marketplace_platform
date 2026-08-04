<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// TODO: implement generic OTP send/verify flow (registration/login).
class OtpController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        return ApiResponse::error(__('customer_api.not_implemented'), [], 501);
    }

    public function verify(Request $request): JsonResponse
    {
        return ApiResponse::error(__('customer_api.not_implemented'), [], 501);
    }
}
