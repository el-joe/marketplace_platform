<?php

namespace App\Http\Middleware;

use App\Services\MarketerTrackingService;
use App\Models\Marketer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackMarketerClick
{
    public function __construct(private readonly MarketerTrackingService $trackingService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // ── Resolve slug + ref from all possible entry methods ────────────────

        // 1. Route path: /r/{slug}
        $slug = $request->route('slug');

        // 2. Query: ?ref=REFERRAL_CODE or ?bc=BARCODE
        $ref = $request->query('ref') ?? $request->query('bc');

        // 3. If no slug from route, check existing session attribution
        if (!$slug) {
            $attribution = $request->session()->get('marketer_attribution');

            if (!$attribution && $cookie = $request->cookie('marketer_ref')) {
                $data = json_decode($cookie, true);
                if (is_array($data)) {
                    $attribution = $data;
                }
            }

            // Re-resolve from cookie/session without creating new click
            // (attribution already stored on original visit)
        }

        // 4. If we have a slug (from route or QR), track the click
        if ($slug) {
            try {
                $this->trackingService->handleTrackingRequest($request, $slug, $ref);
            } catch (\Throwable) {
                // Never break the storefront for tracking errors
            }
        }

        // 5. Restore auto-coupon from WhatsApp session into cart
        // (CartService integration point — cart is handled in storefront checkout)

        return $next($request);
    }
}
