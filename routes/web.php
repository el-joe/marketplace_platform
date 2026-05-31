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

