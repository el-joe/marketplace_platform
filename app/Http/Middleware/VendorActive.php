<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorActive
{
    /**
     * Allow only vendors whose global_status is active or under_review.
     * Suspended / blacklisted vendors get a 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendorAdmin = Auth::guard('vendor')->user();

        if ($vendorAdmin) {
            $vendor = $vendorAdmin->vendor;

            if ($vendor && !in_array($vendor->global_status, ['active', 'under_review'], true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been suspended.',
                    ], 403);
                }

                Auth::guard('vendor')->logout();
                return redirect()->route('partner.login')
                    ->with('error', 'Your store account has been suspended. Please contact support.');
            }
        }

        return $next($request);
    }
}
