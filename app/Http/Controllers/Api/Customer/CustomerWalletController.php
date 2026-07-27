<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\GiftCardAlreadyRedeemedException;
use App\Exceptions\GiftCardCurrencyMismatchException;
use App\Exceptions\GiftCardExpiredException;
use App\Exceptions\GiftCardNotFoundException;
use App\Exceptions\InvalidGiftCardPinException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\RedeemGiftCardRequest;
use App\Http\Responses\ApiResponse;
use App\Models\GiftCard;
use App\Models\Order;
use App\Services\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerWalletController extends Controller
{
    public function __construct(private readonly GiftCardService $giftCardService)
    {
    }

    public function balance(Request $request): JsonResponse
    {
        $balance = $this->giftCardService->getWalletBalance(auth('customer')->user());

        return ApiResponse::success([
            'balance' => $balance['balance'],
            'currency_code' => $balance['currency_code'],
        ]);
    }

    public function redeemGiftCard(RedeemGiftCardRequest $request): JsonResponse
    {
        try {
            $result = $this->giftCardService->redeemByCode(
                auth('customer')->user(),
                $request->string('code')->value(),
                $request->string('pin')->value(),
            );
        } catch (GiftCardNotFoundException|InvalidGiftCardPinException|GiftCardAlreadyRedeemedException|GiftCardExpiredException|GiftCardCurrencyMismatchException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success([
            'new_balance' => $result['new_balance'],
            'credited_amount' => $result['credited_amount'],
            'currency_code' => $result['currency_code'],
        ], 'Gift card redeemed successfully.');
    }

    public function transactions(Request $request): JsonResponse
    {
        $paginator = $this->giftCardService->getTransactionHistory(auth('customer')->user());

        $items = $paginator->getCollection()->map(function ($transaction) {
            $item = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'direction' => $transaction->direction,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'currency_code' => $transaction->currency_code,
                'description' => $transaction->description,
                'note' => $transaction->note,
                'created_at' => $transaction->created_at,
                'order' => null,
                'gift_card' => null,
            ];

            if ($transaction->reference_type === Order::class && $transaction->reference_id) {
                $order = Order::select(['id', 'order_number', 'total', 'currency', 'status', 'placed_at'])
                    ->find($transaction->reference_id);

                if ($order) {
                    $item['order'] = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                        'currency' => $order->currency,
                        'status' => $order->status,
                        'placed_at' => $order->placed_at,
                    ];
                }
            }

            if ($transaction->reference_type === GiftCard::class && $transaction->reference_id) {
                $card = GiftCard::select(['id', 'code', 'amount', 'currency_code', 'redeemed_at'])
                    ->find($transaction->reference_id);

                if ($card) {
                    $item['gift_card'] = [
                        'id' => $card->id,
                        'code' => $card->code,
                        'amount' => $card->amount,
                        'currency' => $card->currency_code,
                        'redeemed_at' => $card->redeemed_at,
                    ];
                }
            }

            return $item;
        });

        return ApiResponse::success([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
