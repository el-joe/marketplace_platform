<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductCostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\AdCampaignController;
use App\Http\Controllers\Admin\AdSlotController;
use App\Http\Controllers\Admin\PaidAdBookingController;
use App\Http\Controllers\Admin\VendorApplicationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\DeliveryAgentController;
use App\Http\Controllers\Admin\DeliveryZoneController;
use App\Http\Controllers\Admin\DeliveryAssignmentController;
use App\Http\Controllers\Admin\DeliveryPayoutController;
use App\Http\Controllers\Admin\MarketerController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\FbnController;
use App\Http\Controllers\Admin\SecretPromotionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Loaded inside: Route::domain('admin.*')->name('admin.')->group(...)
|--------------------------------------------------------------------------
*/

// ─── Auth: Guest routes (login) ─────────────────────────────────────────────────────
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

// ─── Locale switcher (public) ───────────────────────────────────────────────────
Route::post('/set-locale', function (Request $request) {
    $locale = $request->input('locale', 'en');
    if (in_array($locale, config('app.supported_locales', ['en', 'ar']), true)) {
        session(['locale' => $locale]);
    }
    return response()->json(['success' => true]);
})->name('set-locale');

// ─── All protected admin routes ───────────────────────────────────────────────────
Route::middleware('auth.admin')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ─── Dashboard ────────────────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->middleware('admin.permission:dashboard.view')->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware('admin.permission:dashboard.view')->name('dashboard.stats');
    Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->middleware('admin.permission:dashboard.view')->name('dashboard.revenue-chart');
    Route::get('/dashboard/orders-by-status', [DashboardController::class, 'ordersByStatus'])->middleware('admin.permission:dashboard.view')->name('dashboard.orders-by-status');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders'])->middleware('admin.permission:dashboard.view')->name('dashboard.recent-orders');
    Route::get('/dashboard/top-sellers', [DashboardController::class, 'topSellers'])->middleware('admin.permission:dashboard.view')->name('dashboard.top-sellers');
    Route::get('/dashboard/pending-items', [DashboardController::class, 'pendingItems'])->middleware('admin.permission:dashboard.view')->name('dashboard.pending-items');
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock'])->middleware('admin.permission:dashboard.view')->name('dashboard.low-stock');

    // ─── Products ─────────────────────────────────────────────────────────────────
    Route::prefix('products')->name('products.')->middleware('admin.permission:products.view')->group(function () {
        // Specific paths BEFORE the {product} wildcard
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/datatable', [ProductController::class, 'datatable'])->name('datatable');
        Route::post('/bulk', [ProductController::class, 'bulkAction'])->name('bulk');
        Route::post('/generate-variants', [ProductController::class, 'generateVariants'])->name('generate-variants');
        Route::post('/upload-image', [ProductController::class, 'uploadImage'])->name('upload-image');
        Route::get('/check-duplicate', [ProductController::class, 'checkDuplicate'])->name('check-duplicate');
        Route::get('/check-gtin', [ProductController::class, 'checkGtin'])->name('check-gtin');
        Route::delete('/delete-image/{mediaId}', [ProductController::class, 'deleteImage'])->name('delete-image');
        Route::post('/country-settings/{setting}', [ProductController::class, 'updateCountrySetting'])->name('update-country-setting');

        // CRUD
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::post('/{product}/reorder-images', [ProductController::class, 'reorderImages'])->name('reorder-images');
        Route::get('/{product}/country-settings', [ProductController::class, 'countrySettings'])->name('country-settings');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');

        // ── Cost Reference (requires elevated permission) ─────────────────────
        Route::prefix('/{product}/cost')->name('cost.')->group(function () {
            Route::get('/', [ProductCostController::class, 'show'])->name('show');
            Route::post('/', [ProductCostController::class, 'save'])->name('save');
            Route::post('/calculate', [ProductCostController::class, 'calculateMargin'])->name('calculate');
            Route::post('/check-competitors', [ProductCostController::class, 'checkCompetitorPrices'])->name('check-competitors');
        });
    });

    // ─── Brands ──────────────────────────────────────────────────────────────────
    Route::prefix('brands')->name('brands.')->middleware('admin.permission:brands.view')->group(function () {
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/datatable', [BrandController::class, 'datatable'])->name('datatable');
        Route::get('/search', [BrandController::class, 'search'])->name('search');
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/{brand}/edit', [BrandController::class, 'edit'])->name('edit');
        Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
    });

    // ─── Notifications ────────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {

        Route::get('/unread-count', function () {
            $adminId = auth('admin')->id();
            if (!$adminId) {
                return response()->json(['data' => ['count' => 0]]);
            }
            $count = DB::table('notifications')
                ->where('notifiable_type', \App\Models\Admin::class)
                ->where('notifiable_id', $adminId)
                ->whereNull('read_at')
                ->count();
            return response()->json(['data' => ['count' => $count]]);
        })->name('unread-count');

        Route::get('/unread', function () {
            $adminId = auth('admin')->id();
            $items = DB::table('notifications')
                ->where('notifiable_type', \App\Models\Admin::class)
                ->where('notifiable_id', $adminId)
                ->whereNull('read_at')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(function ($n) {
                    $data = is_string($n->data) ? json_decode($n->data, true) : (array) $n->data;
                    return [
                        'id' => $n->id,
                        'title' => $data['title'] ?? 'Notification',
                        'message' => $data['message'] ?? '',
                        'url' => $data['url'] ?? '#',
                        'created_at' => $n->created_at,
                    ];
                });
            return response()->json(['data' => ['items' => $items]]);
        })->name('unread');

        Route::post('/mark-all-read', function () {
            $adminId = auth('admin')->id();
            DB::table('notifications')
                ->where('notifiable_type', \App\Models\Admin::class)
                ->where('notifiable_id', $adminId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            return response()->json(['success' => true]);
        })->name('mark-all-read');
    });


    // ─── Categories (CRUD) ────────────────────────────────────────────────────────
    Route::prefix('categories')->name('categories.')->middleware('admin.permission:categories.view')->group(function () {
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
        Route::post('/bulk-commission', [CategoryController::class, 'bulkCommission'])->name('bulk-commission');
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/toggle-featured', [CategoryController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{category}/sync-attributes', [CategoryController::class, 'syncAttributes'])->name('sync-attributes');


        Route::get('/search', function (Request $request) {
            $term = trim($request->input('q', ''));
            $results = DB::table('categories')
                ->where('is_active', true)
                ->where(function ($q) use ($term) {
                    $q->where('name_en', 'like', "%{$term}%")
                        ->orWhere('name_ar', 'like', "%{$term}%");
                })
                ->limit(30)
                ->get(['id', 'name_en as text']);
            return response()->json(['results' => $results]);
        })->name('search');

        Route::get('/{id}/attributes', function (string $id) {
            $attrs = DB::table('category_attributes as ca')
                ->join('attributes as a', 'a.id', '=', 'ca.attribute_id')
                ->where('ca.category_id', $id)
                ->where('a.is_variant_attribute', true)
                ->select('a.id', 'a.name_en')
                ->orderBy('a.sort_order')
                ->get();
            return response()->json(['data' => $attrs]);
        })->name('attributes');

    });

    // ─── Attributes (CRUD) ────────────────────────────────────────────────────────
    Route::prefix('attributes')->name('attributes.')->middleware('admin.permission:attributes.view')->group(function () {
        Route::get('/create', [AttributeController::class, 'create'])->name('create');
        Route::post('/datatable', [AttributeController::class, 'datatable'])->name('datatable');
        Route::get('/', [AttributeController::class, 'index'])->name('index');
        Route::post('/', [AttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}/edit', [AttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [AttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [AttributeController::class, 'destroy'])->name('destroy');
        Route::post('/{attribute}/values', [AttributeController::class, 'storeValue'])->name('values.store');
        Route::put('/{attribute}/values/{value}', [AttributeController::class, 'updateValue'])->name('values.update');
        Route::delete('/{attribute}/values/{value}', [AttributeController::class, 'destroyValue'])->name('values.destroy');
        Route::post('/{attribute}/values/reorder', [AttributeController::class, 'reorderValues'])->name('values.reorder');
    });

    Route::post('/country', function (Request $request) {
        $code = strtoupper(trim($request->input('country', '')));
        if ($code && preg_match('/^[A-Z]{2,3}$/', $code)) {
            session(['admin_country' => $code]);
        }
        return response()->json(['success' => true]);
    })->name('country');

    // ─── Placeholders ─────────────────────────────────────────────────────────────
// ─── Orders ───────────────────────────────────────────────────────────────────

    Route::prefix('orders')->name('orders.')->middleware('admin.permission:orders.view')->group(function () {
        Route::post('/datatable', [OrderController::class, 'datatable'])->name('datatable');
        Route::post('/update-sub-order-status', [OrderController::class, 'updateSubOrderStatus'])->name('update-sub-order-status');
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::post('/{id}/force-cancel', [OrderController::class, 'forceCancel'])->name('force-cancel');
        Route::post('/{id}/refund', [OrderController::class, 'processRefund'])->name('refund');
        Route::post('/{id}/dispute', [OrderController::class, 'escalateDispute'])->name('dispute');
        Route::post('/{id}/flag-fraud', [OrderController::class, 'flagFraud'])->name('flag-fraud');
    });
    Route::get('/sub-orders/{id}/next-statuses', [OrderController::class, 'nextStatuses'])->middleware('admin.permission:orders.view')->name('sub-orders.next-statuses');
    // ─── Payouts ──────────────────────────────────────────────────────────────────

    Route::prefix('payouts')->name('payouts.')->middleware('admin.permission:payouts.view')->group(function () {
        Route::get('/', [PayoutController::class, 'index'])->name('index');
        Route::post('/datatable', [PayoutController::class, 'datatable'])->name('datatable');
        Route::get('/{payout}', [PayoutController::class, 'show'])->name('show');
        Route::post('/{payout}/approve', [PayoutController::class, 'approve'])->name('approve');
        Route::post('/{payout}/process', [PayoutController::class, 'process'])->name('process');
        Route::post('/{payout}/hold', [PayoutController::class, 'hold'])->name('hold');
        Route::post('/{payout}/recalculate', [PayoutController::class, 'recalculate'])->name('recalculate');
    });

    // ─── Flash Sales ──────────────────────────────────────────────────────────────

    Route::prefix('flash-sales')->name('flash-sales.')->middleware('admin.permission:flash_sales.view')->group(function () {

        // List + create
        Route::get('/', [FlashSaleController::class, 'index'])->name('index');
        Route::post('/datatable', [FlashSaleController::class, 'datatable'])->name('datatable');
        Route::get('/create', [FlashSaleController::class, 'create'])->name('create')
            ->middleware('admin.permission:flash_sales.create');
        Route::post('/', [FlashSaleController::class, 'store'])->name('store')
            ->middleware('admin.permission:flash_sales.create');

        // Misc (before /{flashSale} wildcard)
        Route::get('/price-history', [FlashSaleController::class, 'priceHistory'])->name('price-history');

        // Submission review (before /{flashSale} wildcard)
        Route::post('/submissions/{submission}/review', [FlashSaleController::class, 'reviewSubmission'])
            ->name('submissions.review')
            ->middleware('admin.permission:flash_sales.review_submissions');

        Route::get('/submissions/{submission}/detail', [FlashSaleController::class, 'submissionDetail'])
            ->name('submissions.detail')
            ->middleware('admin.permission:flash_sales.review_submissions');

        // Per-sale routes
        Route::prefix('/{flashSale}')->group(function () {

            Route::get('/', [FlashSaleController::class, 'show'])->name('show');
            Route::get('/edit', [FlashSaleController::class, 'edit'])->name('edit')
                ->middleware('admin.permission:flash_sales.edit');
            Route::put('/', [FlashSaleController::class, 'update'])->name('update')
                ->middleware('admin.permission:flash_sales.edit');
            Route::delete('/', [FlashSaleController::class, 'destroy'])->name('destroy')
                ->middleware('admin.permission:flash_sales.edit');

            Route::post('/transition', [FlashSaleController::class, 'transition'])->name('transition');
            Route::get('/eligible-vendor-count', [FlashSaleController::class, 'eligibleVendorCount'])->name('eligible-vendor-count');
            Route::post('/invite-vendors', [FlashSaleController::class, 'inviteVendors'])->name('invite-vendors');
            Route::post('/invitations/datatable', [FlashSaleController::class, 'invitationsDatatable'])->name('invitations.datatable');

            Route::get('/submission-stats', [FlashSaleController::class, 'submissionStats'])->name('submission-stats');
            Route::post('/submissions/datatable', [FlashSaleController::class, 'submissionsDatatable'])->name('submissions.datatable');
            Route::post('/bulk-review', [FlashSaleController::class, 'bulkReviewSubmissions'])->name('submissions.bulk-review')
                ->middleware('admin.permission:flash_sales.review_submissions');

            Route::get('/live-data', [FlashSaleController::class, 'liveMonitorData'])->name('live-data');
            Route::get('/analytics-data', [FlashSaleController::class, 'analyticsData'])->name('analytics-data');
        });
    });

    // ─── Page Builder ──────────────────────────────────────────────────────────────

    Route::prefix('page-builder')->name('page-builder.')->middleware('admin.permission:pages.view')->group(function () {

        // Builder UI
        Route::get('/', [PageBuilderController::class, 'index'])->name('index');
        Route::get('/load', [PageBuilderController::class, 'loadPage'])->name('load');

        // Pages
        Route::post('/pages', [PageBuilderController::class, 'createPage'])->name('pages.create');
        Route::put('/pages/{page}', [PageBuilderController::class, 'updatePage'])->name('pages.update');
        Route::delete('/pages/{page}', [PageBuilderController::class, 'deletePage'])->name('pages.delete');
        Route::post('/pages/{page}/duplicate', [PageBuilderController::class, 'duplicatePage'])->name('pages.duplicate');
        Route::post('/pages/{page}/publish', [PageBuilderController::class, 'publishPage'])->name('pages.publish');
        Route::get('/pages/{page}/revisions', [PageBuilderController::class, 'getPageRevisions'])->name('pages.revisions');
        Route::post('/page-revisions/{revision}/restore', [PageBuilderController::class, 'restorePageRevision'])->name('page-revisions.restore');

        // Blocks
        Route::post('/blocks', [PageBuilderController::class, 'addBlock'])->name('blocks.add');
        Route::get('/blocks/{block}/config', [PageBuilderController::class, 'getBlockConfig'])->name('blocks.get-config');
        Route::post('/blocks/{block}/config', [PageBuilderController::class, 'updateBlockConfig'])->name('blocks.config');
        Route::post('/blocks/{block}/visibility', [PageBuilderController::class, 'updateBlockVisibility'])->name('blocks.visibility');
        Route::delete('/blocks/{block}', [PageBuilderController::class, 'removeBlock'])->name('blocks.remove');
        Route::post('/reorder', [PageBuilderController::class, 'reorderBlocks'])->name('reorder');

        // Block revisions
        Route::get('/blocks/{block}/revisions', [PageBuilderController::class, 'getRevisions'])->name('blocks.revisions');
        Route::post('/revisions/{revision}/restore', [PageBuilderController::class, 'restoreBlockRevision'])->name('revisions.restore');

        // Config form partials
        Route::get('/config-form', [PageBuilderController::class, 'configFormPartial'])->name('config-form');

        // Slides
        Route::get('/blocks/{block}/slides', [PageBuilderController::class, 'getSlides'])->name('slides.list');
        Route::post('/blocks/{block}/slides', [PageBuilderController::class, 'saveSlide'])->name('slides.save');
        Route::delete('/slides/{slide}', [PageBuilderController::class, 'deleteSlide'])->name('slides.delete');
        Route::post('/blocks/{block}/slides/reorder', [PageBuilderController::class, 'reorderSlides'])->name('slides.reorder');

        // Ad images
        Route::get('/blocks/{block}/ad-images', [PageBuilderController::class, 'getAdImages'])->name('ad-images.list');
        Route::post('/blocks/{block}/ad-images', [PageBuilderController::class, 'saveAdImage'])->name('ad-images.save');
        Route::delete('/ad-images/{adImage}', [PageBuilderController::class, 'deleteAdImage'])->name('ad-images.delete');
        Route::post('/blocks/{block}/ad-images/reorder', [PageBuilderController::class, 'reorderAdImages'])->name('ad-images.reorder');

        // Search (for manual selectors)
        Route::get('/search/products', [PageBuilderController::class, 'searchProducts'])->name('search.products');
        Route::get('/search/categories', [PageBuilderController::class, 'searchCategories'])->name('search.categories');
        Route::get('/search/brands', [PageBuilderController::class, 'searchBrands'])->name('search.brands');
        Route::get('/search/vendors', [PageBuilderController::class, 'searchVendors'])->name('search.vendors');
        Route::get('/search/flash-sales', [PageBuilderController::class, 'searchFlashSales'])->name('search.flash-sales');

        // Block product pickers
        Route::post('/blocks/{block}/products', [PageBuilderController::class, 'addBlockProduct'])->name('products.add');
        Route::delete('/block-products/{blockProduct}', [PageBuilderController::class, 'removeBlockProduct'])->name('products.remove');
        Route::post('/blocks/{block}/products/reorder', [PageBuilderController::class, 'reorderBlockProducts'])->name('products.reorder');
    });

    // ─── Vendors ─────────────────────────────────────────────────────────────────

    Route::prefix('vendors')->name('vendors.')->middleware('admin.permission:vendors.view')->group(function () {
        Route::get('/applications', [VendorController::class, 'applicationQueue'])->name('applications');
        Route::post('/datatable', [VendorController::class, 'datatable'])->name('datatable');
        Route::post('/bulk', [VendorController::class, 'bulkAction'])->name('bulk');

        Route::post('/documents/{document}/verify', [VendorController::class, 'verifyDocument'])->name('documents.verify');
        Route::post('/documents/{document}/reject', [VendorController::class, 'rejectDocument'])->name('documents.reject');

        Route::get('/', [VendorController::class, 'index'])->name('index');
        Route::get('/{vendor}', [VendorController::class, 'show'])->name('show');
        Route::put('/{vendor}', [VendorController::class, 'update'])->name('update');

        Route::post('/{vendor}/approve', [VendorController::class, 'approve'])->name('approve');
        Route::post('/{vendor}/reject', [VendorController::class, 'reject'])->name('reject');
        Route::post('/{vendor}/request-info', [VendorController::class, 'requestInfo'])->name('request-info');
        Route::post('/{vendor}/suspend', [VendorController::class, 'suspend'])->name('suspend');
        Route::post('/{vendor}/reactivate', [VendorController::class, 'reactivate'])->name('reactivate');
        Route::post('/{vendor}/blacklist', [VendorController::class, 'blacklist'])->name('blacklist');
        Route::post('/{vendor}/strikes', [VendorController::class, 'issueStrike'])->name('strikes.store');
        Route::post('/{vendor}/hold', [VendorController::class, 'placeHold'])->name('hold.place');
        Route::post('/{vendor}/release-hold', [VendorController::class, 'releaseHold'])->name('hold.release');
        Route::post('/{vendor}/assign-manager', [VendorController::class, 'assignManager'])->name('assign-manager');
        Route::get('/{vendor}/documents', [VendorController::class, 'documents'])->name('documents.index');
        Route::post('/{vendor}/bank-accounts/{accountId}/verify', [VendorController::class, 'verifyBankAccount'])->name('bank-accounts.verify');
        Route::get('/{vendor}/performance-data', [VendorController::class, 'performanceData'])->name('performance-data');
        Route::post('/{vendor}/notify', [VendorController::class, 'sendNotification'])->name('notify');
    });

    // ─── Geography ───────────────────────────────────────────────────────────────

    Route::prefix('countries')->name('countries.')->middleware('admin.permission:countries.view')->group(function () {
        Route::post('/datatable', [CountryController::class, 'datatable'])->name('datatable');
        Route::get('/create', [CountryController::class, 'create'])->name('create');
        Route::post('/', [CountryController::class, 'store'])->name('store');
        Route::get('/', [CountryController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [CountryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CountryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CountryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/launch', [CountryController::class, 'launch'])->name('launch');
        Route::post('/{id}/deactivate', [CountryController::class, 'deactivate'])->name('deactivate');
        Route::post('/{id}/reactivate', [CountryController::class, 'reactivate'])->name('reactivate');
        // Payment Methods sub-resource
        Route::post('/{id}/payment-methods', [CountryController::class, 'storePaymentMethod'])->name('payment-methods.store');
        Route::put('/{id}/payment-methods/{pmId}', [CountryController::class, 'updatePaymentMethod'])->name('payment-methods.update');
        Route::delete('/{id}/payment-methods/{pmId}', [CountryController::class, 'destroyPaymentMethod'])->name('payment-methods.destroy');
        // Shipping Settings
        Route::post('/{id}/shipping-settings', [CountryController::class, 'updateShippingSettings'])->name('shipping-settings.update');
        // Category Overrides
        Route::post('/{id}/categories/datatable', [CountryController::class, 'categoryOverridesDatatable'])->name('categories.datatable');
        Route::post('/{id}/category-overrides', [CountryController::class, 'updateCategoryOverrides'])->name('category-overrides.update');
    });

    // ─── Cities ─────────────────────────────────────────────────────────────────────────────
    Route::prefix('cities')->name('cities.')->middleware('admin.permission:countries.view')->group(function () {
        Route::post('/datatable', [CityController::class, 'datatable'])->name('datatable');
        Route::post('/bulk-import', [CityController::class, 'bulkImport'])->name('bulk-import');
        Route::get('/create', [CityController::class, 'create'])->name('create');
        Route::post('/', [CityController::class, 'store'])->name('store');
        Route::get('/', [CityController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [CityController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CityController::class, 'update'])->name('update');
        Route::delete('/{id}', [CityController::class, 'destroy'])->name('destroy');
    });

    // ─── Currencies ────────────────────────────────────────────────────────────────────────────
    Route::prefix('currencies')->name('currencies.')->middleware('admin.permission:countries.view')->group(function () {
        Route::get('/', [CurrencyController::class, 'index'])->name('index');
        Route::get('/rates-table', [CurrencyController::class, 'ratesTable'])->name('rates-table');
        Route::post('/dispatch-update', [CurrencyController::class, 'dispatchUpdate'])->name('dispatch-update');
        Route::post('/refresh-rates', [CurrencyController::class, 'refreshRates'])->name('refresh-rates');
        Route::get('/{code}/edit', [CurrencyController::class, 'edit'])->name('edit');
        Route::put('/{code}', [CurrencyController::class, 'update'])->name('update');
        Route::patch('/{code}/rate', [CurrencyController::class, 'updateRate'])->name('update-rate');
    });

    // ─── Coupons ─────────────────────────────────────────────────────────────────
    Route::prefix('coupons')->name('coupons.')->middleware('admin.permission:coupons.view')->group(function () {
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::get('/generate-code', [CouponController::class, 'generateCode'])->name('generate-code');
        Route::post('/datatable', [CouponController::class, 'datatable'])->name('datatable');
        Route::post('/bulk', [CouponController::class, 'bulkAction'])->name('bulk');
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}/usages', [CouponController::class, 'usages'])->name('usages');
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
    });

    // ─── Support Tickets ──────────────────────────────────────────────────────────
    Route::prefix('support-tickets')->name('support-tickets.')->middleware('admin.permission:support.view')->group(function () {
        Route::post('/datatable', [SupportTicketController::class, 'datatable'])->name('datatable');
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/assign-me', [SupportTicketController::class, 'assignMe'])->name('assign-me');
        Route::post('/{ticket}/update-status', [SupportTicketController::class, 'updateStatus'])->name('update-status');
        Route::post('/{ticket}/update-priority', [SupportTicketController::class, 'updatePriority'])->name('update-priority');
    });

    // ─── Disputes ─────────────────────────────────────────────────────────────────
    Route::prefix('disputes')->name('disputes.')->middleware('admin.permission:disputes.view')->group(function () {
        Route::post('/datatable', [DisputeController::class, 'datatable'])->name('datatable');
        Route::get('/', [DisputeController::class, 'index'])->name('index');
        Route::get('/{dispute}', [DisputeController::class, 'show'])->name('show');
        Route::post('/{dispute}/reply', [DisputeController::class, 'reply'])->name('reply');
        Route::post('/{dispute}/assign', [DisputeController::class, 'assign'])->name('assign');
        Route::post('/{dispute}/assign-me', [DisputeController::class, 'assignMe'])->name('assign-me');
        Route::post('/{dispute}/update-status', [DisputeController::class, 'updateStatus'])->name('update-status');
        Route::post('/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('resolve');
    });

    // ─── Stop Impersonating (no extra permission required, just auth) ─────────────
    Route::post('/admins/stop-impersonating', [AdminController::class, 'stopImpersonating'])
        ->name('admins.stop-impersonating');

    // ─── Admins ───────────────────────────────────────────────────────────────────
    Route::prefix('admins')->name('admins.')->middleware('admin.permission:admins.view')->group(function () {
        Route::get('/search', [AdminController::class, 'search'])->name('search');
        Route::get('/create', [AdminController::class, 'create'])->name('create');
        Route::post('/datatable', [AdminController::class, 'datatable'])->name('datatable');
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::get('/{admin}/login-sessions', [AdminController::class, 'loginSessions'])->name('login-sessions');
        Route::post('/{admin}/reset-password', [AdminController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{admin}/impersonate', [AdminController::class, 'impersonate'])->name('impersonate');
        Route::post('/{admin}/toggle-status', [AdminController::class, 'toggleStatus'])->name('toggle-status');
        Route::put('/{admin}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('destroy');
    });

    // ─── Roles ────────────────────────────────────────────────────────────────────
    Route::prefix('roles')->name('roles.')->middleware('admin.permission:roles.view')->group(function () {
        Route::get('/permissions', [RoleController::class, 'permissions'])->name('permissions');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/datatable', [RoleController::class, 'datatable'])->name('datatable');
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });


    // ─── Customers ───────────────────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->middleware('admin.permission:customers.view')->group(function () {
        Route::post('/datatable', [CustomerController::class, 'datatable'])->name('datatable');
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/{customer}/export', [CustomerController::class, 'exportData'])->name('export');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::post('/{customer}/suspend', [CustomerController::class, 'suspend'])->name('suspend');
        Route::post('/{customer}/ban', [CustomerController::class, 'ban'])->name('ban');
        Route::post('/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('reactivate');
        Route::post('/{customer}/adjust-loyalty', [CustomerController::class, 'adjustLoyaltyPoints'])->name('adjust-loyalty');
        Route::post('/{customer}/orders/datatable', [CustomerController::class, 'orders'])->name('orders.datatable');
        Route::post('/{customer}/send-notification', [CustomerController::class, 'sendNotification'])->name('send-notification');
    });

    // ─── Banners ──────────────────────────────────────────────────────────────────
    Route::prefix('banners')->name('banners.')->middleware('admin.permission:banners.view')->group(function () {
        Route::post('/datatable', [BannerController::class, 'datatable'])->name('datatable');
        Route::get('/placements', [BannerController::class, 'placements'])->name('placements');
        Route::get('/create', [BannerController::class, 'create'])->name('create');
        Route::post('/', [BannerController::class, 'store'])->name('store');
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::get('/{banner}/edit', [BannerController::class, 'edit'])->name('edit');
        Route::put('/{banner}', [BannerController::class, 'update'])->name('update');
        Route::delete('/{banner}', [BannerController::class, 'destroy'])->name('destroy');
        Route::post('/{banner}/duplicate', [BannerController::class, 'duplicate'])->name('duplicate');
        Route::post('/{banner}/upload-image', [BannerController::class, 'uploadImage'])->name('upload-image');
        Route::delete('/image', [BannerController::class, 'deleteImage'])->name('delete-image');
        Route::post('/bulk', [BannerController::class, 'bulk'])->name('bulk');
    });

    // ─── Ad Campaigns ──────────────────────────────────────────────────────────────
    Route::prefix('ad-campaigns')->name('ad-campaigns.')->middleware('admin.permission:ad_campaigns.view')->group(function () {
        Route::post('/datatable', [AdCampaignController::class, 'datatable'])->name('datatable');
        Route::get('/fraud', [AdCampaignController::class, 'fraudAlerts'])->name('fraud');
        Route::post('/fraud/datatable', [AdCampaignController::class, 'fraudDatatable'])->name('fraud.datatable');
        Route::post('/fraud/{pattern}/block', [AdCampaignController::class, 'blockFraudPattern'])->name('fraud.block');
        Route::get('/', [AdCampaignController::class, 'index'])->name('index');
        Route::get('/{campaign}', [AdCampaignController::class, 'show'])->name('show');
        Route::post('/{campaign}/approve', [AdCampaignController::class, 'approve'])->name('approve');
        Route::post('/{campaign}/reject', [AdCampaignController::class, 'reject'])->name('reject');
        Route::post('/{campaign}/pause', [AdCampaignController::class, 'pauseCampaign'])->name('pause');
        Route::post('/{campaign}/resume', [AdCampaignController::class, 'resumeCampaign'])->name('resume');
    });

    // ─── Ad Slots ──────────────────────────────────────────────────────────────────
    Route::prefix('ad-slots')->name('ad-slots.')->middleware('admin.permission:ad_campaigns.view')->group(function () {
        Route::post('/datatable', [AdSlotController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdSlotController::class, 'create'])->name('create');
        Route::post('/', [AdSlotController::class, 'store'])->name('store');
        Route::get('/', [AdSlotController::class, 'index'])->name('index');
        Route::get('/{adSlot}/bookings', [AdSlotController::class, 'bookings'])->name('bookings');
        Route::get('/{adSlot}/edit', [AdSlotController::class, 'edit'])->name('edit');
        Route::put('/{adSlot}', [AdSlotController::class, 'update'])->name('update');
    });

    // ─── Paid Ad Bookings ──────────────────────────────────────────────────────────
    Route::prefix('paid-ad-bookings')->name('paid-ad-bookings.')->middleware('admin.permission:ad_campaigns.view')->group(function () {
        Route::post('/datatable', [PaidAdBookingController::class, 'datatable'])->name('datatable');
        Route::post('/creatives/{paidAdCreative}/review', [PaidAdBookingController::class, 'reviewCreative'])->name('creatives.review');
        Route::get('/', [PaidAdBookingController::class, 'index'])->name('index');
        Route::get('/{paidAdBooking}', [PaidAdBookingController::class, 'show'])->name('show');
        Route::post('/{paidAdBooking}/approve', [PaidAdBookingController::class, 'approve'])->name('approve');
        Route::post('/{paidAdBooking}/reject', [PaidAdBookingController::class, 'reject'])->name('reject');
    });

    // ─── Vendor Applications Queue ────────────────────────────────────────────────
    Route::prefix('vendor-applications')->name('vendor-applications.')->middleware('admin.permission:vendors.view')->group(function () {
        Route::post('/datatable', [VendorApplicationController::class, 'datatable'])->name('datatable');
        Route::post('/documents/{document}/verify', [VendorApplicationController::class, 'verifyDocument'])->name('documents.verify');
        Route::post('/documents/{document}/reject', [VendorApplicationController::class, 'rejectDocument'])->name('documents.reject');
        Route::get('/', [VendorApplicationController::class, 'index'])->name('index');
        Route::get('/{vendor}', [VendorApplicationController::class, 'show'])->name('show');
        Route::post('/{vendor}/start-review', [VendorApplicationController::class, 'startReview'])->name('start-review');
        Route::post('/{vendor}/assign-me', [VendorApplicationController::class, 'assignMe'])->name('assign-me');
        Route::post('/{vendor}/approve', [VendorApplicationController::class, 'approve'])->name('approve');
        Route::post('/{vendor}/reject', [VendorApplicationController::class, 'reject'])->name('reject');
        Route::post('/{vendor}/request-info', [VendorApplicationController::class, 'requestMoreInfo'])->name('request-info');
    });

    // ─── Reviews ──────────────────────────────────────────────────────────────────
    Route::prefix('reviews')->name('reviews.')->middleware('admin.permission:reviews.view')->group(function () {
        Route::post('/datatable', [ReviewController::class, 'datatable'])->name('datatable');
        Route::post('/bulk-action', [ReviewController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/vendor-replies/{reply}/hide', [ReviewController::class, 'hideVendorReply'])->name('vendor-replies.hide');
        Route::post('/vendor-replies/{reply}/show', [ReviewController::class, 'showVendorReply'])->name('vendor-replies.show');
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
        Route::post('/{review}/reject', [ReviewController::class, 'reject'])->name('reject');
        Route::delete('/{review}', [ReviewController::class, 'delete'])->name('delete');
    });

    // ─── Transactions & Finance ───────────────────────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->middleware('admin.permission:transactions.view')->group(function () {
        Route::post('/datatable', [TransactionController::class, 'datatable'])->name('datatable');
        // Refund sub-routes BEFORE the /{transaction} wildcard
        Route::get('/refunds', [TransactionController::class, 'refundIndex'])->name('refunds.index');
        Route::post('/refunds/datatable', [TransactionController::class, 'refundDatatable'])->name('refunds.datatable');
        Route::post('/refunds/{refund}/approve', [TransactionController::class, 'approveRefund'])->name('refunds.approve');
        Route::post('/refunds/{refund}/reject', [TransactionController::class, 'rejectRefund'])->name('refunds.reject');
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
    });

    // ─── Ledger ───────────────────────────────────────────────────────────────────
    Route::prefix('ledger')->name('ledger.')->middleware('admin.permission:ledger.view')->group(function () {
        Route::post('/datatable', [LedgerController::class, 'datatable'])->name('datatable');
        Route::get('/transaction-group/{groupId}', [LedgerController::class, 'transactionGroup'])->name('transaction-group');
        Route::get('/', [LedgerController::class, 'index'])->name('index');
    });

    // ─── Settings ─────────────────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->middleware('admin.permission:settings.view')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/group/{category}', [SettingsController::class, 'getGroup'])->name('group');
        Route::post('/group/{category}', [SettingsController::class, 'saveGroup'])->name('save')->middleware('admin.permission:settings.edit');
        Route::post('/reset', [SettingsController::class, 'reset'])->name('reset')->middleware('admin.permission:settings.edit');
        Route::post('/test-gateway', [SettingsController::class, 'testGateway'])->name('test-gateway');
        Route::post('/clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache')->middleware('admin.permission:settings.edit');
    });

    // ─── Activity Log ─────────────────────────────────────────────────────────
    Route::prefix('activity-log')->name('activity-log.')->middleware('admin.permission:activity-log.view')->group(function () {
        Route::post('/datatable', [ActivityLogController::class, 'datatable'])->name('datatable');
        Route::get('/causer-search', [ActivityLogController::class, 'causerSearch'])->name('causer-search');
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
    });

    // ─── Shipping Zones ───────────────────────────────────────────────────────
    Route::prefix('shipping-zones')->name('shipping-zones.')->middleware('admin.permission:settings.view')->group(function () {
        // Zone index + datatable
        Route::get('/', [ShippingZoneController::class, 'index'])->name('index');
        Route::post('/datatable', [ShippingZoneController::class, 'datatable'])->name('datatable');

        // Rates endpoints (specific routes BEFORE /{zone} wildcard)
        Route::post('/rates/datatable', [ShippingZoneController::class, 'getRates'])->name('rates.datatable');
        Route::post('/rates/estimate', [ShippingZoneController::class, 'calculateEstimate'])->name('rates.estimate');
        Route::middleware('admin.permission:settings.edit')->group(function () {
            Route::post('/rates/bulk', [ShippingZoneController::class, 'bulkRates'])->name('rates.bulk');
            Route::post('/rates/copy', [ShippingZoneController::class, 'copyRates'])->name('rates.copy');
            Route::post('/rates', [ShippingZoneController::class, 'storeRate'])->name('rates.store');
            Route::put('/rates/{rate}', [ShippingZoneController::class, 'updateRate'])->name('rates.update');
            Route::delete('/rates/{rate}', [ShippingZoneController::class, 'destroyRate'])->name('rates.destroy');
            Route::post('/rates/{rate}/toggle', [ShippingZoneController::class, 'toggleRate'])->name('rates.toggle');
        });

        // City endpoints (specific before /{zone} wildcard)
        Route::get('/cities/unassigned', [ShippingZoneController::class, 'getUnassigned'])->name('cities.unassigned');
        Route::post('/cities/unassign', [ShippingZoneController::class, 'unassignCity'])->name('cities.unassign')
            ->middleware('admin.permission:settings.edit');

        // Zone show
        Route::get('/{zone}', [ShippingZoneController::class, 'show'])->name('show');

        // Zone CRUD (write operations)
        Route::middleware('admin.permission:settings.edit')->group(function () {
            Route::post('/', [ShippingZoneController::class, 'store'])->name('store');
            Route::put('/{zone}', [ShippingZoneController::class, 'update'])->name('update');
            Route::delete('/{zone}', [ShippingZoneController::class, 'destroy'])->name('destroy');
            Route::post('/{zone}/toggle', [ShippingZoneController::class, 'toggleActive'])->name('toggle');
            Route::post('/{zone}/duplicate', [ShippingZoneController::class, 'duplicate'])->name('duplicate');
        });

        // City assignment per zone
        Route::get('/{zone}/cities', [ShippingZoneController::class, 'getCities'])->name('cities');
        Route::post('/{zone}/cities', [ShippingZoneController::class, 'assignCities'])->name('cities.assign')
            ->middleware('admin.permission:settings.edit');
    });

    // ─── Warehouses ───────────────────────────────────────────────────────────
    Route::prefix('warehouses')->name('warehouses.')->middleware('admin.permission:warehouses.view')->group(function () {
        // Index + datatable
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::post('/datatable', [WarehouseController::class, 'datatable'])->name('datatable');

        // Create / Store
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');

        // Transfers (must be before /{warehouse} so 'transfers' is not treated as a UUID)
        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/', [WarehouseController::class, 'transfersIndex'])->name('index');
            Route::post('/datatable', [WarehouseController::class, 'transfersDatatable'])->name('datatable');
            Route::get('/create', [WarehouseController::class, 'transferCreate'])->name('create');
            Route::post('/', [WarehouseController::class, 'transferStore'])->name('store');
            Route::get('/{transfer}', [WarehouseController::class, 'transferShow'])->name('show');
            Route::post('/{transfer}/ship', [WarehouseController::class, 'transferShip'])->name('ship');
            Route::post('/{transfer}/receive', [WarehouseController::class, 'transferReceive'])->name('receive');
            Route::post('/{transfer}/cancel', [WarehouseController::class, 'transferCancel'])->name('cancel');
        });

        // Single warehouse
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::post('/{warehouse}/toggle-active', [WarehouseController::class, 'toggleActive'])->name('toggle-active');

        // Inventory endpoints
        Route::post('/{warehouse}/inventory/datatable', [WarehouseController::class, 'inventoryDatatable'])->name('inventory.datatable');
        Route::post('/{warehouse}/inventory/{inventory}/adjust', [WarehouseController::class, 'adjustInventory'])->name('inventory.adjust');
        Route::get('/{warehouse}/inventory/{inventory}/movements', [WarehouseController::class, 'movements'])->name('inventory.movements');
    });

    // ─── Analytics ───────────────────────────────────────────────────────────────
    Route::prefix('analytics')->name('analytics.')->middleware('admin.permission:analytics.view')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
        Route::get('/revenue-chart', [AnalyticsController::class, 'revenueChart'])->name('revenue-chart');
        Route::get('/orders-by-status', [AnalyticsController::class, 'ordersByStatus'])->name('orders-by-status');
        Route::get('/orders-by-payment', [AnalyticsController::class, 'ordersByPaymentMethod'])->name('orders-by-payment');
        Route::get('/top-products', [AnalyticsController::class, 'topProducts'])->name('top-products');
        Route::get('/top-vendors', [AnalyticsController::class, 'topVendors'])->name('top-vendors');
        Route::get('/top-categories', [AnalyticsController::class, 'topCategories'])->name('top-categories');
        Route::get('/customers', [AnalyticsController::class, 'customerStats'])->name('customers');
        Route::get('/search', [AnalyticsController::class, 'searchAnalytics'])->name('search');
        Route::get('/products', [AnalyticsController::class, 'productAnalytics'])->name('products');
        Route::get('/sla', [AnalyticsController::class, 'slaMetrics'])->name('sla');
        Route::get('/ads', [AnalyticsController::class, 'adPerformance'])->name('ads');
        Route::get('/flash-sales', [AnalyticsController::class, 'flashSaleAnalytics'])->name('flash-sales');
        Route::get('/returns', [AnalyticsController::class, 'returnAnalytics'])->name('returns');
        Route::get('/support', [AnalyticsController::class, 'supportMetrics'])->name('support');
    });

    // ─── Payment Methods ──────────────────────────────────────────────────────
    Route::prefix('payment-methods')->name('payment-methods.')->middleware('admin.permission:settings.view')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::get('/gateway-config', [PaymentMethodController::class, 'gatewayConfig'])->name('gateway-config');
        Route::post('/test-gateway', [PaymentMethodController::class, 'testGateway'])->name('test-gateway')->middleware('admin.permission:settings.edit');
        Route::post('/sort-order', [PaymentMethodController::class, 'updateSortOrder'])->name('sort-order')->middleware('admin.permission:settings.edit');
        Route::post('/', [PaymentMethodController::class, 'store'])->name('store')->middleware('admin.permission:settings.edit');
        Route::put('/{method}', [PaymentMethodController::class, 'update'])->name('update')->middleware('admin.permission:settings.edit');
        Route::delete('/{method}', [PaymentMethodController::class, 'destroy'])->name('destroy')->middleware('admin.permission:settings.edit');
        Route::post('/{method}/toggle', [PaymentMethodController::class, 'toggleActive'])->name('toggle')->middleware('admin.permission:settings.edit');
    });

    // ─── Shipping Methods ─────────────────────────────────────────────────────
    Route::prefix('shipping-methods')->name('shipping-methods.')->middleware('admin.permission:settings.view')->group(function () {
        Route::get('/', [ShippingMethodController::class, 'index'])->name('index');

        // Shipping method CRUD
        Route::post('/methods', [ShippingMethodController::class, 'storeMethod'])->name('methods.store')->middleware('admin.permission:settings.edit');
        Route::put('/methods/{method}', [ShippingMethodController::class, 'updateMethod'])->name('methods.update')->middleware('admin.permission:settings.edit');
        Route::post('/methods/{method}/toggle', [ShippingMethodController::class, 'toggleMethod'])->name('methods.toggle')->middleware('admin.permission:settings.edit');

        // Carriers — test MUST come before {carrier} wildcard
        Route::post('/carriers/test', [ShippingMethodController::class, 'testCarrier'])->name('carriers.test');
        Route::post('/carriers', [ShippingMethodController::class, 'storeCarrier'])->name('carriers.store')->middleware('admin.permission:settings.edit');
        Route::put('/carriers/{carrier}', [ShippingMethodController::class, 'updateCarrier'])->name('carriers.update')->middleware('admin.permission:settings.edit');
        Route::post('/carriers/{carrier}/toggle', [ShippingMethodController::class, 'toggleCarrier'])->name('carriers.toggle')->middleware('admin.permission:settings.edit');

        // Rates — datatable + store MUST come before {rate} wildcard
        Route::post('/rates/datatable', [ShippingMethodController::class, 'ratesDatatable'])->name('rates.datatable');
        Route::post('/rates', [ShippingMethodController::class, 'storeRate'])->name('rates.store')->middleware('admin.permission:settings.edit');
        Route::put('/rates/{rate}', [ShippingMethodController::class, 'updateRate'])->name('rates.update')->middleware('admin.permission:settings.edit');
        Route::delete('/rates/{rate}', [ShippingMethodController::class, 'destroyRate'])->name('rates.destroy')->middleware('admin.permission:settings.edit');
        Route::post('/rates/{rate}/toggle', [ShippingMethodController::class, 'toggleRate'])->name('rates.toggle')->middleware('admin.permission:settings.edit');

        // Country Settings
        Route::post('/country-settings', [ShippingMethodController::class, 'upsertCountrySetting'])->name('country-settings.upsert')->middleware('admin.permission:settings.edit');
        Route::get('/country-settings', [ShippingMethodController::class, 'countrySettings'])->name('country-settings.index');
    });

    // ─── Delivery ────────────────────────────────────────────────────────────
    Route::prefix('delivery')->name('delivery.')->group(function () {
        // Agents
        Route::get('/agents', [DeliveryAgentController::class, 'index'])->name('agents.index');
        Route::post('/agents', [DeliveryAgentController::class, 'store'])->name('agents.store');
        Route::post('/agents/datatable', [DeliveryAgentController::class, 'datatable'])->name('agents.datatable');
        Route::get('/agents/{agent}', [DeliveryAgentController::class, 'show'])->name('agents.show');
        Route::put('/agents/{agent}', [DeliveryAgentController::class, 'update'])->name('agents.update');
        Route::delete('/agents/{agent}', [DeliveryAgentController::class, 'destroy'])->name('agents.destroy');
        Route::post('/agents/{agent}/suspend', [DeliveryAgentController::class, 'suspend'])->name('agents.suspend');
        Route::post('/agents/{agent}/activate', [DeliveryAgentController::class, 'activate'])->name('agents.activate');
        Route::post('/agents/{agent}/reset-password', [DeliveryAgentController::class, 'resetPassword'])->name('agents.reset-password');
        Route::post('/agents/{agent}/assign-zone', [DeliveryAgentController::class, 'assignToZone'])->name('agents.assign-zone');
        Route::post('/agents/{agent}/assignments/datatable', [DeliveryAgentController::class, 'assignmentsDatatable'])->name('agents.assignments.datatable');
        Route::get('/agents/{agent}/earnings-summary', [DeliveryAgentController::class, 'earningsSummary'])->name('agents.earnings-summary');
        // Documents
        Route::post('/documents/{doc}/verify', [DeliveryAgentController::class, 'verifyDocument'])->name('documents.verify');
        Route::post('/documents/{doc}/reject', [DeliveryAgentController::class, 'rejectDocument'])->name('documents.reject');
        // Zones
        Route::get('/zones', [DeliveryZoneController::class, 'index'])->name('zones.index');
        Route::post('/zones', [DeliveryZoneController::class, 'store'])->name('zones.store');
        Route::get('/zones/live-map', [DeliveryZoneController::class, 'getAgentMap'])->name('zones.live-map');
        Route::get('/zones/{zone}', [DeliveryZoneController::class, 'show'])->name('zones.show');
        Route::put('/zones/{zone}', [DeliveryZoneController::class, 'update'])->name('zones.update');
        Route::delete('/zones/{zone}', [DeliveryZoneController::class, 'destroy'])->name('zones.destroy');
        Route::post('/zones/{zone}/assign-agents', [DeliveryZoneController::class, 'assignAgents'])->name('zones.assign-agents');
        Route::get('/zones/{zone}/agent-map', [DeliveryZoneController::class, 'getAgentMap'])->name('zones.agent-map');
        // Assignments
        Route::get('/assignments', [DeliveryAssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/assignments/datatable', [DeliveryAssignmentController::class, 'datatable'])->name('assignments.datatable');
        Route::post('/assignments/auto-assign', [DeliveryAssignmentController::class, 'autoAssign'])->name('assignments.auto-assign');
        Route::post('/assignments/manual-assign', [DeliveryAssignmentController::class, 'manualAssign'])->name('assignments.manual-assign');
        Route::get('/assignments/live-map', [DeliveryAssignmentController::class, 'liveMap'])->name('assignments.live-map');
        // Payouts
        Route::get('/payouts', [DeliveryPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/datatable', [DeliveryPayoutController::class, 'datatable'])->name('payouts.datatable');
        Route::post('/payouts/generate', [DeliveryPayoutController::class, 'generate'])->name('payouts.generate');
        Route::post('/payouts/{payout}/approve', [DeliveryPayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payout}/process', [DeliveryPayoutController::class, 'process'])->name('payouts.process');
    });

    // ── Marketers ─────────────────────────────────────────────────────────────────
    Route::prefix('marketers')->name('marketers.all.')->group(function () {
        Route::get('/', [MarketerController::class, 'index'])->name('index');
        Route::post('/', [MarketerController::class, 'store'])->name('store');
        Route::post('/datatable', [MarketerController::class, 'datatable'])->name('datatable');
        Route::get('/{marketer}', [MarketerController::class, 'show'])->name('show');
        Route::post('/{marketer}/approve', [MarketerController::class, 'approve'])->name('approve');
        Route::post('/{marketer}/reject', [MarketerController::class, 'reject'])->name('reject');
        Route::post('/{marketer}/suspend', [MarketerController::class, 'suspend'])->name('suspend');
        Route::post('/{marketer}/activate', [MarketerController::class, 'activate'])->name('activate');
        Route::post('/{marketer}/campaigns/datatable', [MarketerController::class, 'marketerCampaignsDatatable'])->name('marketer-campaigns.datatable');
        Route::post('/{marketer}/conversions/datatable', [MarketerController::class, 'marketerConversionsDatatable'])->name('marketer-conversions.datatable');
        Route::get('/{marketer}/tiers', [MarketerController::class, 'tiersShow'])->name('tiers.show');
        Route::post('/{marketer}/tiers', [MarketerController::class, 'storeTiers'])->name('tiers.store');
    });

    // ── Marketer Secret Promotions ──────────────────────────────────────────────
    Route::prefix('marketers-secret-promotions')->name('secret-promotions.')->group(function () {
        // AJAX helpers — must come before wildcard {secretPromotion}
        Route::get('/listings/by-vendor', [SecretPromotionController::class, 'getListingsForVendor'])->name('listings.by-vendor');
        Route::get('/listings/{listing}/details', [SecretPromotionController::class, 'getListingDetails'])->name('listings.details');
        Route::get('/stats/cards', [SecretPromotionController::class, 'stats'])->name('stats');
        Route::post('/datatable', [SecretPromotionController::class, 'datatable'])->name('datatable');

        Route::get('/', [SecretPromotionController::class, 'index'])->name('index');
        Route::post('/', [SecretPromotionController::class, 'store'])->name('store');
        Route::get('/{secretPromotion}', [SecretPromotionController::class, 'show'])->name('show');
        Route::put('/{secretPromotion}', [SecretPromotionController::class, 'update'])->name('update');
        Route::post('/{secretPromotion}/toggle-status', [SecretPromotionController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{secretPromotion}/expire', [SecretPromotionController::class, 'expire'])->name('expire');
        Route::post('/{secretPromotion}/duplicate', [SecretPromotionController::class, 'duplicate'])->name('duplicate');
    });





    // ── Marketer Samples ────────────────────────────────────────────────────────
    Route::prefix('marketer-samples')->name('marketers.samples.')->group(function () {
        Route::get('/', [MarketerController::class, 'samplesIndex'])->name('index');
        Route::post('/datatable', [MarketerController::class, 'samplesDatatable'])->name('datatable');
        Route::post('/{req}/approve', [MarketerController::class, 'approveSample'])->name('approve');
        Route::post('/{req}/dispatch', [MarketerController::class, 'dispatchSample'])->name('dispatch');
    });

    // ── Marketer Campaigns ────────────────────────────────────────────────────────
    Route::prefix('marketer-campaigns')->name('marketers.campaigns.')->group(function () {
        Route::get('/', [MarketerController::class, 'campaignsIndex'])->name('index');
        Route::post('/datatable', [MarketerController::class, 'campaignsDatatable'])->name('datatable');
        Route::get('/{campaign}', [MarketerController::class, 'showCampaign'])->name('show');
        Route::post('/{campaign}/approve', [MarketerController::class, 'approveCampaign'])->name('approve');
        Route::post('/{campaign}/reject', [MarketerController::class, 'rejectCampaign'])->name('reject');
    });

    // ── Marketer Conversions ──────────────────────────────────────────────────────
    Route::prefix('marketer-conversions')->name('marketers.conversions.')->group(function () {
        Route::get('/', [MarketerController::class, 'conversionsIndex'])->name('index');
        Route::post('/datatable', [MarketerController::class, 'conversionsDatatable'])->name('datatable');
        Route::post('/approve', [MarketerController::class, 'approveConversions'])->name('approve');
    });

    // ── Marketer Payouts ──────────────────────────────────────────────────────────
    Route::prefix('marketer-payouts')->name('marketers.payouts.')->group(function () {
        Route::get('/', [MarketerController::class, 'payoutsIndex'])->name('index');
        Route::post('/datatable', [MarketerController::class, 'payoutsDatatable'])->name('datatable');
        Route::post('/generate', [MarketerController::class, 'generatePayout'])->name('generate');
        Route::post('/{payout}/approve', [MarketerController::class, 'approvePayout'])->name('approve');
        Route::post('/{payout}/process', [MarketerController::class, 'processPayout'])->name('process');
    });

    // ── FBN / Fulfillment ─────────────────────────────────────────────────────
    Route::prefix('fbn')->name('fbn.')->group(function () {

        // Inbound requests
        Route::prefix('inbound')->name('inbound.')->group(function () {
            Route::get('/', [FbnController::class, 'inboundIndex'])->name('index');
            Route::post('/datatable', [FbnController::class, 'inboundDatatable'])->name('datatable');
            Route::post('/{request}/approve', [FbnController::class, 'approveInbound'])->name('approve');
            Route::post('/{request}/reject', [FbnController::class, 'rejectInbound'])->name('reject');
            Route::post('/{request}/tracking', [FbnController::class, 'updateTracking'])->name('tracking');
            Route::post('/{request}/receive', [FbnController::class, 'receiveInbound'])->name('receive');
        });

        // Storage fees
        Route::prefix('storage-fees')->name('storage-fees.')->group(function () {
            Route::get('/', [FbnController::class, 'storageFeesIndex'])->name('index');
            Route::post('/datatable', [FbnController::class, 'storageFeesDatatable'])->name('datatable');
            Route::post('/generate', [FbnController::class, 'generateMonthlyFees'])->name('generate');
            Route::post('/{fee}/status', [FbnController::class, 'updateStorageFeeStatus'])->name('status');
        });

        // Marketplace shipping rules
        Route::prefix('marketplace')->name('marketplace.')->group(function () {
            Route::get('/', [FbnController::class, 'marketplaceIndex'])->name('index');
            Route::post('/datatable', [FbnController::class, 'marketplaceDatatable'])->name('datatable');
            Route::post('/', [FbnController::class, 'storeMarketplaceRule'])->name('store');
            Route::put('/{rule}', [FbnController::class, 'updateMarketplaceRule'])->name('update');
            Route::delete('/{rule}', [FbnController::class, 'destroyMarketplaceRule'])->name('destroy');
        });
    });

    // ── Vendor Subscriptions ──────────────────────────────────────────────────────
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        // Plans CRUD
        Route::get('/plans', [SubscriptionController::class, 'plansIndex'])->name('plans.index');
        Route::post('/plans', [SubscriptionController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [SubscriptionController::class, 'updatePlan'])->name('plans.update');
        Route::post('/plans/{plan}/toggle-active', [SubscriptionController::class, 'togglePlanActive'])->name('plans.toggle-active');
        Route::delete('/plans/{plan}', [SubscriptionController::class, 'destroyPlan'])->name('plans.destroy');

        // Vendor subscriptions
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/datatable', [SubscriptionController::class, 'datatable'])->name('datatable');
        Route::post('/subscribe-vendor', [SubscriptionController::class, 'subscribeVendor'])->name('subscribe-vendor');
        // Specific before wildcard
        Route::get('/invoices/list', [SubscriptionController::class, 'invoicesIndex'])->name('invoices.index');
        Route::post('/invoices/datatable', [SubscriptionController::class, 'invoicesDatatable'])->name('invoices.datatable');
        Route::post('/invoices/{invoice}/mark-paid', [SubscriptionController::class, 'markInvoicePaid'])->name('invoices.mark-paid');

        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('cancel');
    });

    // ─── Admin Product Listings (Now Nawy) ───────────────────────────────────
    Route::prefix('admin-product-listings')->name('admin-product-listings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'store'])->name('store');
        Route::get('/{adminProductListing}/edit', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'edit'])->name('edit');
        Route::put('/{adminProductListing}', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'update'])->name('update');
        Route::delete('/{adminProductListing}', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'destroy'])->name('destroy');
        Route::get('/{adminProductListing}/nawy-preview', [\App\Http\Controllers\Admin\AdminProductListingController::class, 'nawyPreview'])->name('nawy-preview');
    });

}); // end auth.admin middleware group

