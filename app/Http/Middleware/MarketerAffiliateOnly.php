<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketerAffiliateOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $marketer = auth()->guard('marketer')->user();

        abort_if(! $marketer || $marketer->isInfluencer(), 403);

        return $next($request);
    }
}
