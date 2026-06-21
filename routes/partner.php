<?php

use App\Http\Controllers\Partner\AuthController as PartnerAuthController;
use App\Http\Controllers\Partner\BankAccountController;
use App\Http\Controllers\Partner\DashboardController;
use App\Http\Controllers\Partner\InventoryController;
use App\Http\Controllers\Partner\ListingController;
use App\Http\Controllers\Partner\OrderController;
use App\Http\Controllers\Partner\FlashSaleController;
use App\Http\Controllers\Partner\PayoutController;
use App\Http\Controllers\Partner\PerformanceController;
use App\Http\Controllers\Partner\ProfileController;
use App\Http\Controllers\Partner\DisputeController;
use App\Http\Controllers\Partner\SupportController;
use App\Http\Controllers\Partner\SubscriptionController as PartnerSubscriptionController;
use App\Http\Controllers\Partner\FulfillmentController;
use App\Http\Controllers\Partner\MarketerCampaignController as PartnerMarketerCampaignController;
use App\Http\Controllers\Partner\MarketerSampleController as PartnerMarketerSampleController;
use App\Http\Controllers\Partner\TeamController;
use App\Http\Controllers\Partner\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Panel Routes — partner.noon.loc
| Authenticated vendor area
|--------------------------------------------------------------------------
*/

// ── Suspended page (accessible without full auth) ────────────────────────
Route::get('/suspended', fn() => view('partner.suspended'))->name('suspended');

