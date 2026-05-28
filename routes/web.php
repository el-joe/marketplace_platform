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

