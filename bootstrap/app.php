<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api/customer')
                ->group(base_path('routes/api_customer.php'));

            Route::middleware('api')
                ->prefix('api/vendor')
                ->group(base_path('routes/api_vendor.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SubdomainDetect::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/payment/*',
        ]);

        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\AdminAuth::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
            'vendor.auth' => \App\Http\Middleware\VendorAuth::class,
            'vendor.active' => \App\Http\Middleware\VendorActive::class,
            'vendor.onboarded' => \App\Http\Middleware\VendorOnboarded::class,
            'vendor.locale' => \App\Http\Middleware\SetVendorLocale::class,
            'auth.delivery' => \App\Http\Middleware\DeliveryAuth::class,
            'auth.marketer' => \App\Http\Middleware\MarketerAuth::class,
            'marketer.active' => \App\Http\Middleware\MarketerAuth::class,
            'track.marketer.click' => \App\Http\Middleware\TrackMarketerClick::class,
            'auth.travel_agency' => \App\Http\Middleware\TravelAgencyAuth::class,
            'auth.carrier' => \App\Http\Middleware\ShippingCompanySupervisorAuth::class,
            'detect.country' => \App\Http\Middleware\DetectCountry::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
