<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestCartToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Cart-Token');

        if ($token) {
            $request->attributes->set('guest_cart_token', $token);
        }

        return $next($request);
    }
}
