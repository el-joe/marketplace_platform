<?php

namespace App\Services\Shared;

use App\Models\AdminListing;
use App\Models\Country;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\VendorListing;
use Illuminate\Support\Facades\Cache;

/**
 * Central cache management for the page builder system.
 *
 * Cache keys managed here:
 *   page_block:{blockId}:{countryId}    — PageRendererService per-block cache
 *   app_config_{countryId}              — AppConfigController home context config
 *   browse_page_blocks:{type}:{cId}:{nodeId} — BrowseService category page blocks
 *   home_page_builder:{countryId}       — future: if HomeService ever caches page_builder result
 */
class PageCacheService
{
    // ── Bust a single block across all countries ──────────────────────────────

    public function bustBlock(PageBlock $block): void
    {
        // The block is scoped to one page, which is scoped to one country.
        // Forget the exact key. If country_id is available on the page, use it;
        // otherwise forget by iterating active countries (rare, safe fallback).
        $countryId = $block->page?->country_id;

        if ($countryId) {
            Cache::forget("page_block:{$block->id}:{$countryId}");
        } else {
            // Fallback: bust across all active countries
            Country::where('is_active', true)
                ->pluck('id')
                ->each(fn ($cid) => Cache::forget("page_block:{$block->id}:{$cid}"));
        }
    }

    // ── Bust all blocks on a page + page-level keys ──────────────────────────

    public function bustPage(Page $page): void
    {
        $countryId = $page->country_id;

        // Bust every block's individual cache
        $page->blocks()->pluck('id')->each(function ($blockId) use ($countryId) {
            if ($countryId) {
                Cache::forget("page_block:{$blockId}:{$countryId}");
            }
        });

        // Bust app_config (home context mapping) for this country
        if ($countryId) {
            Cache::forget("app_config_{$countryId}");
        }

        // Bust browse page blocks cache for category/brand/vendor pages
        if ($page->page_type !== 'home' && $page->reference_id && $countryId) {
            Cache::forget("browse_page_blocks:{$page->page_type}:{$countryId}:{$page->reference_id}");
        }

        // Bust home page builder specifically if it's a home page
        if ($page->page_type === 'home' && $countryId) {
            Cache::forget("home_page_builder:{$countryId}");
        }
    }

    // ── Manual bust: all page caches for one country ─────────────────────────

    public function bustAllForCountry(string $countryId): void
    {
        // Bust app_config
        Cache::forget("app_config_{$countryId}");

        // Bust all page blocks for this country
        PageBlock::whereHas('page', fn ($q) => $q->where('country_id', $countryId))
            ->pluck('id')
            ->each(fn ($id) => Cache::forget("page_block:{$id}:{$countryId}"));

        // Bust browse page blocks (all page types, all nodes)
        // Use a version key approach to invalidate all at once
        $version = (int) Cache::get("page_cache_version:{$countryId}", 0) + 1;
        Cache::put("page_cache_version:{$countryId}", $version, 86400);
    }

    // ── Bust admin listing's page block references ────────────────────────────

    public function bustAdminListingBlocks(AdminListing $listing): void
    {
        // Find any page_block that references this admin listing in its config
        // (deal_of_day blocks with admin_listing_id, product_row blocks with manual picks)
        PageBlock::where('config->admin_listing_id', $listing->id)
            ->orWhereHas('blockProducts', fn ($q) =>
                $q->whereHas('productVariant', fn ($q2) =>
                    $q2->where('id', $listing->product_variant_id)
                )
            )
            ->get()
            ->each(fn ($block) => $this->bustBlock($block));
    }

    // ── Bust vendor listing's page block references ───────────────────────────

    public function bustVendorListingBlocks(VendorListing $listing): void
    {
        PageBlock::where('config->vendor_listing_id', $listing->id)
            ->orWhereHas('blockProducts', fn ($q) =>
                $q->whereHas('productVariant', fn ($q2) =>
                    $q2->where('id', $listing->product_variant_id)
                )
            )
            ->get()
            ->each(fn ($block) => $this->bustBlock($block));
    }
}
