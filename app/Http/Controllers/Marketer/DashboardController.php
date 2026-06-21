<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketer\MarketerDashboardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Services\Marketer\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/dashboard
    public function index(): JsonResponse
    {
        $snapshot = $this->service->getSnapshot($this->marketer());

        return ApiResponse::success(new MarketerDashboardResource($snapshot));
    }
}
