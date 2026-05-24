<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Loaded inside: Route::domain('admin.*')->name('admin.')->group(...)
|--------------------------------------------------------------------------
*/

// ─── Locale switcher ──────────────────────────────────────────────────────────
Route::post('/set-locale', function (Request $request) {
    $locale = $request->input('locale', 'en');
    if (in_array($locale, config('app.supported_locales', ['en', 'ar']), true)) {
        session(['locale' => $locale]);
    }
    return response()->json(['success' => true]);
})->name('set-locale');

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
    Route::delete('/delete-image/{mediaId}', [ProductController::class, 'deleteImage'])->name('delete-image');

    // CRUD
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
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
            ->where('a.is_variant_type', true)
            ->select('a.id', 'a.name_en')
            ->orderBy('a.sort_order')
            ->get();
        return response()->json(['data' => $attrs]);
    })->name('attributes');
});

Route::get('/brands/search', function (Request $request) {
    $term = trim($request->input('q', ''));
    $results = DB::table('brands')
        ->where('is_active', true)
        ->where('name', 'like', "%{$term}%")
        ->limit(30)
        ->get(['id', 'name as text']);
    return response()->json(['results' => $results]);
})->name('brands.search');

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

// ─── Country switcher ─────────────────────────────────────────────────────────
Route::post('/country', function (Request $request) {
    $code = strtoupper(trim($request->input('country', '')));
    if ($code && preg_match('/^[A-Z]{2,3}$/', $code)) {
        session(['admin_country' => $code]);
    }
    return response()->json(['success' => true]);
})->name('country');

// ─── Placeholders ─────────────────────────────────────────────────────────────
// ─── Orders ───────────────────────────────────────────────────────────────────
use App\Http\Controllers\Admin\OrderController;

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
Route::get('/vendors', fn() => abort(404))->name('vendors.index');

// ─── Geography ───────────────────────────────────────────────────────────────

use App\Http\Controllers\Admin\CountryController;

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

use App\Http\Controllers\Admin\CityController;

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

use App\Http\Controllers\Admin\CurrencyController;

Route::prefix('currencies')->name('currencies.')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('index');
    Route::get('/{code}/edit', [CurrencyController::class, 'edit'])->name('edit');
    Route::put('/{code}', [CurrencyController::class, 'update'])->name('update');
    Route::post('/dispatch-update', [CurrencyController::class, 'dispatchUpdate'])->name('dispatch-update');
});
