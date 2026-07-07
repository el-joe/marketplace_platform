<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Earnings\EarningsListRequest;
use App\Http\Resources\Marketer\ConversionResource;
use App\Http\Responses\ApiResponse;
use App\Services\Marketer\EarningsService;
use Illuminate\Http\JsonResponse;

class EarningsController extends Controller
{
    public function __construct(private readonly EarningsService $earningsService) {}

    public function index(EarningsListRequest $request): JsonResponse
    {
        $marketer  = auth('marketer_api')->user();
        $paginator = $this->earningsService->list($marketer, $request->validated());

        return ApiResponse::paginated($paginator, ConversionResource::class);
    }

    public function summary(): JsonResponse
    {
        $marketer = auth('marketer_api')->user();

        return ApiResponse::success($this->earningsService->summary($marketer));
    }

    public function show(string $conversionId): JsonResponse
    {
        $marketer   = auth('marketer_api')->user();
        $conversion = $this->earningsService->findConversion($marketer, $conversionId);

        if (!$conversion) {
            return ApiResponse::error('Conversion not found.', [], 404);
        }

        return ApiResponse::success(new ConversionResource($conversion));
    }
}
