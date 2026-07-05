<?php

use App\Http\Controllers\MarketerPortal\AuthController;
use App\Http\Controllers\MarketerPortal\CampaignController;
use App\Http\Controllers\MarketerPortal\DashboardController;
use App\Http\Controllers\MarketerPortal\EarningsController;
use App\Http\Controllers\MarketerPortal\InvitationController;
use App\Http\Controllers\MarketerPortal\ProfileController;
use App\Http\Controllers\MarketerPortal\QrCodeController;
use App\Http\Controllers\MarketerPortal\SampleRequestController;
use App\Http\Controllers\MarketerPortal\TrackingController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ── Locale switcher (marketer subdomain) ─────────────────────────────────
Route::domain('marketer.' . env('APP_DOMAIN', 'localhost'))
    ->middleware('web')
    ->post('/locale/switch', function (\Illuminate\Http\Request $request) {
        $locale = $request->input('locale');
        abort_unless(in_array($locale, config('app.available_locales', ['ar', 'en'])), 422);
        $request->session()->put([
            'locale'          => $locale,
            'locale_override' => $locale,
            'dir'             => $locale === 'ar' ? 'rtl' : 'ltr',
        ]);
        \Carbon\Carbon::setLocale($locale);
        \Illuminate\Support\Facades\App::setLocale($locale);
        return back();
    })->name('marketer.locale.switch');

Route::domain('marketer.' . env('APP_DOMAIN', 'localhost'))
    ->name('marketer.')
    ->group(function () {

        Broadcast::routes(['middleware' => ['web', 'auth.marketer']]);

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

            // ── Notifications ───────────────────────────────────────────────────
            Route::prefix('notifications')->name('notifications.')
                ->controller(NotificationController::class)
                ->group(function () {
                    Route::get('/',              'index')->name('index');
                    Route::get('/recent',        'recent')->name('recent');
                    Route::get('/unread-count',  'unreadCount')->name('unread-count');
                    Route::post('/mark-all-read','markAllRead')->name('mark-all-read');
                    Route::post('/{id}/read',    'markRead')->name('mark-read');
                });

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

            // Product / classified / travel AJAX search
            Route::get('/campaigns/products/search', [CampaignController::class, 'searchProducts'])->name('campaigns.products.search');
            Route::get('/campaigns/classifieds/search', [CampaignController::class, 'searchClassifiedListings'])->name('campaigns.classifieds.search');
            Route::get('/campaigns/travel-packages/search', [CampaignController::class, 'searchTravelPackages'])->name('campaigns.travel-packages.search');

            // Tracking link generation + stats
            Route::get('/campaigns/{campaign}/link', [TrackingController::class, 'generateLink'])->name('campaigns.link');
            Route::get('/campaigns/{campaign}/stats', [TrackingController::class, 'stats'])->name('campaigns.stats');

            // Sample Requests
            Route::get('/samples', [SampleRequestController::class, 'index'])->name('samples.index');
            Route::post('/samples/{sampleRequest}/mark-received', [SampleRequestController::class, 'markReceived'])->name('samples.mark-received');

            // Earnings
            Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
            Route::get('/earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');

            // Wallet
            Route::get('/wallet', [\App\Http\Controllers\MarketerPortal\WalletController::class, 'index'])->name('wallet.index');
            Route::post('/wallet/withdraw', [\App\Http\Controllers\MarketerPortal\WalletController::class, 'requestWithdrawal'])->name('wallet.withdraw');

            // Invitations
            Route::prefix('invitations')->name('invitations.')->group(function () {
                Route::get('/', [InvitationController::class, 'index'])->name('index');
                Route::get('/{invitation}', [InvitationController::class, 'show'])->name('show');
                Route::post('/{invitation}/accept', [InvitationController::class, 'accept'])->name('accept');
                Route::post('/{invitation}/decline', [InvitationController::class, 'decline'])->name('decline');
            });
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
