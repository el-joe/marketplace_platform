<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Services\Marketer\TierService;
use Illuminate\Http\JsonResponse;

class TierController extends Controller
{
    public function __construct(private readonly TierService $service) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/tiers
    public function index(): JsonResponse
    {
        $tiers = $this->service->getAllTiers($this->marketer());

        return ApiResponse::success($tiers);
    }
}
