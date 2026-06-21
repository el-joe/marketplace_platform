<?php

use App\Http\Controllers\Marketer\PublicProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes — /api/public/v1/...
| No auth guard — these endpoints are fully public / unauthenticated.
|
| EXCEPTION NOTE: The marketer public profile endpoint deliberately lives
| here instead of under /api/marketer/v1/ because it is public storefront
| content (unauthenticated, cacheable, rate-limited) — not a marketer-
| authenticated action. This matches the Boutiqaat-style pattern where the
| profile URL is a public-facing page consumers browse.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // ── Marketer public profiles ───────────────────────────────────────────────
    // Aggressively rate-limited: 30 req/min per IP (public, unauthenticated).
    // Response is cached 10 min per slug inside PublicProfileService.
    Route::get('marketers/{slug}', [PublicProfileController::class, 'show'])
        ->name('public.marketer.profile')
        ->middleware('throttle:30,1');
});
