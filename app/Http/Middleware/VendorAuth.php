<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class VendorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::setRequest($request)->parseToken()->authenticate();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            auth()->shouldUse('vendor');
        } catch (TokenExpiredException) {
            return response()->json(['success' => false, 'message' => 'Token expired.'], 401);
        } catch (JWTException) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
