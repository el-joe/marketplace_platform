<?php

use App\Http\Controllers\Marketer\AuthController;
use App\Http\Controllers\Marketer\CampaignController;
use App\Http\Controllers\Marketer\CountryController;
use App\Http\Controllers\Marketer\DashboardController;
use App\Http\Controllers\Marketer\EarningsController;
use App\Http\Controllers\Marketer\NotificationController;
use App\Http\Controllers\Marketer\PayoutController;
use App\Http\Controllers\Marketer\ProfileController;
use App\Http\Controllers\Marketer\QrCodeController;
use App\Http\Controllers\Marketer\SampleRequestController;
use App\Http\Controllers\Marketer\SupportTicketController;
use App\Http\Controllers\Marketer\TierController;
use App\Http\Controllers\Marketer\TrackingLinkController;
use App\Http\Controllers\Marketer\WhatsappLinkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketer API Routes — /api/marketer/v1/...
| Guard: marketer_api (JWT via tymon/jwt-auth)
| Authenticated middleware stack: marketer.api.auth → marketer.api.active
|
| NOTE: The public Boutiqaat storefront endpoint is deliberately NOT here.
|       It lives at /api/public/v1/marketers/{slug} → routes/api_public.php
|       because it is unauthenticated, publicly cacheable storefront content,
|       not a marketer-authenticated action.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // ── Auth (public) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->name('marketer.auth.')->group(function (): void {
        Route::post('register',       [AuthController::class, 'register'])->name('register');
        Route::post('login',          [AuthController::class, 'login'])->name('login')
            ->middleware('throttle:10,1');
        Route::post('forgot-password',[AuthController::class, 'forgotPassword'])->name('forgot-password')
            ->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // Public — needed to populate the country picker on the registration form.
    Route::get('countries', [CountryController::class, 'index'])->name('marketer.countries.index');

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware(['marketer.api.auth', 'marketer.api.active'])->group(function (): void {

        Route::prefix('auth')->name('marketer.auth.')->group(function (): void {
            Route::post('logout',        [AuthController::class, 'logout'])->name('logout');
            Route::post('refresh-token', [AuthController::class, 'refresh'])->name('refresh');
            Route::get('me',             [AuthController::class, 'me'])->name('me');
            Route::post('device-token',   [AuthController::class, 'registerDeviceToken'])->name('device-token.store');
            Route::delete('device-token', [AuthController::class, 'removeDeviceToken'])->name('device-token.destroy');
        });

        // ── Notifications ─────────────────────────────────────────────────────
        Route::prefix('notifications')->name('marketer.notifications.')->group(function (): void {
            Route::get('/',              [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count',  [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::put('/read-all',      [NotificationController::class, 'markAllRead'])->name('read-all');
            Route::put('/{id}/read',     [NotificationController::class, 'markRead'])->name('read');
        });

        Route::prefix('profile')->name('marketer.profile.')->group(function (): void {
            Route::get('/',            [ProfileController::class, 'show'])->name('show');
            Route::put('/',            [ProfileController::class, 'update'])->name('update');
            Route::post('photo',       [ProfileController::class, 'updatePhoto'])->name('photo');
            Route::put('password',     [ProfileController::class, 'updatePassword'])->name('password');
            Route::put('bank-account', [ProfileController::class, 'updateBankAccount'])->name('bank-account');
        });

        // ── Dashboard ─────────────────────────────────────────────────────────
        Route::get('dashboard', [DashboardController::class, 'index'])->name('marketer.dashboard');

        // ── Tiers (read-only ladder) ──────────────────────────────────────────
        Route::get('tiers', [TierController::class, 'index'])->name('marketer.tiers.index');

        // ── Tracking Links ────────────────────────────────────────────────────
        Route::get('tracking-link', [TrackingLinkController::class, 'base'])->name('marketer.tracking-link.base');
        Route::get('campaigns/{campaign}/tracking-link', [TrackingLinkController::class, 'forCampaign'])->name('marketer.tracking-link.campaign');

        // ── QR Codes ──────────────────────────────────────────────────────────
        Route::prefix('qr-codes')->name('marketer.qr-codes.')->group(function (): void {
            Route::get('/',        [QrCodeController::class, 'index'])->name('index');
            Route::post('/',       [QrCodeController::class, 'store'])->name('store');
            Route::get('/{qrCode}',    [QrCodeController::class, 'show'])->name('show');
            Route::delete('/{qrCode}', [QrCodeController::class, 'destroy'])->name('destroy');
        });

        // ── Campaigns ─────────────────────────────────────────────────────────
        Route::prefix('campaigns')->name('marketer.campaigns.')->group(function (): void {
            Route::get('/',    [CampaignController::class, 'index'])->name('index');
            Route::post('/',   [CampaignController::class, 'store'])->name('store');
            Route::get('/{campaign}',            [CampaignController::class, 'show'])->name('show');
            Route::get('/{campaign}/analytics',  [CampaignController::class, 'analytics'])->name('analytics');
            Route::get('/{campaign}/conversions',[CampaignController::class, 'conversions'])->name('conversions');

            // ── WhatsApp Links (campaign-scoped) ──────────────────────────────
            Route::get( '/{campaign}/whatsapp-links', [WhatsappLinkController::class, 'index'])->name('whatsapp-links.index');
            Route::post('/{campaign}/whatsapp-links', [WhatsappLinkController::class, 'store'])->name('whatsapp-links.store');

            // ── Sample Requests (campaign-scoped) ─────────────────────────────
            Route::get( '/{campaign}/samples', [SampleRequestController::class, 'campaignSamples'])->name('samples.show');
            Route::post('/{campaign}/samples', [SampleRequestController::class, 'store'])->name('samples.store');
        });

        // ── WhatsApp Link QR (link-scoped) ────────────────────────────────────
        Route::get('whatsapp-links/{whatsappLink}/qr-code', [WhatsappLinkController::class, 'qrCode'])
            ->name('marketer.whatsapp-links.qr-code');

        // ── Sample Requests (global list + detail) ────────────────────────────
        Route::prefix('sample-requests')->name('marketer.sample-requests.')->group(function (): void {
            Route::get('/',                        [SampleRequestController::class, 'index'])->name('index');
            Route::get('/{sampleRequest}',         [SampleRequestController::class, 'show'])->name('show');
            Route::post('/{sampleRequest}/mark-received', [SampleRequestController::class, 'markReceived'])->name('mark-received');
        });

        // ── Earnings (Conversions) ────────────────────────────────────────────
        Route::prefix('earnings')->name('marketer.earnings.')->group(function (): void {
            Route::get('/',           [EarningsController::class, 'index'])->name('index');
            Route::get('/summary',    [EarningsController::class, 'summary'])->name('summary');
            Route::get('/{conversion}', [EarningsController::class, 'show'])->name('show');
        });

        // ── Payouts ───────────────────────────────────────────────────────────
        Route::prefix('payouts')->name('marketer.payouts.')->group(function (): void {
            Route::get('/',           [PayoutController::class, 'index'])->name('index');
            Route::get('/{payout}',         [PayoutController::class, 'show'])->name('show');
            Route::get('/{payout}/invoice', [PayoutController::class, 'invoice'])->name('invoice');
        });

        // ── Support Tickets ───────────────────────────────────────────────────
        Route::prefix('support/tickets')->name('marketer.support.tickets.')->group(function (): void {
            Route::get('/',                                     [SupportTicketController::class, 'index'])->name('index');
            Route::post('/',                                    [SupportTicketController::class, 'store'])->name('store');
            Route::get('/{ticketNumber}',                       [SupportTicketController::class, 'show'])->name('show');
            Route::post('/{ticketNumber}/messages',             [SupportTicketController::class, 'addMessage'])->name('messages.store');
            Route::put('/{ticketNumber}/rate',                  [SupportTicketController::class, 'rate'])->name('rate');
        });
    });
});
