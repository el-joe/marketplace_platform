<?php

use App\Http\Controllers\MarketerPortal\AuthController;
use App\Http\Controllers\MarketerPortal\CampaignController;
use App\Http\Controllers\MarketerPortal\DashboardController;
use App\Http\Controllers\MarketerPortal\EarningsController;
use App\Http\Controllers\MarketerPortal\ProfileController;
use App\Http\Controllers\MarketerPortal\QrCodeController;
use App\Http\Controllers\MarketerPortal\TrackingController;
use Illuminate\Support\Facades\Route;

Route::domain('marketer.' . env('APP_DOMAIN', 'localhost'))
    ->name('marketer.')
    ->group(function () {

        // ── Public: Boutiqaat profile ─────────────────────────────────────────
        Route::get('/p/{slug}', [ProfileController::class, 'boutiqaat'])->name('profile.public');

        // ── Guest ─────────────────────────────────────────────────────────────
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.post');

        // ── Authenticated ─────────────────────────────────────────────────────
        Route::middleware(['auth.marketer'])->group(function () {

            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Profile
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

            // QR Codes
            Route::get('/qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');
            Route::post('/qr-codes', [QrCodeController::class, 'generate'])->name('qr-codes.generate');
            Route::get('/qr-codes/{qrCode}/download', [QrCodeController::class, 'download'])->name('qr-codes.download');

            // Campaigns
            Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
            Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
            Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
            Route::post('/campaigns/{campaign}/whatsapp-link', [CampaignController::class, 'requestWhatsappLink'])->name('campaigns.whatsapp-link');
            Route::post('/campaigns/{campaign}/qr-code', [CampaignController::class, 'generateQrCode'])->name('campaigns.qr-code');
            Route::post('/campaigns/{campaign}/samples', [CampaignController::class, 'requestSamples'])->name('campaigns.samples');

            // Product AJAX search
            Route::get('/campaigns/products/search', [CampaignController::class, 'searchProducts'])->name('campaigns.products.search');

            // Tracking link generation + stats
            Route::get('/campaigns/{campaign}/link', [TrackingController::class, 'generateLink'])->name('campaigns.link');
            Route::get('/campaigns/{campaign}/stats', [TrackingController::class, 'stats'])->name('campaigns.stats');

            // Earnings
            Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
            Route::get('/earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');
        });
    });

// Route::domain('marketer.' . env('APP_DOMAIN', 'localhost'))
//     ->name('marketer.')
//     ->group(function () {

//         // ── Guest ─────────────────────────────────────────────────────────────
//         Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
//         Route::post('/login', [AuthController::class, 'login'])->name('login.post');

//         // ── Authenticated ─────────────────────────────────────────────────────
//         Route::middleware(['auth.marketer'])->group(function () {

//             Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

//             // Dashboard
//             Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

//             // Campaigns
//             Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
//             Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
//             Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
//             Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

//             // Product AJAX search
//             Route::get('/campaigns/products/search', [CampaignController::class, 'searchProducts'])->name('campaigns.products.search');

//             // Tracking link generation + stats
//             Route::get('/campaigns/{campaign}/link', [TrackingController::class, 'generateLink'])->name('campaigns.link');
//             Route::get('/campaigns/{campaign}/stats', [TrackingController::class, 'stats'])->name('campaigns.stats');

//             // Earnings
//             Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
//             Route::get('/earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');
//         });
//     });
