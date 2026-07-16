<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Private resource — returned only to the authenticated marketer.
 * Bank IBAN is masked as •••• XXXX (last 4 chars); never returned in full.
 */
class MarketerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'type'                  => $this->type?->value,
            'niche'                 => $this->niche,
            'bio'                   => $this->bio,
            'slug'                  => $this->boutiqaat_style_slug,
            'profile_photo_url'     => $this->profile_photo_path
                ? asset('storage/' . $this->profile_photo_path)
                : null,
            'banner_url'            => $this->profile_banner_path
                ? asset('storage/' . $this->profile_banner_path)
                : null,
            'profile_video_url'     => $this->profile_video_url,
            'social_links'          => [
                'instagram' => $this->social_instagram,
                'tiktok'    => $this->social_tiktok,
                'youtube'   => $this->social_youtube,
                'twitter'   => $this->social_twitter,
                'facebook'  => $this->social_facebook,
            ],
            'followers_count'       => $this->followers_count,
            'whatsapp_number'       => $this->whatsapp_number,
            'status'                => $this->status?->value,
            'referral_code'         => $this->referral_code,
            'total_clicks'          => $this->total_clicks,
            'total_conversions'     => $this->total_conversions,
            'total_earnings'  => $this->total_earnings,
            'country'               => $this->whenLoaded('country', fn () => [
                'id'   => $this->country->id,
                'name' => $this->country->name_en,
            ]),
            'current_tier'          => $this->whenLoaded('commissionTiers', function () {
                $active = $this->commissionTiers
                    ->where('is_active', 1)
                    ->sortByDesc('min_sales_count')
                    ->first(fn ($t) => $this->total_conversions >= $t->min_sales_count);
                return $active ? [
                    'tier_order'      => $active->tier_order,
                    'commission_rate' => $active->commission_rate,
                ] : null;
            }),
            'bank_account'          => $this->maskedBankAccount(),
            'last_login_at'         => $this->last_login_at?->toISOString(),
            'approved_at'           => $this->approved_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
        ];
    }

    private function maskedBankAccount(): ?array
    {
        if (!$this->bank_iban) {
            return null;
        }

        $iban = $this->bank_iban;
        $masked = '•••• ' . substr($iban, -4);

        return [
            'account_holder_name' => $this->bank_account_name,
            'bank_name'           => $this->bank_name,
            'iban'                => $masked,
        ];
    }
}
