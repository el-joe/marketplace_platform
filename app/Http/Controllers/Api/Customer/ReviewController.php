<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// TODO: implement flat POST /reviews + GET /reviews/mine.
// Country-scoped review store/update/helpful already exist in
// App\Http\Controllers\Customer\ReviewController.
class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return ApiResponse::error('Not implemented.', [], 501);
    }

    public function mine(Request $request): JsonResponse
    {
        return ApiResponse::error('Not implemented.', [], 501);
    }
}
