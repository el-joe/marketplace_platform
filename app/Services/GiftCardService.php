<?php

namespace App\Services;

use App\Jobs\SendGiftCardNotificationJob;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
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
                    'denomination_cents' => $data['denomination_cents'],
                    'currency' => $data['currency'],
                    'balance_cents' => $data['denomination_cents'],
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
                    'amount_cents' => $giftCard->denomination_cents,
                    'balance_after_cents' => $giftCard->balance_cents,
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
                'amount_cents' => 0,
                'balance_after_cents' => $locked->balance_cents,
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
            'amount_cents' => 0,
            'balance_after_cents' => $giftCard->balance_cents,
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
            $newBalance = $locked->balance_cents + $delta;

            if ($newBalance < 0) {
                throw new \DomainException('Adjustment would result in a negative balance.');
            }

            $locked->update([
                'balance_cents' => $newBalance,
                'status' => $newBalance === 0 && $locked->status === 'active' ? 'redeemed' : $locked->status,
            ]);

            return GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'amount_cents' => $delta,
                'balance_after_cents' => $newBalance,
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
                throw new \DomainException('Gift card is not active.');
            }

            if (! $locked->expires_at || $locked->expires_at->isPast()) {
                throw new \DomainException('Gift card has expired.');
            }

            if ($locked->balance_cents < $amountCents) {
                throw new \DomainException('Gift card has insufficient balance.');
            }

            $newBalance = $locked->balance_cents - $amountCents;

            $locked->update([
                'balance_cents' => $newBalance,
                'status' => $newBalance === 0 ? 'redeemed' : $locked->status,
            ]);

            return GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'order_id' => $order->id,
                'amount_cents' => $amountCents,
                'balance_after_cents' => $newBalance,
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
            'denomination_cents' => $data['denomination_cents'],
            'currency' => $data['currency'],
            'balance_cents' => $data['denomination_cents'],
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
            'amount_cents' => $giftCard->denomination_cents,
            'balance_after_cents' => $giftCard->balance_cents,
            'type' => 'issuance',
            'performed_by_customer_id' => $customer->id,
            'notes' => 'Gift card purchased',
        ]);

        if ($giftCard->recipient_email) {
            SendGiftCardNotificationJob::dispatch($giftCard->id);
        }

        return $giftCard;
    }
}
