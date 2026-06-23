<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketer\PayoutDetailResource;
use App\Http\Resources\Marketer\PayoutResource;
use App\Http\Responses\ApiResponse;
use App\Services\Marketer\EarningsService;
use Illuminate\Http\JsonResponse;

class PayoutController extends Controller
{
    public function __construct(private readonly EarningsService $earningsService) {}

    public function index(): JsonResponse
    {
        $marketer  = auth('marketer')->user();
        $paginator = $this->earningsService->listPayouts($marketer);

        return ApiResponse::paginated($paginator, PayoutResource::class);
    }

    public function show(string $payoutId): JsonResponse
    {
        $marketer = auth('marketer')->user();
        $payout   = $this->earningsService->findPayout($marketer, $payoutId);

        if (!$payout) {
            return ApiResponse::error('Payout not found.', [], 404);
        }

        $conversions = $this->earningsService->payoutConversions($payout);
        $payout->setRelation('payoutConversions', $conversions);

        return ApiResponse::success(new PayoutDetailResource($payout));
    }

    public function invoice(string $payoutId): JsonResponse
    {
        $marketer = auth('marketer')->user();
        $payout   = $this->earningsService->findPayout($marketer, $payoutId);

        if (!$payout) {
            return ApiResponse::error('Payout not found.', [], 404);
        }

        // Invoice PDF generation is an admin-side operation; surface the URL if stored
        return ApiResponse::success([
            'payout_number' => $payout->payout_number,
            'invoice_url'   => null, // populated by admin when invoice is generated
        ]);
    }
}
