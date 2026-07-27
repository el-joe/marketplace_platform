<?php

namespace App\Services;

use App\Exceptions\GiftCardAlreadyRedeemedException;
use App\Exceptions\GiftCardCurrencyMismatchException;
use App\Exceptions\GiftCardExpiredException;
use App\Exceptions\GiftCardNotFoundException;
use App\Exceptions\InvalidGiftCardPinException;
use App\Jobs\SendGiftCardNotificationJob;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerWallet;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GiftCardService
{
    public const MAX_BATCH_SIZE = 100;

    /**
     * Admin-issued gift card(s). Returns the created cards (1 or many for a batch).
     *
     * @return \Illuminate\Support\Collection<int, GiftCard>
     */
    public function issueByAdmin(array $data, Admin $admin): \Illuminate\Support\Collection
    {
        $quantity = min((int) ($data['quantity'] ?? 1), self::MAX_BATCH_SIZE);
        $quantity = max($quantity, 1);

        return DB::transaction(function () use ($data, $admin, $quantity) {
            $cards = collect();

            for ($i = 0; $i < $quantity; $i++) {
                $giftCard = GiftCard::create([
                    'code' => $this->generateCode(),
                    'denomination' => $data['denomination'],
                    'currency' => $data['currency'],
                    'balance' => $data['denomination'],
                    'status' => 'active',
                    'recipient_email' => $data['recipient_email'] ?? null,
                    'recipient_phone' => $data['recipient_phone'] ?? null,
                    'recipient_name' => $data['recipient_name'] ?? null,
                    'personal_message' => $data['personal_message'] ?? null,
                    'created_by_admin_id' => $admin->id,
                    'activated_at' => now(),
                    'expires_at' => $data['expires_at'] ?? now()->addYear(),
                ]);

                GiftCardTransaction::create([
                    'gift_card_id' => $giftCard->id,
                    'amount' => $giftCard->denomination,
                    'balance_after' => $giftCard->balance,
                    'type' => 'issuance',
                    'performed_by_admin_id' => $admin->id,
                    'notes' => 'Issued by admin',
                ]);

                if ($giftCard->recipient_email) {
                    SendGiftCardNotificationJob::dispatch($giftCard->id);
                }

                $cards->push($giftCard);
            }

            return $cards;
        });
    }

    public function cancel(GiftCard $giftCard, Admin $admin): GiftCard
    {
        return DB::transaction(function () use ($giftCard, $admin) {
            $locked = GiftCard::where('id', $giftCard->id)->lockForUpdate()->first();

            $locked->update(['status' => 'cancelled']);

            GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'amount' => 0,
                'balance_after' => $locked->balance,
                'type' => 'admin_adjustment',
                'performed_by_admin_id' => $admin->id,
                'notes' => 'Cancelled by admin',
            ]);

            return $locked;
        });
    }

    public function extend(GiftCard $giftCard, \DateTimeInterface $expiresAt, Admin $admin): GiftCard
    {
        $giftCard->update(['expires_at' => $expiresAt]);

        GiftCardTransaction::create([
            'gift_card_id' => $giftCard->id,
            'amount' => 0,
            'balance_after' => $giftCard->balance,
            'type' => 'admin_adjustment',
            'performed_by_admin_id' => $admin->id,
            'notes' => 'Expiry extended to ' . $expiresAt->format('Y-m-d'),
        ]);

        return $giftCard;
    }

    public function adjustBalance(GiftCard $giftCard, int $amountCents, string $type, ?string $notes, Admin $admin): GiftCardTransaction
    {
        return DB::transaction(function () use ($giftCard, $amountCents, $type, $notes, $admin) {
            $locked = GiftCard::where('id', $giftCard->id)->lockForUpdate()->first();

            $delta = $type === 'subtract' ? -$amountCents : $amountCents;
            $newBalance = $locked->balance + $delta;

            if ($newBalance < 0) {
                throw new \DomainException('Adjustment would result in a negative balance.');
            }

            $locked->update([
                'balance' => $newBalance,
                'status' => $newBalance === 0 && $locked->status === 'active' ? 'redeemed' : $locked->status,
            ]);

            return GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'amount' => $delta,
                'balance_after' => $newBalance,
                'type' => 'admin_adjustment',
                'performed_by_admin_id' => $admin->id,
                'notes' => $notes,
            ]);
        });
    }

    public function checkBalance(string $code): ?GiftCard
    {
        return GiftCard::active()->where('code', $code)->first();
    }

    public function redeem(GiftCard $card, int $amountCents, Order $order, Customer $customer): GiftCardTransaction
    {
        return DB::transaction(function () use ($card, $amountCents, $order, $customer) {
            $locked = GiftCard::where('id', $card->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'active') {
                throw new \DomainException(__('common.exceptions.gift_card.not_active'));
            }

            if (! $locked->expires_at || $locked->expires_at->isPast()) {
                throw new \DomainException(__('common.exceptions.gift_card.expired'));
            }

            if ($locked->balance < $amountCents) {
                throw new \DomainException(__('common.exceptions.gift_card.insufficient_balance'));
            }

            $newBalance = $locked->balance - $amountCents;

            $locked->update([
                'balance' => $newBalance,
                'status' => $newBalance === 0 ? 'redeemed' : $locked->status,
            ]);

            return GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'order_id' => $order->id,
                'amount' => $amountCents,
                'balance_after' => $newBalance,
                'type' => 'redemption',
                'performed_by_customer_id' => $customer->id,
                'notes' => "Redeemed against order {$order->order_number}",
            ]);
        });
    }

    public function generateCode(): string
    {
        do {
            $code = 'NOON-'
                . strtoupper(Str::random(4)) . '-'
                . strtoupper(Str::random(4)) . '-'
                . strtoupper(Str::random(4));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }

    public function purchase(Customer $customer, array $data): GiftCard
    {
        $giftCard = GiftCard::create([
            'code' => $this->generateCode(),
            'denomination' => $data['denomination'],
            'currency' => $data['currency'],
            'balance' => $data['denomination'],
            'status' => 'active',
            'purchased_by_customer_id' => $customer->id,
            'recipient_email' => $data['recipient_email'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'recipient_name' => $data['recipient_name'],
            'personal_message' => $data['personal_message'] ?? null,
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        GiftCardTransaction::create([
            'gift_card_id' => $giftCard->id,
            'amount' => $giftCard->denomination,
            'balance_after' => $giftCard->balance,
            'type' => 'issuance',
            'performed_by_customer_id' => $customer->id,
            'notes' => 'Gift card purchased',
        ]);

        if ($giftCard->recipient_email) {
            SendGiftCardNotificationJob::dispatch($giftCard->id);
        }

        return $giftCard;
    }

    /**
     * Redeem a code+PIN gift card into the customer's wallet.
     */
    public function redeemByCode(Customer $customer, string $code, string $pin): array
    {
        $giftCard = GiftCard::where('code', strtoupper(trim($code)))
            ->lockForUpdate()
            ->first();

        if (! $giftCard) {
            throw new GiftCardNotFoundException();
        }

        if (! Hash::check($pin, $giftCard->pin_hash)) {
            throw new InvalidGiftCardPinException();
        }

        if (! $giftCard->is_redeemable) {
            if ($giftCard->status !== 'active') {
                throw new GiftCardAlreadyRedeemedException();
            }

            throw new GiftCardExpiredException();
        }

        if ($giftCard->currency_code !== $customer->country?->currency_code) {
            throw new GiftCardCurrencyMismatchException();
        }

        return DB::transaction(function () use ($giftCard, $customer) {
            $giftCard->update([
                'status' => 'redeemed',
                'redeemed_by_customer_id' => $customer->id,
                'redeemed_at' => now(),
            ]);

            $wallet = CustomerWallet::firstOrCreate(
                ['customer_id' => $customer->id],
                ['balance' => 0, 'currency_code' => $giftCard->currency_code]
            );

            $wallet->credit($giftCard->amount);
            $wallet->refresh();

            WalletTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'gift_card_redeem',
                'direction' => 'credit',
                'amount' => $giftCard->amount,
                'balance_after' => $wallet->balance,
                'currency_code' => $giftCard->currency_code,
                'reference_type' => GiftCard::class,
                'reference_id' => $giftCard->id,
            ]);

            return [
                'new_balance' => $wallet->balance,
                'credited_amount' => $giftCard->amount,
                'currency_code' => $giftCard->currency_code,
            ];
        });
    }

    public function getWalletBalance(Customer $customer): array
    {
        $wallet = CustomerWallet::firstOrCreate(
            ['customer_id' => $customer->id],
            ['balance' => 0, 'currency_code' => $customer->country?->currency_code]
        );

        return [
            'balance' => $wallet->balance,
            'currency_code' => $wallet->currency_code,
        ];
    }

    public function getTransactionHistory(Customer $customer, int $perPage = 20): LengthAwarePaginator
    {
        return WalletTransaction::where('customer_id', $customer->id)
            ->latest('created_at')
            ->paginate($perPage);
    }
}
