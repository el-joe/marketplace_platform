<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Marketer\PublicProfileService;
use Illuminate\Http\JsonResponse;

class PublicProfileController extends Controller
{
    public function __construct(private readonly PublicProfileService $service) {}

    // GET /api/public/v1/marketers/{slug}
    // Fully public — no auth guard. Aggressive rate-limit applied in route definition.
    // Cached 10 min per slug inside PublicProfileService::getPublicProfile().
    public function show(string $slug): JsonResponse
    {
        $profile = $this->service->getPublicProfile($slug);
        return ApiResponse::success($profile);
    }
}
