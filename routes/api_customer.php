<?php

use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\CouponController;
use App\Http\Controllers\Customer\GiftCardController;
use App\Http\Controllers\Customer\DisputeController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\PaymentMethodController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RefundController;
use App\Http\Controllers\Customer\ReturnController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\SearchController;
use App\Http\Controllers\Customer\SupportTicketController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\BrowseController;
use App\Http\Controllers\Customer\ListingController;
use App\Http\Controllers\Customer\ListingDetailController;
use App\Http\Controllers\Customer\NavigationController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\PageController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\VendorPageController;
use App\Http\Controllers\Customer\BrandPageController;
use App\Http\Controllers\Customer\WalletController;
use App\Http\Controllers\Customer\WarrantyClaimController;
use App\Models\Country;
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

        // ── Home composite (public) ───────────────────────────────────────────
        Route::get('home', [HomeController::class, 'index'])->name('customer.home.index');

        // ── Unified navigation tree (public) ─────────────────────────────────
        Route::get('nav', [NavigationController::class, 'index'])->name('customer.nav.index');

        // ── Product catalog (public) ──────────────────────────────────────────
        Route::prefix('products')->name('customer.products.')->group(function (): void {
            Route::get('/', [ProductController::class, 'index'])->name('index');

            // Legacy: product detail moved to /l/{identifier}. Keep old URLs working.
            Route::get('{identifier}', [ListingDetailController::class, 'show'])->name('show.redirect');
        });

        // ── Listing detail (public) ───────────────────────────────────────────
        // GET /l/{identifier} — identifier IN (listing UUID, variant SKU, listing_ref, product slug)
        Route::get('l/{identifier}', [ListingDetailController::class, 'show'])
            ->name('customer.listing.show');

        // ── Unified browse (public) ───────────────────────────────────────────
        // GET /browse/{type}/{id}  — type IN (product, classified, travel); id = category UUID
        Route::get('browse/{type}/{id}', [BrowseController::class, 'show'])->name('customer.browse.show');

        // GET /travel — all active travel packages, unfiltered (same as browse/travel/all)
        Route::get('travel', [BrowseController::class, 'travelIndex'])->name('customer.travel.index');

        // ── Unified listing detail (public) ───────────────────────────────────
        // GET  /listings/{type}/{slug} — type IN (product, classified, travel)
        //   product:    slug = product slug
        //   classified: slug = listing_number (e.g. CL-XXXX)
        //   travel:     slug = package UUID
        Route::get('listings/{type}/{slug}', [ListingController::class, 'show'])
            ->name('customer.listings.show');

        // ── Classified inquiry (authenticated) ────────────────────────────────
        // POST /listings/classified/{slug}/inquiries
        Route::post(
            'listings/classified/{slug}/inquiries',
            [ListingController::class, 'createInquiry']
        )->middleware('auth:customer')->name('customer.listings.classified.inquiries.store');

        // ── Travel booking (authenticated) ────────────────────────────────────
        // POST /listings/travel/{slug}/bookings
        Route::post(
            'listings/travel/{slug}/bookings',
            [ListingController::class, 'createBooking']
        )->middleware('auth:customer')->name('customer.listings.travel.bookings.store');

        // POST /listings/travel/{slug}/bookings/{booking_number}/contract
        Route::post(
            'listings/travel/{slug}/bookings/{booking_number}/contract',
            [ListingController::class, 'signContract']
        )->middleware('auth:customer')->name('customer.listings.travel.bookings.contract');

        // Legacy alias: GET /categories/{slug} → browse/product/{id}
        Route::get(
            'categories/{slug}',
            function (\Illuminate\Http\Request $request, $country, string $slug) {
            $country = $request->attributes->get('country');

            $category = \App\Models\Category::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            return redirect()->route('customer.browse.show', [
                'country' => $country->site_code,
                'type' => 'product',
                'id' => $category->id,
            ], 301);
        }
        )->name('customer.categories.show.legacy');

        // ── Categories (public) ───────────────────────────────────────────────
        Route::prefix('categories')->name('customer.categories.')->group(function (): void {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
        });

        // ── Page Renderer (public) ────────────────────────────────────────────
        Route::get('pages/{type}', [PageController::class, 'show'])->name('customer.pages.show');

        // ── Page block click tracking (public — guests can click) ─────────────
        Route::post('blocks/{id}/click', [PageController::class, 'click'])
            ->middleware('throttle:60,1')
            ->name('customer.blocks.click');

        // ── Vendor storefront page (public) ───────────────────────────────────
        Route::get('vendors/{vendor_id}', [VendorPageController::class, 'show'])
            ->name('customer.vendors.show');

        // ── Brand page (public) ───────────────────────────────────────────────
        Route::get('brands/{id}', [BrandPageController::class, 'show'])
            ->name('customer.brands.show');

        // ── Coupon detail — public "Learn more" CTA (public) ──────────────────
        Route::get('coupons/{code}', [CouponController::class, 'show'])
            ->name('customer.coupons.show');

        // ── Search (public) ───────────────────────────────────────────────────
        Route::prefix('search')->name('customer.search.')->group(function (): void {
            Route::get('/', [SearchController::class, 'search'])->name('search');
            Route::get('suggestions', [SearchController::class, 'suggestions'])->name('suggestions');
        });

        // ── Gift cards (public balance check) ───────────────────────────────────
        Route::prefix('gift-cards')->name('customer.gift-cards.')->group(function (): void {
            Route::post('check-balance', [GiftCardController::class, 'checkBalance'])->name('check-balance');
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
                Route::delete('{vendor_listing_id}', [WishlistController::class, 'destroy'])->name('destroy');
            });

            // Addresses
            Route::prefix('addresses')->name('customer.addresses.')->group(function (): void {
                Route::get('/', [AddressController::class, 'index'])->name('index');
                Route::post('/', [AddressController::class, 'store'])->name('store');
                Route::put('{address}', [AddressController::class, 'update'])->name('update');
                Route::delete('{address}', [AddressController::class, 'destroy'])->name('destroy');
                Route::put('{address}/set-default', [AddressController::class, 'setDefault'])->name('set-default');
            });

            // Payment methods
            Route::prefix('payment-methods')->name('customer.payment-methods.')->group(function (): void {
                Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
                Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
                Route::patch('{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('set-default');
                Route::delete('{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
            });

            // Cart
            Route::prefix('cart')->name('customer.cart.')->group(function (): void {
                Route::get('/', [CartController::class, 'show'])->name('show');
                Route::post('items', [CartController::class, 'addItem'])->name('items.add');
                Route::post('items/bulk', [CartController::class, 'addItems'])->name('items.add-bulk');
                Route::put('items/{id}', [CartController::class, 'updateItem'])->name('items.update');
                Route::delete('items/{id}', [CartController::class, 'removeItem'])->name('items.remove');
                Route::delete('/', [CartController::class, 'clear'])->name('clear');
                Route::post('coupon', [CartController::class, 'applyCoupon'])->name('coupon.apply');
                Route::delete('coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');
            });

            // Wallet
            Route::prefix('wallet')->name('customer.wallet.')->group(function (): void {
                Route::get('/', [WalletController::class, 'show'])->name('show');
                Route::get('transactions', [WalletController::class, 'transactions'])->name('transactions');
                Route::post('withdrawal', [WalletController::class, 'requestWithdrawal'])->name('withdrawal');
            });

            // Gift cards
            Route::prefix('gift-cards')->name('customer.gift-cards.')->group(function (): void {
                Route::get('/', [GiftCardController::class, 'myCodes'])->name('index');
                Route::post('purchase', [GiftCardController::class, 'purchase'])->name('purchase');
            });

            // Checkout
            Route::prefix('checkout')->name('customer.checkout.')->group(function (): void {
                Route::get('shipping-methods', [CheckoutController::class, 'shippingMethods'])->name('shipping-methods');
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
                Route::get('/', [DisputeController::class, 'index'])->name('index');
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

            // Warranty claims
            Route::prefix('warranty-claims')->name('customer.warranty-claims.')->group(function (): void {
                Route::get('/', [WarrantyClaimController::class, 'index'])->name('index');
                Route::post('/', [WarrantyClaimController::class, 'store'])->name('store');
                Route::get('{id}', [WarrantyClaimController::class, 'show'])->name('show');
                Route::post('{id}/messages', [WarrantyClaimController::class, 'addMessage'])->name('messages.store');
            });

            // Support tickets
            Route::prefix('support/tickets')->name('customer.support.tickets.')->group(function (): void {
                Route::get('/', [SupportTicketController::class, 'index'])->name('index');
                Route::post('/', [SupportTicketController::class, 'store'])->name('store');
                Route::get('{ticket_number}', [SupportTicketController::class, 'show'])->name('show');
                Route::post('{ticket_number}/messages', [SupportTicketController::class, 'addMessage'])->name('messages.store');
                Route::put('{ticket_number}/rate', [SupportTicketController::class, 'rate'])->name('rate');
            });

            // Account dashboard
            Route::get('account/dashboard', [AccountController::class, 'dashboard'])
                ->name('customer.account.dashboard');

            // My classified listings (customer as seller)
            Route::prefix('account/classified-listings')->name('customer.account.classified-listings.')->group(function (): void {
                Route::get('/', [AccountController::class, 'listingsIndex'])->name('index');
                Route::post('/', [AccountController::class, 'listingsStore'])->name('store');
                Route::get('{listing_number}', [AccountController::class, 'listingsShow'])->name('show');
                Route::put('{listing_number}', [AccountController::class, 'listingsUpdate'])->name('update');
                Route::delete('{listing_number}', [AccountController::class, 'listingsDestroy'])->name('destroy');
                Route::get('{listing_number}/inquiries', [AccountController::class, 'listingInquiries'])->name('inquiries');
            });

            // My travel bookings
            Route::prefix('account/travel-bookings')->name('customer.account.travel-bookings.')->group(function (): void {
                Route::get('/', [AccountController::class, 'travelBookingsIndex'])->name('index');
                Route::get('{id}', [AccountController::class, 'travelBookingsShow'])->name('show');
                Route::post('{id}/cancel', [AccountController::class, 'travelBookingsCancel'])->name('cancel');
            });

            // My classified inquiries (customer as buyer)
            Route::prefix('account/inquiries')->name('customer.account.inquiries.')->group(function (): void {
                Route::get('/', [AccountController::class, 'inquiriesIndex'])->name('index');
                Route::get('{id}', [AccountController::class, 'inquiriesShow'])->name('show');
            });
        });

        // Public review listing (outside auth guard)
        Route::get('products/{slug}/reviews', [ReviewController::class, 'indexByProduct'])
            ->name('customer.products.reviews');
    });
