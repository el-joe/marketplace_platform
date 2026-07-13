<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function show(string $_country, string $code): JsonResponse
    {
        $coupon = Coupon::where('code', $code)->first();

        abort_if(! $coupon, 404, 'Coupon not found.');

        return ApiResponse::success([
            'code'               => $coupon->code,
            'name'               => $coupon->name,
            'description'        => $coupon->description,
            'type'               => $coupon->type->value,
            'value'              => $coupon->value,
            'min_order_amount'   => $coupon->min_order_amount,
            'max_discount'       => $coupon->max_discount,
            'valid_until'        => $coupon->valid_until->toIso8601String(),
            'is_stackable'       => $coupon->is_stackable,
        ]);
    }
}
