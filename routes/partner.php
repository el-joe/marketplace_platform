<?php

use App\Http\Controllers\Partner\AuthController as PartnerAuthController;
use App\Http\Controllers\Partner\DashboardController;
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
    // Additional partner panel routes will be added here in subsequent modules.
});
