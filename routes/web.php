<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Admin subdomain routes
|--------------------------------------------------------------------------
*/
Route::domain('admin.' . env('APP_DOMAIN', 'localhost'))->name('admin.')->group(function () {
    require __DIR__ . '/admin.php';
});

/*
|--------------------------------------------------------------------------
| Vendor Portal (public landing + registration)
| portal.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('portal.' . env('APP_DOMAIN', 'localhost'))->name('portal.')->group(
    base_path('routes/portal.php')
);

/*
|--------------------------------------------------------------------------
| Partner Panel (authenticated vendor area)
| partner.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('partner.' . env('APP_DOMAIN', 'localhost'))->name('partner.')->group(
    base_path('routes/partner.php')
);

/*
|--------------------------------------------------------------------------
| Delivery Agent Panel (mobile-first)
| delivery.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('delivery.' . env('APP_DOMAIN', 'localhost'))->group(
    base_path('routes/delivery.php')
);

/*
|--------------------------------------------------------------------------
| Marketer Portal
| marketer.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('marketer.' . env('APP_DOMAIN', 'localhost'))->group(
    base_path('routes/marketer.php')
);

/*
|--------------------------------------------------------------------------
| Travel Agency Portal
| travel-agency.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('travel-agency.' . env('APP_DOMAIN', 'localhost'))->group(
    base_path('routes/travel.php')
);

/*
|--------------------------------------------------------------------------
| Carrier (Shipping Company Supervisor) Portal
| carrier.noon.loc
|--------------------------------------------------------------------------
*/
Route::domain('carrier.' . env('APP_DOMAIN', 'localhost'))->group(
    base_path('routes/carrier.php')
);

/*
|--------------------------------------------------------------------------
| Storefront Tracking Redirects
| {country}.noon.loc/r/{slug}
|--------------------------------------------------------------------------
*/
Route::prefix('{country}/r')
    ->middleware(['web', 'track.marketer.click'])
    ->group(function () {
        Route::get('/{slug}', [\App\Http\Controllers\MarketerPortal\TrackingController::class, 'redirect'])
            ->name('marketer.tracking.redirect');
    });

/*
|--------------------------------------------------------------------------
| Storefront — Now Nawy curated feed
| {country}.noon.loc/now-nawy
|--------------------------------------------------------------------------
*/
Route::prefix('{country}')
    ->middleware(['web'])
    ->name('nawy.')
    ->group(function () {
        Route::get('/now-nawy', [\App\Http\Controllers\Storefront\NawyController::class, 'index'])
            ->name('index');
        Route::get('/now-nawy/category/{category}', [\App\Http\Controllers\Storefront\NawyController::class, 'byCategory'])
            ->name('category');
    });

/*
|--------------------------------------------------------------------------
| Storefront — Classifieds Marketplace
| {country}.noon.loc/classifieds
|--------------------------------------------------------------------------
*/
Route::prefix('{country}/classifieds')
    ->middleware(['web'])
    ->name('classifieds.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'index'])
            ->name('index');
        Route::get('/map-data', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'mapData'])
            ->name('map-data');

        // Auth-required routes (before /{listingNumber} wildcard)
        Route::middleware('auth:customer')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'create'])
                ->name('create');
            Route::post('/draft', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'storeDraft'])
                ->name('draft');
            Route::post('/{listing}/sign-contract', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'signContract'])
                ->name('sign');
        });

        Route::get('/{listingNumber}', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'show'])
            ->name('show');
        Route::post('/{listing}/inquire', [\App\Http\Controllers\Storefront\ClassifiedController::class, 'inquire'])
            ->name('inquire');
    });

/*
|--------------------------------------------------------------------------
| Storefront — Travel Packages
| {country}.noon.loc/travel
|--------------------------------------------------------------------------
*/
Route::prefix('{country}/travel')
    ->middleware(['web'])
    ->name('travel.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Storefront\TravelController::class, 'index'])
            ->name('index');
        Route::get('/booking/{booking}/confirmed', [\App\Http\Controllers\Storefront\TravelController::class, 'bookingConfirmed'])
            ->name('booking.confirmed')
            ->middleware('auth:customer');
        Route::get('/{package}', [\App\Http\Controllers\Storefront\TravelController::class, 'show'])
            ->name('show');
        Route::middleware('auth:customer')->group(function () {
            Route::get('/{package}/book', [\App\Http\Controllers\Storefront\TravelController::class, 'bookForm'])
                ->name('book');
            Route::post('/{package}/book', [\App\Http\Controllers\Storefront\TravelController::class, 'book'])
                ->name('book.submit');
        });
    });

/*
|--------------------------------------------------------------------------
| Storefront — Virtual Try-On
| {country}.noon.loc/try-on/{listing}
| Only available for listings whose category has supports_virtual_tryon = 1
|--------------------------------------------------------------------------
*/
Route::prefix('{country}/try-on')
    ->middleware(['web'])
    ->name('tryon.')
    ->group(function () {
        Route::get('/{listing}', [\App\Http\Controllers\Storefront\TryOnController::class, 'create'])
            ->name('create');
        Route::post('/{listing}', [\App\Http\Controllers\Storefront\TryOnController::class, 'process'])
            ->name('process');
        Route::get('/status/{sessionId}', [\App\Http\Controllers\Storefront\TryOnController::class, 'status'])
            ->name('status');
    });


/*
|--------------------------------------------------------------------------
| Storefront — Radio
| {country}/radio
|--------------------------------------------------------------------------
*/
Route::prefix('{country}/radio')
    ->middleware(['web'])
    ->name('storefront.radio.')
    ->group(function () {
        Route::get('/',              [\App\Http\Controllers\Storefront\RadioController::class, 'index'])->name('index');
        Route::get('/{channel}',     [\App\Http\Controllers\Storefront\RadioController::class, 'player'])->name('player');
    });

// Session tracking (no country prefix — called from JS cross-page)
Route::post('/radio/session', [\App\Http\Controllers\Storefront\RadioController::class, 'trackSession'])
    ->middleware(['web'])
    ->name('storefront.radio.session');

// ─── Delivery Rating (customer rates carrier after delivery) ──────────────
// NOTE: carrier identity is NEVER returned to the customer — only rating stored
Route::post('/orders/{subOrder}/rate-delivery', [\App\Http\Controllers\Storefront\DeliveryRatingController::class, 'store'])
    ->middleware(['web', 'auth:customer'])
    ->name('storefront.orders.rate-delivery');
