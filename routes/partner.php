<?php

use App\Http\Controllers\Partner\AuthController as PartnerAuthController;
use App\Http\Controllers\Partner\DashboardController;
use App\Http\Controllers\Partner\InventoryController;
use App\Http\Controllers\Partner\ListingController;
use App\Http\Controllers\Partner\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Panel Routes — partner.noon.loc
| Authenticated vendor area
|--------------------------------------------------------------------------
*/

// ── Suspended page (accessible without full auth) ────────────────────────
Route::get('/suspended', fn() => view('partner.suspended'))->name('suspended');

// ── Auth (guest only) ────────────────────────────────────────────────────
Route::middleware('guest:vendor')->group(function () {
    Route::get('/login', [PartnerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PartnerAuthController::class, 'login'])->name('login.submit');

    Route::get('/forgot-password', [PartnerAuthController::class, 'forgotPassword'])->name('auth.forgot');
    Route::post('/forgot-password', [PartnerAuthController::class, 'sendResetLink'])->name('auth.forgot.send');

    Route::get('/reset-password/{token}', [PartnerAuthController::class, 'resetPassword'])->name('auth.reset');
    Route::post('/reset-password', [PartnerAuthController::class, 'updatePassword'])->name('auth.reset.update');
});

Route::post('/logout', [PartnerAuthController::class, 'logout'])
    ->middleware('vendor.auth')
    ->name('logout');

// ── Protected panel ──────────────────────────────────────────────────────
Route::middleware(['vendor.auth', 'vendor.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Orders module ────────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->controller(OrderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/{subOrderNumber}', 'show')->name('show');
        Route::post('/{subOrderNumber}/confirm', 'confirm')->name('confirm');
        Route::post('/{subOrderNumber}/ship', 'ship')->name('ship');
        Route::post('/{subOrderNumber}/cancel', 'cancel')->name('cancel');
    });

    // ── Listings module ──────────────────────────────────────────────────────
    Route::prefix('listings')->name('listings.')->controller(ListingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/product-search', 'productSearch')->name('product-search');
        Route::get('/{listing}', 'show')->name('show');
        Route::post('/{listing}/update-price', 'updatePrice')->name('update-price');
        Route::post('/{listing}/toggle-status', 'toggleStatus')->name('toggle-status');
        Route::post('/{listing}/adjust-stock', 'adjustStock')->name('adjust-stock');
    });

    // ── Inventory module ─────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->controller(InventoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/low-stock', 'lowStock')->name('low-stock');
        Route::get('/out-of-stock', 'outOfStock')->name('out-of-stock');
        Route::get('/{listing}/movements', 'movements')->name('movements');
    });
});
