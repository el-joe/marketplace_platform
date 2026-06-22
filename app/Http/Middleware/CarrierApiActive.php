<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CarrierApiActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\ShippingCompanySupervisor $supervisor */
        $supervisor = auth()->guard('shipping_supervisor_api')->user();

        if (! $supervisor->is_active) {
            auth()->guard('shipping_supervisor_api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Contact your company administrator.',
            ], 403);
        }

        $company = $supervisor->company;

        if ($company->status === 'pending') {
            auth()->guard('shipping_supervisor_api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your company is pending platform approval.',
                'company_status' => 'pending',
            ], 403);
        }

        if ($company->status === 'suspended') {
            auth()->guard('shipping_supervisor_api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your company has been suspended. Please contact platform support.',
                'company_status' => 'suspended',
            ], 403);
        }

        return $next($request);
    }
}
