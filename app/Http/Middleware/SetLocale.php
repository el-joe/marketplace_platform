<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Priority order for locale resolution:
     *  1. Authenticated user's stored preference (user->locale)
     *  2. Session value (admin panel country switch, etc.)
     *  3. URL segment/query param (?lang=ar)
     *  4. Accept-Language header
     *  5. App default
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($this->isSupported($locale)) {
            App::setLocale($locale);
            // Persist for next request
            if (!$request->session()->has('locale')) {
                $request->session()->put('locale', $locale);
            }
        }

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        // 1. Authenticated user preference
        $user = $request->user();
        if ($user && isset($user->locale) && $user->locale) {
            return (string) $user->locale;
        }

        // 2. Session
        if ($request->session()->has('locale')) {
            return (string) $request->session()->get('locale');
        }

        // 3. Query param or route segment
        if ($request->has('lang')) {
            $lang = (string) $request->query('lang');
            $request->session()->put('locale', $lang);
            return $lang;
        }

        // 4. Accept-Language header (first tag only)
        $accept = $request->header('Accept-Language', '');
        if ($accept) {
            $primary = explode(',', $accept)[0];
            $lang = strtolower(explode(';', $primary)[0]);
            $lang = substr($lang, 0, 2); // "en-US" → "en"
            if ($this->isSupported($lang)) {
                return $lang;
            }
        }

        // 5. App default
        return config('app.locale', 'en');
    }

    /**
     * Supported locales — extend as you add translation files.
     */
    protected function isSupported(string $locale): bool
    {
        $supported = config('app.supported_locales', ['en', 'ar']);
        return in_array($locale, (array) $supported, true);
    }
}
