<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TravelAgencyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('travel_agency')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('travel-agency.login');
        }

        $agency = auth()->guard('travel_agency')->user();

        if ($agency->status === 'suspended') {
            auth()->guard('travel_agency')->logout();

            return redirect()->route('travel-agency.login')
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        if ($agency->status === 'pending') {
            auth()->guard('travel_agency')->logout();

            return redirect()->route('travel-agency.login')
                ->withErrors(['email' => 'Your account is pending admin approval.']);
        }

        return $next($request);
    }
}
