<?php

use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes — /api/customer/v1/{country}/...
| Guard: customer (JWT)
| Middleware: detect.country resolves {country} site_code → Country model
|--------------------------------------------------------------------------
*/

Route::prefix('v1/{country}')
    ->middleware('detect.country')
    ->group(function (): void {

        // ── Public auth endpoints ─────────────────────────────────────────────
        Route::prefix('auth')->name('customer.auth.')->group(function (): void {
            Route::post('register', [AuthController::class, 'register'])
                ->middleware('throttle:10,1')
                ->name('register');

            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:10,1')
                ->name('login');

            Route::post('refresh-token', [AuthController::class, 'refreshToken'])
                ->middleware('throttle:10,1')
                ->name('refresh');

            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
                ->middleware('throttle:5,1')
                ->name('forgot-password');

            Route::post('reset-password', [AuthController::class, 'resetPassword'])
                ->middleware('throttle:5,1')
                ->name('reset-password');

            // Email verification — token from email link, no auth guard needed
            Route::post('verify-email', [AuthController::class, 'verifyEmail'])
                ->name('verify-email');
        });

        // ── Authenticated endpoints ───────────────────────────────────────────
        Route::middleware('auth:customer')->group(function (): void {

            // Auth
            Route::prefix('auth')->name('customer.auth.')->group(function (): void {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('me', [AuthController::class, 'me'])->name('me');
                Route::post('resend-verification', [AuthController::class, 'resendVerification'])
                    ->middleware('throttle:3,1')
                    ->name('resend-verification');
            });

            // Profile
            Route::prefix('profile')->name('customer.profile.')->group(function (): void {
                Route::get('/', [ProfileController::class, 'show'])->name('show');
                Route::put('/', [ProfileController::class, 'update'])->name('update');
                Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
                Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
            });

            // Addresses
            Route::prefix('addresses')->name('customer.addresses.')->group(function (): void {
                Route::get('/', [AddressController::class, 'index'])->name('index');
                Route::post('/', [AddressController::class, 'store'])->name('store');
                Route::put('{address}', [AddressController::class, 'update'])->name('update');
                Route::delete('{address}', [AddressController::class, 'destroy'])->name('destroy');
                Route::put('{address}/set-default', [AddressController::class, 'setDefault'])->name('set-default');
            });
        });
    });
