<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SubdomainDetect::class,
        ]);

        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\AdminAuth::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
            'vendor.auth' => \App\Http\Middleware\VendorAuth::class,
            'vendor.active' => \App\Http\Middleware\VendorActive::class,
            'vendor.onboarded' => \App\Http\Middleware\VendorOnboarded::class,
            'vendor.locale' => \App\Http\Middleware\SetVendorLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
