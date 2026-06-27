<?php

use App\Http\Controllers\TravelAgencyPortal\AuthController;
use App\Http\Controllers\TravelAgencyPortal\BookingController;
use App\Http\Controllers\TravelAgencyPortal\DashboardController;
use App\Http\Controllers\TravelAgencyPortal\PackageController;
use App\Http\Controllers\TravelAgencyPortal\ProfileController;
use Illuminate\Support\Facades\Route;

Route::name('travel-agency.')
    ->group(function () {

        // ── Guest ─────────────────────────────────────────────────────────────
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');

        // ── Authenticated ─────────────────────────────────────────────────────
        Route::middleware(['auth.travel_agency'])->group(function () {

            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Packages
            Route::prefix('packages')->name('packages.')->group(function () {
                Route::get('/', [PackageController::class, 'index'])->name('index');
                Route::get('/create', [PackageController::class, 'create'])->name('create');
                Route::post('/', [PackageController::class, 'store'])->name('store');
                Route::get('/{package}', [PackageController::class, 'show'])->name('show');
                Route::get('/{package}/edit', [PackageController::class, 'edit'])->name('edit');
                Route::put('/{package}', [PackageController::class, 'update'])->name('update');
                Route::post('/{package}/submit', [PackageController::class, 'submitForReview'])->name('submit');
                Route::delete('/{package}/media/{media}', [PackageController::class, 'destroyMedia'])->name('media.destroy');
            });

            // Bookings (read + status updates — agency confirms/cancels)
            Route::prefix('bookings')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
                Route::patch('/{booking}/status', [BookingController::class, 'updateStatus'])->name('status');
            });

            // Profile
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        });
    });
