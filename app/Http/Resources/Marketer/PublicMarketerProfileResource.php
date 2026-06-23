<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Explicit allow-list — any column not listed here is safe-by-default excluded.
 * NEVER expose: commission_rate, total_earnings_cents, bank_*, referral_code,
 * total_clicks, total_conversions, status, admin columns, or any internal field.
 */
class PublicMarketerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'              => $this->name,
            'type'              => $this->type,
            'niche'             => $this->niche,
            'bio'               => $this->bio,
            'profile_photo_url' => $this->profile_photo_path
                ? asset('storage/' . $this->profile_photo_path)
                : null,
            'banner_url'        => $this->profile_banner_path
                ? asset('storage/' . $this->profile_banner_path)
                : null,
            'profile_video_url' => $this->profile_video_url,
            'social_links'      => array_filter([
                'instagram' => $this->social_instagram,
                'tiktok'    => $this->social_tiktok,
                'youtube'   => $this->social_youtube,
                'twitter'   => $this->social_twitter,
                'facebook'  => $this->social_facebook,
            ]),
            'country_flag'      => $this->whenLoaded('country', fn () => $this->country->flag ?? null),
            'featured_products' => $this->when(
                isset($this->resource->featured_products),
                fn () => $this->resource->featured_products
            ),
        ];
    }
}
