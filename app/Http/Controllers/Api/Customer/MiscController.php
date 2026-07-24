<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// TODO: implement flat GET /countries + GET /shipping-methods.
// Country-scoped shipping methods already exist at
// checkout/shipping-methods in App\Http\Controllers\Customer\CheckoutController.
class MiscController extends Controller
{
    public function countries(Request $request): JsonResponse
    {
        return ApiResponse::error('Not implemented.', [], 501);
    }

    public function shippingMethods(Request $request): JsonResponse
    {
        return ApiResponse::error('Not implemented.', [], 501);
    }
}
