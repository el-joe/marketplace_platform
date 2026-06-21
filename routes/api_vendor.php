<?php

use App\Http\Controllers\Vendor\AuthController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\DisputeController;
use App\Http\Controllers\Vendor\OnboardingController;
use App\Http\Controllers\Vendor\OrderController;
use App\Http\Controllers\Vendor\ProfileController;
use App\Http\Controllers\Vendor\ReturnController;
use App\Http\Controllers\Vendor\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor API Routes — /api/vendor/v1/...
| Guard: vendor (JWT via tymon/jwt-auth)
| Authenticated middleware stack: vendor.auth → vendor.active
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // ── Auth (public) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->name('vendor.auth.')->group(function (): void {
        Route::post('login',          [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password',[AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware(['vendor.auth', 'vendor.active'])->group(function (): void {

        Route::prefix('auth')->name('vendor.auth.')->group(function (): void {
            Route::post('logout',        [AuthController::class, 'logout'])->name('logout');
            Route::post('refresh-token', [AuthController::class, 'refresh'])->name('refresh');
            Route::get('me',             [AuthController::class, 'me'])->name('me');
        });

        // ── Onboarding wizard (accessible before onboarding is complete) ──────
        Route::prefix('onboarding')->name('vendor.onboarding.')->group(function (): void {
            Route::get('status',            [OnboardingController::class, 'status'])->name('status');
            Route::put('store-identity',    [OnboardingController::class, 'storeIdentity'])->name('store-identity');
            Route::put('business-details',  [OnboardingController::class, 'businessDetails'])->name('business-details');
            Route::post('documents',        [OnboardingController::class, 'uploadDocument'])->name('documents');
            Route::post('bank-account',     [OnboardingController::class, 'bankAccount'])->name('bank-account');
            Route::put('shipping-settings', [OnboardingController::class, 'shippingSettings'])->name('shipping-settings');
            Route::post('complete',         [OnboardingController::class, 'complete'])->name('complete');
        });

        // ── Onboarded-only routes ─────────────────────────────────────────────
        Route::middleware('vendor.onboarded')->group(function (): void {

            // Profile
            Route::prefix('profile')->name('vendor.profile.')->group(function (): void {
                Route::get('/',                          [ProfileController::class, 'show'])->name('show');
                Route::put('/',                          [ProfileController::class, 'update'])->name('update');
                Route::put('password',                   [ProfileController::class, 'updatePassword'])->name('password');
                Route::get('documents',                  [ProfileController::class, 'documents'])->name('documents');
                Route::post('documents/{type}/reupload', [ProfileController::class, 'reuploadDocument'])->name('documents.reupload');
            });

            // Team
            Route::prefix('team')->name('vendor.team.')->group(function (): void {
                Route::get('/',                  [TeamController::class, 'index'])->name('index');
                Route::post('/',                 [TeamController::class, 'store'])->name('store');
                Route::put('{id}/toggle-active', [TeamController::class, 'toggleActive'])->name('toggle-active');
                Route::delete('{id}',            [TeamController::class, 'destroy'])->name('destroy');
            });

            // Dashboard
            Route::get('dashboard', DashboardController::class)->name('vendor.dashboard');

            // Orders & fulfillment
            Route::prefix('orders')->name('vendor.orders.')->group(function (): void {
                Route::get('/',                                [OrderController::class, 'index'])->name('index');
                Route::get('{subOrderNumber}',                 [OrderController::class, 'show'])->name('show');
                Route::post('{subOrderNumber}/ship',           [OrderController::class, 'ship'])->name('ship');
                Route::post('{subOrderNumber}/cancel',         [OrderController::class, 'cancel'])->name('cancel');
                Route::get('{subOrderNumber}/packing-slip',   [OrderController::class, 'packingSlip'])->name('packing-slip');
            });

            // Disputes (vendor side)
            Route::prefix('disputes')->name('vendor.disputes.')->group(function (): void {
                Route::get('/',                                  [DisputeController::class, 'index'])->name('index');
                Route::get('{disputeNumber}',                    [DisputeController::class, 'show'])->name('show');
                Route::post('{disputeNumber}/messages',          [DisputeController::class, 'addMessage'])->name('messages.store');
                Route::post('{disputeNumber}/evidence',          [DisputeController::class, 'addEvidence'])->name('evidence.store');
            });

            // Returns (vendor side — read + respond only)
            Route::prefix('returns')->name('vendor.returns.')->group(function (): void {
                Route::get('/',                              [ReturnController::class, 'index'])->name('index');
                Route::get('{returnNumber}',                 [ReturnController::class, 'show'])->name('show');
                Route::post('{returnNumber}/respond',        [ReturnController::class, 'respond'])->name('respond');
            });
        });
    });
});
