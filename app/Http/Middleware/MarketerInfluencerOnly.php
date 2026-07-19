<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketerInfluencerOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $marketer = auth()->guard('marketer')->user();

        abort_if(! $marketer || $marketer->isAffiliate(), 403);

        return $next($request);
    }
}
