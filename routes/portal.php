<?php

use App\Http\Controllers\Portal\AdSupportController;
use App\Http\Controllers\Portal\AdvertiseRequestController;
use App\Http\Controllers\Portal\BlogController;
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

// noon ads style seller landing — {country} mirrors the storefront's country-prefixed routes
Route::get('/{country}/advertise/sellers', [LandingController::class, 'sellers'])->name('sellers');

// noon ads style brand landing — mirrors advertise.noon.com/{locale}/brands
Route::get('/{country}/advertise/brands', [LandingController::class, 'advertiseBrands'])->name('advertise.brands');

// noon ads style advertiser landing — mirrors advertise.noon.com/{locale}/advertisers
Route::get('/{country}/advertise/advertisers', [LandingController::class, 'advertiseAdvertisers'])->name('advertise.advertisers');

// noon ads style product ads landing — mirrors advertise.noon.com/{locale}/product
Route::get('/{country}/advertise/product', [LandingController::class, 'advertiseProduct'])->name('advertise.product');

// noon ads style display ads landing — mirrors advertise.noon.com/{locale}/display
Route::get('/{country}/advertise/display', [LandingController::class, 'advertiseDisplay'])->name('advertise.display');

// noon ads style contact form — mirrors advertise.noon.com/{locale}/request
Route::get('/{country}/advertise/request', [LandingController::class, 'advertiseRequest'])->name('advertise.request');
Route::post('/{country}/advertise/request', [AdvertiseRequestController::class, 'store'])->name('advertise.request.store');

// noon ads style Knowledge Hub — mirrors adsupport.noon.com/en/*
Route::prefix('{country}/adsupport')->name('adsupport.')->group(function () {
    Route::get('/', [AdSupportController::class, 'index'])->name('index');
    Route::get('/collections/{collection}', [AdSupportController::class, 'collection'])->name('collections.show');
    Route::get('/articles/{article}', [AdSupportController::class, 'article'])->name('articles.show');
});

// Multi-step vendor registration
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::get('/register/success', [RegistrationController::class, 'success'])->name('register.success');
Route::post('/register/step/{step}', [RegistrationController::class, 'storeStep'])->where('step', '[1-3]')->name('register.step');
Route::get('/register/check-slug', [RegistrationController::class, 'checkSlug'])->name('register.check-slug');
Route::get('/register/cities', [RegistrationController::class, 'cities'])->name('register.cities');
Route::get('/register/document-requirements', [RegistrationController::class, 'documentRequirements'])->name('register.document-requirements');
Route::post('/register/upload', [RegistrationController::class, 'uploadDocument'])->name('register.upload');
Route::delete('/register/document', [RegistrationController::class, 'removeDocument'])->name('register.document.remove');
Route::post('/register/complete', [RegistrationController::class, 'complete'])->name('register.complete');

// Blog
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::post('/posts/{post}/increment-views', [BlogController::class, 'incrementViews'])->name('posts.increment-views');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

// Language switcher
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ar', 'en'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back()->withFragment('');
})->name('language');
