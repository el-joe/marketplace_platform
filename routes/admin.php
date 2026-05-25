<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
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
use App\Http\Controllers\Admin\CurrencyController;
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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');
    Route::get('/dashboard/orders-by-status', [DashboardController::class, 'ordersByStatus'])->name('dashboard.orders-by-status');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/top-sellers', [DashboardController::class, 'topSellers'])->name('dashboard.top-sellers');
    Route::get('/dashboard/pending-items', [DashboardController::class, 'pendingItems'])->name('dashboard.pending-items');
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock'])->name('dashboard.low-stock');

    // ─── Products ─────────────────────────────────────────────────────────────────
    Route::prefix('products')->name('products.')->group(function () {
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
    });

    // ─── Async-select search endpoints ────────────────────────────────────────────
    Route::prefix('categories')->name('categories.')->group(function () {
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

    // ─── Brands ──────────────────────────────────────────────────────────────────
    Route::prefix('brands')->name('brands.')->group(function () {
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
    Route::prefix('categories')->name('categories.')->group(function () {
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
    });

    // ─── Attributes (CRUD) ────────────────────────────────────────────────────────
    Route::prefix('attributes')->name('attributes.')->group(function () {
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

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::post('/datatable', [OrderController::class, 'datatable'])->name('datatable');
        Route::post('/update-sub-order-status', [OrderController::class, 'updateSubOrderStatus'])->name('update-sub-order-status');
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::post('/{id}/force-cancel', [OrderController::class, 'forceCancel'])->name('force-cancel');
        Route::post('/{id}/refund', [OrderController::class, 'processRefund'])->name('refund');
        Route::post('/{id}/dispute', [OrderController::class, 'escalateDispute'])->name('dispute');
        Route::post('/{id}/flag-fraud', [OrderController::class, 'flagFraud'])->name('flag-fraud');
    });
    Route::get('/sub-orders/{id}/next-statuses', [OrderController::class, 'nextStatuses'])->name('sub-orders.next-statuses');
    // ─── Payouts ──────────────────────────────────────────────────────────────────

    Route::prefix('payouts')->name('payouts.')->group(function () {
        Route::get('/', [PayoutController::class, 'index'])->name('index');
        Route::post('/datatable', [PayoutController::class, 'datatable'])->name('datatable');
        Route::get('/{payout}', [PayoutController::class, 'show'])->name('show');
        Route::post('/{payout}/approve', [PayoutController::class, 'approve'])->name('approve');
        Route::post('/{payout}/process', [PayoutController::class, 'process'])->name('process');
        Route::post('/{payout}/hold', [PayoutController::class, 'hold'])->name('hold');
        Route::post('/{payout}/recalculate', [PayoutController::class, 'recalculate'])->name('recalculate');
    });

    // ─── Flash Sales ──────────────────────────────────────────────────────────────

    Route::prefix('flash-sales')->name('flash-sales.')->group(function () {
        Route::get('/', [FlashSaleController::class, 'index'])->name('index');
        Route::post('/datatable', [FlashSaleController::class, 'datatable'])->name('datatable');
        Route::get('/create', [FlashSaleController::class, 'create'])->name('create');
        Route::post('/store', [FlashSaleController::class, 'store'])->name('store');

        // Submission actions (before /{flashSale} wildcard)
        Route::post('/submissions/{submission}/approve', [FlashSaleController::class, 'approveSubmission'])->name('submissions.approve');
        Route::post('/submissions/{submission}/reject', [FlashSaleController::class, 'rejectSubmission'])->name('submissions.reject');
        Route::get('/submissions/{submission}/fraud-check', [FlashSaleController::class, 'checkFraud'])->name('submissions.fraud-check');

        Route::get('/{flashSale}', [FlashSaleController::class, 'show'])->name('show');
        Route::put('/{flashSale}', [FlashSaleController::class, 'update'])->name('update');
        Route::post('/{flashSale}/transition', [FlashSaleController::class, 'transition'])->name('transition');
        Route::post('/{flashSale}/invite-vendors', [FlashSaleController::class, 'inviteVendors'])->name('invite-vendors');
        Route::post('/{flashSale}/submissions/datatable', [FlashSaleController::class, 'submissionsDatatable'])->name('submissions.datatable');
    });

    // ─── Page Builder ──────────────────────────────────────────────────────────────

    Route::prefix('page-builder')->name('page-builder.')->group(function () {

        // Pages listing & CRUD
        Route::get('/', [PageBuilderController::class, 'index'])->name('index');
        Route::post('/datatable', [PageBuilderController::class, 'datatable'])->name('datatable');
        Route::get('/create', [PageBuilderController::class, 'create'])->name('create');
        Route::post('/store', [PageBuilderController::class, 'store'])->name('store');

        // Block-level routes (before /{page} wildcard)
        Route::post('/blocks/{block}/toggle-visibility', [PageBuilderController::class, 'blockToggleVisibility'])->name('blocks.toggle');
        Route::put('/blocks/{block}', [PageBuilderController::class, 'blockUpdate'])->name('blocks.update');
        Route::delete('/blocks/{block}', [PageBuilderController::class, 'blockDestroy'])->name('blocks.destroy');
        Route::get('/blocks/{block}/revisions', [PageBuilderController::class, 'blockRevisions'])->name('blocks.revisions');

        // Slides
        Route::get(
            '/blocks/{block}/slides',
            fn(\App\Models\PageBlock $block) =>
            response()->json(['slides' => $block->slides()->with(['desktopFile', 'mobileFile'])->orderBy('position')->get()->map(fn($s) => app(PageBuilderController::class)->serializeSlidePublic($s))])
        )->name('blocks.slides.index');
        Route::post('/blocks/{block}/slides', [PageBuilderController::class, 'slideStore'])->name('blocks.slides.store');
        Route::post('/blocks/{block}/slides/reorder', [PageBuilderController::class, 'slidesReorder'])->name('blocks.slides.reorder');
        Route::get(
            '/slides/{slide}',
            fn(\App\Models\SliderSlide $slide) =>
            response()->json(['slide' => $slide->load(['desktopFile', 'mobileFile'])->toArray()])
        )->name('slides.show');
        Route::put('/slides/{slide}', [PageBuilderController::class, 'slideUpdate'])->name('slides.update');
        Route::delete('/slides/{slide}', [PageBuilderController::class, 'slideDestroy'])->name('slides.destroy');

        // Ad Images
        Route::get(
            '/blocks/{block}/ad-images',
            fn(\App\Models\PageBlock $block) =>
            response()->json(['items' => $block->adImageItems()->with('file')->orderBy('position')->get()->map(fn($i) => app(PageBuilderController::class)->serializeAdImagePublic($i))])
        )->name('blocks.ad-images.index');
        Route::post('/blocks/{block}/ad-images', [PageBuilderController::class, 'adImageStore'])->name('blocks.ad-images.store');
        Route::post('/blocks/{block}/ad-images/reorder', [PageBuilderController::class, 'adImagesReorder'])->name('blocks.ad-images.reorder');
        Route::get(
            '/ad-images/{item}',
            fn(\App\Models\AdImageItem $item) =>
            response()->json(['item' => $item->load('file')->toArray()])
        )->name('ad-images.show');
        Route::put('/ad-images/{item}', [PageBuilderController::class, 'adImageUpdate'])->name('ad-images.update');
        Route::delete('/ad-images/{item}', [PageBuilderController::class, 'adImageDestroy'])->name('ad-images.destroy');

        // Block Products
        Route::get(
            '/blocks/{block}/products',
            fn(\App\Models\PageBlock $block) =>
            response()->json(['items' => $block->blockProducts()->with('productVariant.product')->orderBy('position')->get()->map(fn($p) => ['id' => $p->id, 'variant_id' => $p->product_variant_id, 'name' => $p->productVariant?->product?->name_en ?? '—', 'position' => $p->position])])
        )->name('blocks.products.index');
        Route::post('/blocks/{block}/products', [PageBuilderController::class, 'blockProductStore'])->name('blocks.products.store');
        Route::post('/blocks/{block}/products/reorder', [PageBuilderController::class, 'blockProductsReorder'])->name('blocks.products.reorder');
        Route::delete('/products/{blockProduct}', [PageBuilderController::class, 'blockProductDestroy'])->name('products.destroy');

        // Sections
        Route::post('/sections/{section}', [PageBuilderController::class, 'sectionUpdate'])->name('sections.update');   // POST + _method=PUT
        Route::delete('/sections/{section}', [PageBuilderController::class, 'sectionDestroy'])->name('sections.destroy');

        // Pages (wildcard last)
        Route::get('/{page}/edit', [PageBuilderController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageBuilderController::class, 'update'])->name('update');
        Route::post('/{page}/publish', [PageBuilderController::class, 'publish'])->name('publish');
        Route::post('/{page}/clone', [PageBuilderController::class, 'clone'])->name('clone');
        Route::delete('/{page}', [PageBuilderController::class, 'destroy'])->name('destroy');
        Route::post('/{page}/blocks', [PageBuilderController::class, 'blockStore'])->name('blocks.store');
        Route::post('/{page}/blocks/reorder', [PageBuilderController::class, 'blocksReorder'])->name('blocks.reorder');
        Route::post('/{page}/sections', [PageBuilderController::class, 'sectionStore'])->name('sections.store');
    });

    // ─── Vendors ─────────────────────────────────────────────────────────────────

    Route::prefix('vendors')->name('vendors.')->group(function () {
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

    Route::prefix('countries')->name('countries.')->group(function () {
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
    Route::prefix('cities')->name('cities.')->group(function () {
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
    Route::prefix('currencies')->name('currencies.')->group(function () {
        Route::get('/', [CurrencyController::class, 'index'])->name('index');
        Route::get('/{code}/edit', [CurrencyController::class, 'edit'])->name('edit');
        Route::put('/{code}', [CurrencyController::class, 'update'])->name('update');
        Route::post('/dispatch-update', [CurrencyController::class, 'dispatchUpdate'])->name('dispatch-update');
    });

}); // end auth.admin middleware group

