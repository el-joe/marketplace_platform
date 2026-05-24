<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::domain('admin.' . env('APP_DOMAIN'))->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');
    Route::get('/dashboard/orders-by-status', [DashboardController::class, 'ordersByStatus'])->name('dashboard.orders-by-status');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/top-sellers', [DashboardController::class, 'topSellers'])->name('dashboard.top-sellers');
    Route::get('/dashboard/pending-items', [DashboardController::class, 'pendingItems'])->name('dashboard.pending-items');
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock'])->name('dashboard.low-stock');

    // Products
    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::post('/products/datatable', [ProductsController::class, 'datatable'])->name('products.datatable');
    Route::post('/products/bulk', [ProductsController::class, 'bulk'])->name('products.bulk');
    Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');

    // Placeholder routes referenced in the dashboard view
    Route::get('/orders', fn() => abort(404))->name('orders.index');
    Route::get('/vendors', fn() => abort(404))->name('vendors.index');
});