Route::redirect('/', '/login')->name('home');

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

    // ── Payouts module ───────────────────────────────────────────────────────
    Route::prefix('payouts')->name('payouts.')->controller(PayoutController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/earnings-summary', 'earningsSummary')->name('earnings-summary');
        Route::get('/{payoutNumber}', 'show')->name('show');
    });

    // ── Bank Accounts module ─────────────────────────────────────────────────
    Route::prefix('bank-accounts')->name('bank-accounts.')->controller(BankAccountController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{account}/set-primary', 'setPrimary')->name('set-primary');
        Route::delete('/{account}', 'destroy')->name('destroy');
    });
    // ── Flash Sales module ────────────────────────────────────────────────────
    Route::prefix('flash-sales')->name('flash-sales.')->controller(FlashSaleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        // Static sub-routes BEFORE /{flashSaleId} wildcard
        Route::get('/{flashSaleId}/eligible-listings', 'eligibleListings')->name('eligible-listings');
        Route::post('/{flashSaleId}/submit', 'submit')->name('submit');
        Route::get('/{flashSaleId}/live-stats', 'liveStats')->name('live-stats');
        Route::get('/{flashSaleId}', 'show')->name('show');
    });

    // ── Performance module ───────────────────────────────────────────────────
    Route::prefix('performance')->name('performance.')->controller(PerformanceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/stats', 'stats')->name('stats');
    });

    // ── Profile & store settings ─────────────────────────────────────────────
    Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/update-store', 'updateStore')->name('update-store');
        Route::post('/update-password', 'updatePassword')->name('update-password');
    });

    // ── Documents ────────────────────────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'documentsIndex')->name('index');
        Route::post('/upload', 'uploadDocument')->name('upload');
    });

    // ── Team management ──────────────────────────────────────────────────────
    Route::prefix('team')->name('team.')->controller(TeamController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{member}/toggle-active', 'toggleActive')->name('toggle-active');
        Route::delete('/{member}', 'destroy')->name('destroy');
    });

    // ── Support tickets ──────────────────────────────────────────────────────
    Route::prefix('support/tickets')->name('support.tickets.')->controller(SupportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticketNumber}', 'show')->name('show');
        Route::post('/{ticketNumber}/reply', 'reply')->name('reply');
    });

    // ── Disputes ─────────────────────────────────────────────────────────────
    Route::prefix('disputes')->name('disputes.')->controller(DisputeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{disputeNumber}', 'show')->name('show');
        Route::post('/{disputeNumber}/reply', 'reply')->name('reply');
        Route::post('/{disputeNumber}/evidence', 'uploadEvidence')->name('evidence');
    });

    // ── Fulfillment (FBN / FBP / Marketplace) ───────────────────────────────
    Route::prefix('fulfillment')->name('fulfillment.')->controller(FulfillmentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/fbn-requests', 'fbnRequests')->name('fbn.requests');
        Route::post('/fbn/submit', 'submitInboundRequest')->name('fbn.submit');
        Route::post('/fbn/{request}/cancel', 'cancelInboundRequest')->name('fbn.cancel');
        Route::get('/fbp-inventory', 'fbpInventory')->name('fbp.inventory');
        Route::get('/storage-fees', 'storageFees')->name('storage-fees');
    });

    // ── Subscription ──────────────────────────────────────────────────────────
    Route::prefix('subscription')->name('subscription.')->controller(PartnerSubscriptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/subscribe', 'subscribe')->name('subscribe');
        Route::post('/cancel', 'cancel')->name('cancel');
    });

    // ── Marketer Campaigns (read-only for vendors) ───────────────────────────────
    Route::prefix('marketer-campaigns')->name('marketer-campaigns.')->controller(PartnerMarketerCampaignController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{campaign}', 'show')->name('show');
    });

    // ── Marketer Sample Requests ─────────────────────────────────────────────────
    Route::prefix('marketer-samples')->name('marketer-samples.')->controller(PartnerMarketerSampleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{req}/approve', 'approve')->name('approve');
        Route::post('/{req}/reject', 'reject')->name('reject');
    });

    // ── Wallet ────────────────────────────────────────────────────────────────
    Route::prefix('wallet')->name('wallet.')->controller(\App\Http\Controllers\Partner\WalletController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/withdraw', 'requestWithdrawal')->name('withdraw');
    });

    // ── AI Tools ──────────────────────────────────────────────────────────
    Route::prefix('ai')->name('ai.')->controller(\App\Http\Controllers\Partner\AiToolsController::class)->group(function () {
        Route::get('/credits', 'credits')->name('credits');
        Route::get('/{listing}', 'index')->name('index');

        // Image enhancement
        Route::post('/enhance/{listing}/{image}', 'enhanceImage')->name('enhance');
        Route::get('/enhance/status/{jobId}', 'checkEnhancementStatus')->name('enhance.status');
        Route::post('/enhance/apply/{jobId}', 'applyEnhancement')->name('enhance.apply');

        // Video generation
        Route::post('/video/generate', 'generateVideo')->name('video.generate');
        Route::get('/video/status/{jobId}', 'checkVideoStatus')->name('video.status');
    });

    // ─── Carrier Claims ───────────────────────────────────────────────────────
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Partner\ClaimController::class, 'index'])->name('index');
        Route::get('/create',        [\App\Http\Controllers\Partner\ClaimController::class, 'create'])->name('create');
        Route::post('/',             [\App\Http\Controllers\Partner\ClaimController::class, 'store'])->name('store');
        Route::get('/{claim}',       [\App\Http\Controllers\Partner\ClaimController::class, 'show'])->name('show');
    });

    // ─── Warehouses (My Warehouse module) ────────────────────────────────────────
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/',                        [WarehouseController::class, 'index'])->name('index');
        Route::get('/create',                  [WarehouseController::class, 'create'])->name('create');
        Route::post('/',                       [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}',             [WarehouseController::class, 'show'])->name('show');
        Route::put('/{warehouse}',             [WarehouseController::class, 'update'])->name('update');
        Route::post('/{warehouse}/deactivate', [WarehouseController::class, 'deactivate'])->name('deactivate');
        Route::post('/{warehouse}/inventory',  [WarehouseController::class, 'getInventory'])->name('inventory');

        Route::post('/inventory/{inventory}/adjust',   [WarehouseController::class, 'adjustInventory'])->name('inventory.adjust');
        Route::post('/inventory/{inventory}/count',    [WarehouseController::class, 'stockCount'])->name('inventory.count');
        Route::get('/inventory/{inventory}/movements', [WarehouseController::class, 'getMovements'])->name('inventory.movements');

        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/',                       [WarehouseController::class, 'transfersIndex'])->name('index');
            Route::post('/datatable',             [WarehouseController::class, 'transfersDatatable'])->name('datatable');
            Route::get('/create',                 [WarehouseController::class, 'transferCreate'])->name('create');
            Route::post('/',                      [WarehouseController::class, 'transferStore'])->name('store');
            Route::get('/{transfer}',             [WarehouseController::class, 'transferShow'])->name('show');
            Route::post('/{transfer}/ship',       [WarehouseController::class, 'transferShip'])->name('ship');
            Route::post('/{transfer}/cancel',     [WarehouseController::class, 'transferCancel'])->name('cancel');
        });
    });

    // ─── Delivery Ratings ─────────────────────────────────────────────────────
    Route::post('/orders/{subOrder}/rate-delivery', [\App\Http\Controllers\Partner\DeliveryRatingController::class, 'store'])
        ->name('orders.rate-delivery');

    // ─── Packaging Supplies ───────────────────────────────────────────────────
    Route::prefix('packaging-supplies')->name('packaging-supplies.')->group(function () {
        Route::get('/',                                           [\App\Http\Controllers\Partner\PackagingSupplyController::class, 'index'])->name('index');
        Route::get('/request',                                    [\App\Http\Controllers\Partner\PackagingSupplyController::class, 'request'])->name('request');
        Route::post('/request',                                   [\App\Http\Controllers\Partner\PackagingSupplyController::class, 'submitRequest'])->name('submit');
        Route::get('/my-requests',                                [\App\Http\Controllers\Partner\PackagingSupplyController::class, 'myRequests'])->name('my-requests');
        Route::get('/my-requests/{packagingSupplyRequest}',       [\App\Http\Controllers\Partner\PackagingSupplyController::class, 'showRequest'])->name('show-request');
    });
});
