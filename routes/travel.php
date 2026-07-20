<?php

use App\Http\Controllers\TravelAgencyPortal\AuthController;
use App\Http\Controllers\TravelAgencyPortal\BookingController;
use App\Http\Controllers\TravelAgencyPortal\CampaignController;
use App\Http\Controllers\TravelAgencyPortal\DashboardController;
use App\Http\Controllers\TravelAgencyPortal\PackageController;
use App\Http\Controllers\TravelAgencyPortal\PackageInquiryController;
use App\Http\Controllers\TravelAgencyPortal\ProfileController;
use App\Http\Controllers\TravelAgencyPortal\RoleController;
use App\Http\Controllers\TravelAgencyPortal\TeamController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ── Locale switcher (travel-agency subdomain) ─────────────────────────────
Route::middleware('web')
    ->post('/locale/switch', function (\Illuminate\Http\Request $request) {
        $locale = $request->input('locale');
        abort_unless(in_array($locale, config('app.available_locales', ['ar', 'en'])), 422);
        $request->session()->put([
            'locale'          => $locale,
            'locale_override' => $locale,
            'dir'             => $locale === 'ar' ? 'rtl' : 'ltr',
        ]);
        \Carbon\Carbon::setLocale($locale);
        \Illuminate\Support\Facades\App::setLocale($locale);
        return back();
    })->name('travel-agency.locale.switch');

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
                Route::post('/{package}/withdraw', [PackageController::class, 'withdraw'])->name('withdraw');
                Route::delete('/{package}/media/{media}', [PackageController::class, 'destroyMedia'])->name('media.destroy');
                Route::get('/{package}/contract', [PackageController::class, 'downloadContract'])->name('contract.download');
            });

            // Bookings
            Route::prefix('bookings')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::get('/create', [BookingController::class, 'create'])->name('create');
                Route::post('/', [BookingController::class, 'store'])->name('store');
                Route::get('/customer-search', [BookingController::class, 'customerSearch'])->name('customer-search');
                Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
                Route::patch('/{booking}/status', [BookingController::class, 'updateStatus'])->name('status');
            });

            // Campaign Offers
            Route::prefix('campaigns')->name('campaigns.')->group(function () {
                Route::get('/',                                   [CampaignController::class, 'index'])->name('index');
                Route::get('/create',                              [CampaignController::class, 'create'])->name('create');
                Route::post('/',                                   [CampaignController::class, 'store'])->name('store');
                Route::get('/marketers/search',                    [CampaignController::class, 'searchMarketers'])->name('marketers.search');
                Route::get('/packages/search',                     [CampaignController::class, 'searchPackages'])->name('packages.search');
                Route::get('/{offer}',                             [CampaignController::class, 'show'])->name('show');
                Route::post('/{offer}/submit',                     [CampaignController::class, 'submitForReview'])->name('submit');
                Route::post('/{offer}/pause',                      [CampaignController::class, 'pauseOffer'])->name('pause');
                Route::post('/{offer}/resume',                     [CampaignController::class, 'resumeOffer'])->name('resume');
                Route::delete('/{offer}',                          [CampaignController::class, 'destroy'])->name('destroy');
                Route::post('/{offer}/invite',                     [CampaignController::class, 'invite'])->name('invite');
                Route::delete('/invitations/{invitation}/revoke',  [CampaignController::class, 'revokeInvitation'])->name('invitations.revoke');
            });

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
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

            // Team management
            Route::prefix('team')->name('team.')->controller(TeamController::class)->group(function () {
                Route::get('/', 'index')->name('index')->middleware('travel_agency.can:team.view');
                Route::post('/', 'store')->name('store')->middleware('travel_agency.can:team.invite');
                Route::put('/{member}/role', 'updateRole')->name('update-role')->middleware('travel_agency.can:team.manage');
                Route::post('/{member}/toggle-active', 'toggleActive')->name('toggle-active')->middleware('travel_agency.can:team.manage');
                Route::delete('/{member}', 'destroy')->name('destroy')->middleware('travel_agency.can:team.manage');
            });

            // Roles & permissions
            Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function () {
                Route::get('/', 'index')->name('index')->middleware('travel_agency.can:roles.view');
                Route::get('/permissions', 'permissions')->name('permissions')->middleware('travel_agency.can:roles.view');
                Route::get('/create', 'create')->name('create')->middleware('travel_agency.can:roles.create');
                Route::post('/', 'store')->name('store')->middleware('travel_agency.can:roles.create');
                Route::get('/{role}/edit', 'edit')->name('edit')->middleware('travel_agency.can:roles.edit');
                Route::put('/{role}', 'update')->name('update')->middleware('travel_agency.can:roles.edit');
                Route::delete('/{role}', 'destroy')->name('destroy')->middleware('travel_agency.can:roles.delete');
            });
        });
    });
