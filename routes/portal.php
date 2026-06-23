<?php

use App\Http\Controllers\Portal\LandingController;
use App\Http\Controllers\Portal\RegistrationController;
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

// Multi-step vendor registration
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::get('/register/success', [RegistrationController::class, 'success'])->name('register.success');
Route::post('/register/step/{step}', [RegistrationController::class, 'storeStep'])->where('step', '[1-3]')->name('register.step');
Route::get('/register/check-slug', [RegistrationController::class, 'checkSlug'])->name('register.check-slug');
Route::get('/register/cities', [RegistrationController::class, 'cities'])->name('register.cities');
Route::post('/register/upload', [RegistrationController::class, 'uploadDocument'])->name('register.upload');
Route::delete('/register/document', [RegistrationController::class, 'removeDocument'])->name('register.document.remove');
Route::post('/register/complete', [RegistrationController::class, 'complete'])->name('register.complete');

// Language switcher
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ar', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back()->withFragment('');
})->name('language');
