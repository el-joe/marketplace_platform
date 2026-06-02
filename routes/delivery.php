<?php

use App\Http\Controllers\Delivery\AuthController;
use App\Http\Controllers\Delivery\AssignmentController;
use App\Http\Controllers\Delivery\DashboardController;
use App\Http\Controllers\Delivery\EarningsController;
use App\Http\Controllers\Delivery\LocationController;
use App\Http\Controllers\Delivery\ProfileController;
use Illuminate\Support\Facades\Route;

Route::domain('delivery.' . env('APP_DOMAIN', 'localhost'))
    ->name('delivery.')
    ->group(function () {

        // ── Guest routes ──────────────────────────────────────────────────────
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

        // ── Authenticated routes ───────────────────────────────────────────────
        Route::middleware(['auth.delivery'])->group(function () {

            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Assignments
            Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
            Route::post('/assignments/{assignment}/accept', [AssignmentController::class, 'accept'])->name('assignments.accept');
            Route::post('/assignments/{assignment}/picked-up', [AssignmentController::class, 'pickedUp'])->name('assignments.picked-up');
            Route::post('/assignments/{assignment}/deliver', [AssignmentController::class, 'deliver'])->name('assignments.deliver');
            Route::post('/assignments/{assignment}/fail', [AssignmentController::class, 'fail'])->name('assignments.fail');

            // Location
            Route::put('/location', [LocationController::class, 'update'])->name('location.update');
            Route::put('/availability', [LocationController::class, 'updateAvailability'])->name('availability.update');

            // Earnings
            Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
            Route::get('/earnings/summary', [EarningsController::class, 'summary'])->name('earnings.summary');

            // Profile
            Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
            Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
        });
    });
