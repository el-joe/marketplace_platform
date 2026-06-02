<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('marketer')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('marketer.login');
        }

        $marketer = auth()->guard('marketer')->user();

        if ($marketer->status === 'suspended') {
            auth()->guard('marketer')->logout();
            return redirect()->route('marketer.login')
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        return $next($request);
    }
}
