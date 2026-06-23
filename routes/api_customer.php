<?php

use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\DisputeController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RefundController;
use App\Http\Controllers\Customer\ReturnController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\SearchController;
use App\Http\Controllers\Customer\SupportTicketController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes — /api/customer/v1/{country}/...
| Guard: customer (JWT)
| Middleware: detect.country resolves {country} site_code → Country model
|--------------------------------------------------------------------------
*/

Route::prefix('v1/{country}')
    ->middleware('detect.country')
    ->group(function (): void {

        // ── Product catalog (public) ──────────────────────────────────────────
        Route::prefix('products')->name('customer.products.')->group(function (): void {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('{slug}', [ProductController::class, 'show'])->name('show');
        });

        // ── Categories (public) ───────────────────────────────────────────────
        Route::prefix('categories')->name('customer.categories.')->group(function (): void {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('{slug}', [CategoryController::class, 'show'])->name('show');
        });

        // ── Page Renderer (public) ────────────────────────────────────────────
        Route::get('pages/{type}', [PageController::class, 'show'])->name('customer.pages.show');

        // ── Search (public) ───────────────────────────────────────────────────
        Route::prefix('search')->name('customer.search.')->group(function (): void {
            Route::get('/', [SearchController::class, 'search'])->name('search');
            Route::get('suggestions', [SearchController::class, 'suggestions'])->name('suggestions');
        });

        // ── Public auth endpoints ─────────────────────────────────────────────
        Route::prefix('auth')->name('customer.auth.')->group(function (): void {
            Route::post('register', [AuthController::class, 'register'])
                ->middleware('throttle:10,1')
                ->name('register');

            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:10,1')
                ->name('login');

            Route::post('refresh-token', [AuthController::class, 'refreshToken'])
                ->middleware('throttle:10,1')
                ->name('refresh');

            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
                ->middleware('throttle:5,1')
                ->name('forgot-password');

            Route::post('reset-password', [AuthController::class, 'resetPassword'])
                ->middleware('throttle:5,1')
                ->name('reset-password');

            // Email verification — token from email link, no auth guard needed
            Route::post('verify-email', [AuthController::class, 'verifyEmail'])
                ->name('verify-email');
        });

        // ── Authenticated endpoints ───────────────────────────────────────────
        Route::middleware('auth:customer')->group(function (): void {

            // Auth
            Route::prefix('auth')->name('customer.auth.')->group(function (): void {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('me', [AuthController::class, 'me'])->name('me');
                Route::post('resend-verification', [AuthController::class, 'resendVerification'])
                    ->middleware('throttle:3,1')
                    ->name('resend-verification');
            });

            // Profile
            Route::prefix('profile')->name('customer.profile.')->group(function (): void {
                Route::get('/', [ProfileController::class, 'show'])->name('show');
                Route::put('/', [ProfileController::class, 'update'])->name('update');
                Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
                Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
            });

            // Wishlist
            Route::prefix('wishlist')->name('customer.wishlist.')->group(function (): void {
                Route::get('/', [WishlistController::class, 'index'])->name('index');
                Route::post('/', [WishlistController::class, 'store'])->name('store');
                Route::delete('{product_id}', [WishlistController::class, 'destroy'])->name('destroy');
            });

            // Addresses
            Route::prefix('addresses')->name('customer.addresses.')->group(function (): void {
                Route::get('/', [AddressController::class, 'index'])->name('index');
                Route::post('/', [AddressController::class, 'store'])->name('store');
                Route::put('{address}', [AddressController::class, 'update'])->name('update');
                Route::delete('{address}', [AddressController::class, 'destroy'])->name('destroy');
                Route::put('{address}/set-default', [AddressController::class, 'setDefault'])->name('set-default');
            });

            // Cart
            Route::prefix('cart')->name('customer.cart.')->group(function (): void {
                Route::get('/', [CartController::class, 'show'])->name('show');
                Route::post('items', [CartController::class, 'addItem'])->name('items.add');
                Route::put('items/{id}', [CartController::class, 'updateItem'])->name('items.update');
                Route::delete('items/{id}', [CartController::class, 'removeItem'])->name('items.remove');
                Route::delete('/', [CartController::class, 'clear'])->name('clear');
                Route::post('coupon', [CartController::class, 'applyCoupon'])->name('coupon.apply');
                Route::delete('coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');
            });

            // Checkout
            Route::prefix('checkout')->name('customer.checkout.')->group(function (): void {
                Route::post('prepare', [CheckoutController::class, 'prepare'])->name('prepare');
                Route::post('place-order', [CheckoutController::class, 'placeOrder'])
                    ->middleware('throttle:5,1')
                    ->name('place-order');
                Route::get('{order_number}/confirmation', [CheckoutController::class, 'confirmation'])->name('confirmation');
            });

            // Orders
            Route::prefix('orders')->name('customer.orders.')->group(function (): void {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::get('{order_number}', [OrderController::class, 'show'])->name('show');
                Route::post('{order_number}/cancel', [OrderController::class, 'cancel'])->name('cancel');
                Route::post('{order_number}/returns', [ReturnController::class, 'store'])->name('returns.store');
                Route::post('{order_number}/disputes', [DisputeController::class, 'store'])->name('disputes.store');
                Route::post('{order_number}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
            });

            // Sub-order tracking
            Route::post('sub-orders/{id}/track', [OrderController::class, 'trackSubOrder'])
                ->name('customer.sub-orders.track');

            // Returns
            Route::prefix('returns')->name('customer.returns.')->group(function (): void {
                Route::get('/', [ReturnController::class, 'index'])->name('index');
                Route::get('{return_number}', [ReturnController::class, 'show'])->name('show');
            });

            // Disputes
            Route::prefix('disputes')->name('customer.disputes.')->group(function (): void {
                Route::get('{dispute_number}', [DisputeController::class, 'show'])->name('show');
                Route::post('{dispute_number}/messages', [DisputeController::class, 'addMessage'])->name('messages.store');
            });

            // Reviews
            Route::prefix('reviews')->name('customer.reviews.')->group(function (): void {
                Route::put('{id}', [ReviewController::class, 'update'])->name('update');
                Route::post('{id}/helpful', [ReviewController::class, 'helpful'])->name('helpful');
            });

            // Refunds
            Route::prefix('refunds')->name('customer.refunds.')->group(function (): void {
                Route::get('/', [RefundController::class, 'index'])->name('index');
                Route::get('{id}', [RefundController::class, 'show'])->name('show');
            });

            // Support tickets
            Route::prefix('support/tickets')->name('customer.support.tickets.')->group(function (): void {
                Route::get('/', [SupportTicketController::class, 'index'])->name('index');
                Route::post('/', [SupportTicketController::class, 'store'])->name('store');
                Route::get('{ticket_number}', [SupportTicketController::class, 'show'])->name('show');
                Route::post('{ticket_number}/messages', [SupportTicketController::class, 'addMessage'])->name('messages.store');
                Route::put('{ticket_number}/rate', [SupportTicketController::class, 'rate'])->name('rate');
            });
        });

        // Public review listing (outside auth guard)
        Route::get('products/{slug}/reviews', [ReviewController::class, 'indexByProduct'])
            ->name('customer.products.reviews');
    });
