<?php

namespace App\Services\Marketer;

use App\Enums\MarketerCampaignStatus;
use App\Enums\MarketerStatus;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use Illuminate\Support\Facades\Cache;

class PublicProfileService
{
    private const CACHE_TTL_SECONDS = 600; // 10 minutes

    public function getPublicProfile(string $slug): array
    {
        return Cache::remember("public_marketer_profile:{$slug}", self::CACHE_TTL_SECONDS, function () use ($slug) {
            $marketer = Marketer::with('country')
                ->where('boutiqaat_style_slug', $slug)
                ->where('status', MarketerStatus::Active)
                ->firstOrFail();

            return [
                // Explicit allow-list — internal fields are safe-by-default excluded
                'name'              => $marketer->name,
                'type'              => $marketer->type,
                'niche'             => $marketer->niche,
                'bio'               => $marketer->bio,
                'profile_photo_url' => $marketer->profile_photo_path
                    ? asset('storage/' . $marketer->profile_photo_path)
                    : null,
                'banner_url'        => $marketer->profile_banner_path
                    ? asset('storage/' . $marketer->profile_banner_path)
                    : null,
                'profile_video_url' => $marketer->profile_video_url,
                'social_links'      => array_filter([
                    'instagram' => $marketer->social_instagram,
                    'tiktok'    => $marketer->social_tiktok,
                    'youtube'   => $marketer->social_youtube,
                    'twitter'   => $marketer->social_twitter,
                    'facebook'  => $marketer->social_facebook,
                ]),
                'country_flag'      => $marketer->country?->flag ?? null,
                'featured_products' => $this->getFeaturedProducts($marketer),
            ];
        });
    }

    private function getFeaturedProducts(Marketer $marketer): array
    {
        $campaigns = MarketerCampaign::where('marketer_id', $marketer->id)
            ->where('status', MarketerCampaignStatus::Active)
            ->with(['products.vendorListing.productVariant.product'])
            ->get();

        $products = [];

        foreach ($campaigns as $campaign) {
            foreach ($campaign->products->sortBy('position') as $campaignProduct) {
                $listing = $campaignProduct->vendorListing;
                if (!$listing) {
                    continue;
                }

                $variant = $listing->productVariant ?? null;
                $product = $variant?->product ?? null;

                $products[] = [
                    // Only public-safe product fields
                    'product_name'  => $product?->name_en ?? null,
                    'image_url'     => $product?->main_image ?? null,
                    'price'         => $listing->price,
                    'currency'      => $listing->currency,
                    // Pre-embedded affiliate tracking link — no commission data exposed
                    'affiliate_url' => url('/r/' . $campaign->tracking_url_slug),
                ];
            }
        }

        return $products;
    }
}
