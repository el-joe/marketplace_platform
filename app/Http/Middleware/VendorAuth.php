<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('vendor')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('partner.login')
                ->with('error', 'Please sign in to access the vendor panel.');
        }

        return $next($request);
    }
}
