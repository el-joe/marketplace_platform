<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketerApiActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = auth()->guard('marketer_api')->user();

        if ($marketer->status !== 'active') {
            auth()->guard('marketer_api')->logout();

            $message = match ($marketer->status) {
                'pending'   => 'Your account is pending admin approval.',
                'suspended' => 'Your account has been suspended. Please contact support.',
                'rejected'  => 'Your account application was not approved.',
                default     => 'Your account is not active.',
            };

            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return $next($request);
    }
}
