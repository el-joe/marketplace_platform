<?php

use App\Http\Controllers\TravelAgencyPortal\AuthController;
use App\Http\Controllers\TravelAgencyPortal\BookingController;
use App\Http\Controllers\TravelAgencyPortal\DashboardController;
use App\Http\Controllers\TravelAgencyPortal\PackageController;
use App\Http\Controllers\TravelAgencyPortal\PackageInquiryController;
use App\Http\Controllers\TravelAgencyPortal\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::name('travel-agency.')
    ->group(function () {

        Broadcast::routes(['middleware' => ['web', 'auth.travel_agency']]);

        // ── Guest ─────────────────────────────────────────────────────────────
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');

        // ── Authenticated ─────────────────────────────────────────────────────
        Route::middleware(['auth.travel_agency'])->group(function () {

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

            // Packages
            Route::prefix('packages')->name('packages.')->group(function () {
                Route::get('/', [PackageController::class, 'index'])->name('index');
                Route::get('/create', [PackageController::class, 'create'])->name('create');
                Route::post('/', [PackageController::class, 'store'])->name('store');
                Route::get('/cities-for-country/{travelCountryId}', [PackageController::class, 'citiesForCountry'])->name('cities-for-country');
                Route::get('/{package}', [PackageController::class, 'show'])->name('show');
                Route::get('/{package}/edit', [PackageController::class, 'edit'])->name('edit');
                Route::put('/{package}', [PackageController::class, 'update'])->name('update');
                Route::post('/{package}/submit', [PackageController::class, 'submitForReview'])->name('submit');
                Route::delete('/{package}/media/{media}', [PackageController::class, 'destroyMedia'])->name('media.destroy');
                Route::get('/{package}/contract', [PackageController::class, 'downloadContract'])->name('contract.download');
            });

            // Bookings
            Route::prefix('bookings')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::get('/customer-search', [BookingController::class, 'customerSearch'])->name('customer-search');
                Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
                Route::patch('/{booking}/status', [BookingController::class, 'updateStatus'])->name('status');
            });

            // Create booking scoped to a package
            Route::get('/packages/{package}/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
            Route::post('/packages/{package}/bookings', [BookingController::class, 'store'])->name('bookings.store');

            // Package Inquiries (lead management)
            Route::prefix('inquiries')->name('inquiries.')->group(function () {
                Route::get('/', [PackageInquiryController::class, 'index'])->name('index');
                Route::post('/{inquiry}/contacted', [PackageInquiryController::class, 'markContacted'])->name('contacted');
                Route::post('/{inquiry}/convert', [PackageInquiryController::class, 'convertToBooking'])->name('convert');
                Route::post('/{inquiry}/close', [PackageInquiryController::class, 'close'])->name('close');
            });

            // Profile
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        });
    });
