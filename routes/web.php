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

