<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\GiftCardBalanceResource;
use App\Http\Resources\Customer\GiftCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\GiftCard;
use App\Services\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GiftCardController extends Controller
{
    public function __construct(private readonly GiftCardService $giftCardService) {}

    public function checkBalance(Request $request, string $country): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $giftCard = $this->giftCardService->checkBalance($validated['code']);

        if (! $giftCard) {
            return ApiResponse::error('Gift card not found or inactive.', [], 404);
        }

        return ApiResponse::success(new GiftCardBalanceResource($giftCard));
    }

    public function myCodes(Request $request, string $country): JsonResponse
    {
        $customer = auth('customer')->user();

        $giftCards = GiftCard::where('purchased_by_customer_id', $customer->id)
            ->orWhereHas('transactions', fn ($q) => $q->where('performed_by_customer_id', $customer->id))
            ->orderByDesc('created_at')
            ->paginate(15);

        return ApiResponse::paginated($giftCards, GiftCardResource::class);
    }

    public function purchase(Request $request, string $country): JsonResponse
    {
        $countryModel = $request->attributes->get('country');

        $activeCurrencies = $countryModel
            ? \App\Models\Currency::where('is_active', true)->pluck('code')->all()
            : [];

        $validated = $request->validate([
            'denomination' => ['required', 'integer', Rule::in([5000, 10000, 25000, 50000, 100000])],
            'currency' => ['required', 'string', Rule::in($activeCurrencies)],
            'recipient_email' => ['required_without:recipient_phone', 'nullable', 'email'],
            'recipient_phone' => ['required_without:recipient_email', 'nullable', 'string', 'max:30'],
            'recipient_name' => ['required', 'string', 'min:2', 'max:100'],
            'personal_message' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $customer = auth('customer')->user();

        $giftCard = $this->giftCardService->purchase($customer, $validated);

        return ApiResponse::success(new GiftCardResource($giftCard, true), 'Gift card purchased successfully', 201);
    }
}
