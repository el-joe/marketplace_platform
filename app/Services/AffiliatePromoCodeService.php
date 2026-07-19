<?php

namespace App\Services;

use App\Models\AffiliatePromoCode;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Marketer;
use App\Models\MarketerConversion;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffiliatePromoCodeService
{
    public function generateCode(Marketer $marketer): string
    {
        $initials = $this->initialsFor($marketer->name);

        do {
            $code = 'AFF-' . $initials . '-' . strtoupper(Str::random(6));
        } while ($this->codeExists($code));

        return $code;
    }

    public function codeExists(string $code): bool
    {
        return AffiliatePromoCode::where('code', $code)->exists()
            || Coupon::where('code', $code)->exists();
    }

    public function applyCode(string $code, Cart $cart): array
    {
        $promoCode = AffiliatePromoCode::where('code', $code)->first();

        if (!$promoCode) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Promo code not found.'];
        }

        if (!$promoCode->is_active) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Promo code is inactive.'];
        }

        if ($promoCode->isExpired() || $promoCode->valid_from > now()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Promo code is not currently valid.'];
        }

        if ($promoCode->hasReachedLimit()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Promo code has reached its usage limit.'];
        }

        if ($promoCode->min_order_amount !== null && $cart->subtotal < $promoCode->min_order_amount) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Order does not meet the minimum amount for this promo code.'];
        }

        $discount = match ($promoCode->discount_type->value ?? $promoCode->discount_type) {
            'percentage' => (int) round($cart->subtotal * ((float) $promoCode->discount_value / 100)),
            'fixed_amount' => min((int) $promoCode->discount_value, $cart->subtotal),
            'free_shipping' => (int) $cart->estimated_shipping,
            default => 0,
        };

        return [
            'valid' => true,
            'discount' => $discount,
            'message' => 'Promo code applied.',
            'promo_code_id' => $promoCode->id,
        ];
    }

    public function recordConversion(AffiliatePromoCode $code, Order $order, int $commissionAmount): void
    {
        DB::transaction(function () use ($code, $order, $commissionAmount) {
            $code->increment('times_used');
            $code->increment('total_revenue_generated', $order->total);
            $code->increment('total_commission_earned', $commissionAmount);

            MarketerConversion::create([
                'campaign_id' => $code->campaign_id,
                'marketer_id' => $code->marketer_id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'order_value' => $order->total,
                'commission_amount' => $commissionAmount,
                'currency' => $order->currency ?? $code->currency,
                'status' => 'pending',
                'attribution_model' => 'last_click',
                'affiliate_promo_code_id' => $code->id,
            ]);
        });
    }

    private function initialsFor(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        $initials = preg_replace('/[^A-Z0-9]/', '', $initials);

        return $initials !== '' ? substr($initials, 0, 4) : 'MK';
    }
}
