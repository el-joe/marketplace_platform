<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string'],
        ]);

        $giftCard = GiftCard::where('code', $data['code'])
            ->where('status', 'active')
            ->where('currency', $data['currency'])
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $giftCard) {
            return ApiResponse::error('Gift card is invalid, expired, or not usable in this currency.', [], 422);
        }

        return ApiResponse::success([
            'valid' => true,
            'balance' => $giftCard->getRawOriginal('balance'),
            'currency' => $giftCard->currency,
            'recipient_name' => $giftCard->recipient_name,
            'expires_at' => $giftCard->expires_at?->toIso8601String(),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $giftCards = GiftCard::where('purchased_by_customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GiftCard $giftCard) => [
                'id' => $giftCard->id,
                'code' => $giftCard->code,
                'denomination' => $giftCard->getRawOriginal('denomination'),
                'balance' => $giftCard->getRawOriginal('balance'),
                'currency' => $giftCard->currency,
                'status' => $giftCard->status,
                'recipient_email' => $giftCard->recipient_email,
                'expires_at' => $giftCard->expires_at?->toIso8601String(),
            ])
            ->values();

        return ApiResponse::success($giftCards);
    }
}
