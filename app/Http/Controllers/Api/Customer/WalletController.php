<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\WalletOwnerType;
use App\Enums\WalletWithdrawalRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $wallets = Wallet::where('owner_type', WalletOwnerType::Customer)
            ->where('owner_id', $customer->id)
            ->get()
            ->map(fn (Wallet $wallet) => [
                'id' => $wallet->id,
                'balance' => $wallet->getRawOriginal('balance'),
                'pending_balance' => $wallet->getRawOriginal('pending_balance'),
                'currency' => $wallet->currency,
                'is_frozen' => $wallet->is_frozen,
                'frozen_reason' => $wallet->frozen_reason,
            ])
            ->values();

        return ApiResponse::success($wallets);
    }

    public function transactions(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $walletsQuery = Wallet::where('owner_type', WalletOwnerType::Customer)
            ->where('owner_id', $customer->id);

        if ($currency = $request->query('currency')) {
            $walletsQuery->where('currency', $currency);
        }

        $walletIds = $walletsQuery->pluck('id');

        $paginator = \App\Models\WalletTransaction::whereIn('wallet_id', $walletIds)
            ->orderByDesc('created_at')
            ->paginate(15);

        $items = collect($paginator->items())->map(fn ($transaction) => [
            'type' => $transaction->type,
            'amount' => $transaction->getRawOriginal('amount'),
            'balance_after' => $transaction->getRawOriginal('balance_after'),
            'source_type' => $transaction->source_type,
            'description' => $transaction->description,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ])->values();

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

    public function withdrawalRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'bank_name' => ['required', 'string', 'max:150'],
            'bank_iban' => ['required', 'string', 'max:50'],
        ]);

        $customer = auth('customer')->user();

        $wallet = Wallet::where('owner_type', WalletOwnerType::Customer)
            ->where('owner_id', $customer->id)
            ->where('currency', $data['currency'])
            ->first();

        if (! $wallet) {
            return ApiResponse::error('Wallet not found for this currency.', [], 404);
        }

        if ($wallet->is_frozen) {
            return ApiResponse::error('Your wallet is frozen and cannot request withdrawals.', [], 422);
        }

        if ($wallet->getRawOriginal('balance') < $data['amount']) {
            return ApiResponse::error('Insufficient wallet balance.', [], 422);
        }

        $hasPending = $wallet->withdrawalRequests()
            ->where('status', WalletWithdrawalRequestStatus::Pending)
            ->exists();

        if ($hasPending) {
            return ApiResponse::error('You already have a pending withdrawal request.', [], 422);
        }

        $withdrawalRequest = \App\Models\WalletWithdrawalRequest::create([
            'wallet_id' => $wallet->id,
            'amount' => $data['amount'],
            'currency' => $wallet->currency,
            'bank_name' => $data['bank_name'],
            'bank_iban' => $data['bank_iban'],
            'status' => WalletWithdrawalRequestStatus::Pending,
        ]);

        return ApiResponse::success([
            'id' => $withdrawalRequest->id,
            'amount' => $withdrawalRequest->getRawOriginal('amount'),
            'currency' => $withdrawalRequest->currency,
            'bank_name' => $withdrawalRequest->bank_name,
            'bank_iban' => $withdrawalRequest->bank_iban,
            'status' => $withdrawalRequest->status,
        ], 'Withdrawal request submitted successfully.', 201);
    }
}
