<?php

namespace App\Http\Middleware;

use App\Enums\MarketerStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketerApiActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = auth()->guard('marketer_api')->user();

        if ($marketer->status !== MarketerStatus::Active) {
            auth()->guard('marketer_api')->logout();

            $message = match ($marketer->status) {
                MarketerStatus::Pending   => 'Your account is pending admin approval.',
                MarketerStatus::Suspended => 'Your account has been suspended. Please contact support.',
                MarketerStatus::Rejected  => 'Your account application was not approved.',
                default     => 'Your account is not active.',
            };

            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return $next($request);
    }
}
