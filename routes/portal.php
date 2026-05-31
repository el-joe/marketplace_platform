<?php

use App\Http\Controllers\Portal\LandingController;
use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Routes — portal.noon.loc
| Public: landing page, registration, FAQ
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/faq', [LandingController::class, 'faq'])->name('faq');
Route::get('/how-it-works', [LandingController::class, 'howItWorks'])->name('how-it-works');
Route::get('/fulfillment', [LandingController::class, 'fulfillment'])->name('fulfillment');
Route::get('/smart-tools', [LandingController::class, 'smartTools'])->name('smart-tools');

// Registration (redirects to partner login for now)
Route::get('/register', [PortalAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [PortalAuthController::class, 'register'])->name('register.submit');

// Language switcher
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ar', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back()->withFragment('');
})->name('language');
