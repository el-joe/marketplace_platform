<?php

use App\Http\Controllers\MarketerPortal\AdminOfferController;
use App\Http\Controllers\MarketerPortal\AffiliatePromoCodeController;
use App\Http\Controllers\MarketerPortal\AnalyticsController;
use App\Http\Controllers\MarketerPortal\AuthController;
use App\Http\Controllers\MarketerPortal\CampaignController;
use App\Http\Controllers\MarketerPortal\DashboardController;
use App\Http\Controllers\MarketerPortal\EarningsController;
use App\Http\Controllers\MarketerPortal\InfluencerDealController;
use App\Http\Controllers\MarketerPortal\InvitationController;
use App\Http\Controllers\MarketerPortal\MediaKitController;
use App\Http\Controllers\MarketerPortal\OpenMarketController;
use App\Http\Controllers\MarketerPortal\ProfileController;
use App\Http\Controllers\MarketerPortal\PromotionRequestController;
use App\Http\Controllers\MarketerPortal\QrCodeController;
use App\Http\Controllers\MarketerPortal\QuotaController;
use App\Http\Controllers\MarketerPortal\SampleRequestController;
use App\Http\Controllers\MarketerPortal\StoreProductController;
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

            // Analytics
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

            // Profile
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

            // My Store — same underlying profile data, "store" naming for the public-facing brand
            Route::get('/store', [ProfileController::class, 'edit'])->name('store.edit');
            Route::put('/store', [ProfileController::class, 'update'])->name('store.update');
            Route::get('/store/preview', [ProfileController::class, 'preview'])->name('store.preview');

            // QR Codes
            Route::get('/qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');
            Route::post('/qr-codes', [QrCodeController::class, 'generate'])->name('qr-codes.generate');
            Route::get('/qr-codes/{qrCode}/download', [QrCodeController::class, 'download'])->name('qr-codes.download');

            // Campaigns
            Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
            Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
            Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
            Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
            Route::post('/campaigns/{campaign}/whatsapp-link', [CampaignController::class, 'requestWhatsappLink'])->name('campaigns.whatsapp-link');
            Route::post('/campaigns/{campaign}/qr-code', [CampaignController::class, 'generateQrCode'])->name('campaigns.qr-code');
            Route::post('/campaigns/{campaign}/samples', [CampaignController::class, 'requestSamples'])->name('campaigns.samples');
            Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
            Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
            Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
            Route::post('/campaigns/{campaign}/request-pause', [CampaignController::class, 'requestPause'])->name('campaigns.request-pause');
            Route::post('/campaigns/{campaign}/resubmit', [CampaignController::class, 'resubmit'])->name('campaigns.resubmit');
            Route::post('/campaigns/{campaign}/conversions/datatable', [CampaignController::class, 'conversionsDatatable'])->name('campaigns.conversions.datatable');

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

            // Promotion Requests (vendor-initiated influencer slot invitations)
            Route::prefix('promotion-requests')->name('promotion-requests.')->group(function () {
                Route::get('/', [PromotionRequestController::class, 'index'])->name('index');
                Route::post('/{item}/accept', [PromotionRequestController::class, 'accept'])->name('accept');
                Route::post('/{item}/decline', [PromotionRequestController::class, 'decline'])->name('decline');
            });

            // Monthly Quota Dashboard
            Route::get('/quota', [QuotaController::class, 'index'])->name('quota.index');

            // Secret Promotions
            Route::prefix('secret-promotions')->name('secret-promotions.')->group(function () {
                Route::get('/', [\App\Http\Controllers\MarketerPortal\SecretPromotionController::class, 'index'])->name('index');
            });

            // Invitations
            Route::prefix('invitations')->name('invitations.')->group(function () {
                Route::get('/', [InvitationController::class, 'index'])->name('index');
                Route::get('/{invitation}', [InvitationController::class, 'show'])->name('show');
                Route::post('/{invitation}/accept', [InvitationController::class, 'accept'])->name('accept');
                Route::post('/{invitation}/decline', [InvitationController::class, 'decline'])->name('decline');
            });

            // Admin Offers (direct campaign invitations sent by admins)
            Route::prefix('admin-offers')->name('admin-offers.')->group(function () {
                Route::get('/', [AdminOfferController::class, 'index'])->name('index');
                Route::get('/{invitation}', [AdminOfferController::class, 'show'])->name('show');
                Route::post('/{invitation}/accept', [AdminOfferController::class, 'accept'])->name('accept');
                Route::post('/{invitation}/decline', [AdminOfferController::class, 'decline'])->name('decline');
            });

            // My Store Products (own branded products, all marketer types)
            Route::prefix('store-products')->name('store-products.')->group(function () {
                Route::get('/', [StoreProductController::class, 'index'])->name('index');
                Route::get('/create', [StoreProductController::class, 'create'])->name('create');
                Route::post('/', [StoreProductController::class, 'store'])->name('store');
                Route::get('/{product}/edit', [StoreProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [StoreProductController::class, 'update'])->name('update');
                Route::post('/{product}/submit', [StoreProductController::class, 'submitForReview'])->name('submit');
                Route::delete('/{product}', [StoreProductController::class, 'destroy'])->name('destroy');
            });

            // Open Market (browse + promote other marketers' own products — quota category 2)
            Route::prefix('open-market')->name('open-market.')->group(function () {
                Route::get('/', [OpenMarketController::class, 'index'])->name('index');
                Route::post('/{product}/promote', [OpenMarketController::class, 'promote'])->name('promote');
            });

            // ── Affiliate only: Promo Codes ───────────────────────────────────────
            Route::middleware('marketer.affiliate')->group(function () {
                Route::resource('promo-codes', AffiliatePromoCodeController::class)->only(['index', 'store', 'show']);
            });

            // ── Influencer only: Deals + Deliverables + Media Kit ────────────────
            Route::middleware('marketer.influencer')->group(function () {
                Route::get('/deals', [InfluencerDealController::class, 'index'])->name('deals.index');
                Route::get('/deals/{deal}', [InfluencerDealController::class, 'show'])->name('deals.show');
                Route::post('/deals/{deal}/accept', [InfluencerDealController::class, 'accept'])->name('deals.accept');
                Route::post('/deals/{deal}/reject', [InfluencerDealController::class, 'reject'])->name('deals.reject');
                Route::post('/deals/{deal}/deliverables/{deliverable}/submit', [InfluencerDealController::class, 'submitDeliverable'])->name('deals.deliverables.submit');

                Route::get('/media-kit', [MediaKitController::class, 'show'])->name('media-kit.show');
                Route::put('/media-kit', [MediaKitController::class, 'update'])->name('media-kit.update');
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
