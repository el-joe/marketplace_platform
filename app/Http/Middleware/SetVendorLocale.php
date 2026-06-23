<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetVendorLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendorAdmin = Auth::guard('vendor')->user();

        if ($vendorAdmin && $vendorAdmin->vendor) {
            $countryLocale = $vendorAdmin->vendor->locale ?? 'ar';
            $locale = in_array($countryLocale, config('app.available_locales', ['ar', 'en']))
                ? $countryLocale
                : 'ar';

            app()->setLocale($locale);
            session(['locale' => $locale]);
        } else {
            $locale = session('locale', 'ar');
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
