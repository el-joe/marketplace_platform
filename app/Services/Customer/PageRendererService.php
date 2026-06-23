<?php

namespace App\Services\Customer;

use App\Http\Resources\Customer\ProductListResource;
use App\Jobs\LogAbImpressionJob;
use App\Models\AbTest;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockCategory;
use App\Models\PageBlockProduct;
use App\Models\PageBlockSeller;
use App\Models\PageSection;
use App\Models\PaidBannerBooking;
use App\Models\ProductCountrySetting;
use App\Models\VendorListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PageRendererService
{
    public function render(
        string $type,
        ?string $slug,
        Country $country,
        ?Customer $customer,
        string $sessionId,
    ): array {
        $now = now();

        $page = Page::where('page_type', $type)
            ->where('status', 'published')
            ->where('is_default', true)
            ->where(fn($q) => $q->whereNull('country_id')->orWhere('country_id', $country->id))
            ->when($slug !== null, fn($q) => $q->where('slug', $slug))
            ->where(fn($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', $now))
            ->orderByRaw('CASE WHEN country_id IS NOT NULL THEN 0 ELSE 1 END')
            ->first();

        if (! $page) {
            return [];
        }

        $identityKey = $customer ? 'c:' . $customer->id : 's:' . $sessionId;

        // Resolve A/B variants for all running tests on this page.
        // Done before caching so impressions are logged per real request and
        // so the cache key captures which variant the visitor will see.
        $runningTests = AbTest::where('page_id', $page->id)
            ->where('status', 'running')
            ->get();

        $chosenVariants = [];
        foreach ($runningTests as $test) {
            $variant = $this->resolveAbVariant($test, $identityKey);
            $chosenVariants[$test->id] = $variant;
            dispatch(new LogAbImpressionJob($test->id, $variant));
        }

        // Cache key encodes the chosen variants so different visitors bucketed
        // into different variants get their own cached response.
        $variantSuffix = collect($chosenVariants)
            ->map(fn($v, $id) => "$id:$v")
            ->sort()
            ->implode('|');

        $cacheKey = implode(':', array_filter([
            'page_render',
            $country->id,
            $type,
            $slug ?? '__default',
            $variantSuffix ? md5($variantSuffix) : 'novariants',
        ]));

        return Cache::tags(['pages'])->remember($cacheKey, 300, function () use ($page, $country, $chosenVariants) {
            return $this->assemble($page, $country, $chosenVariants);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assemble(Page $page, Country $country, array $chosenVariants): array
    {
        $sections = PageSection::where('page_id', $page->id)
            ->where('is_visible', true)
            ->orderBy('position')
            ->get();

        $now = now();

        $allBlocks = PageBlock::where('page_id', $page->id)
            ->where('is_visible', true)
            ->where(fn($q) => $q->whereNull('visible_from')->orWhere('visible_from', '<=', $now))
            ->where(fn($q) => $q->whereNull('visible_until')->orWhere('visible_until', '>=', $now))
            ->orderBy('position')
            ->get();

        $resolvedBlocks = $this->filterAbBlocks($allBlocks, $chosenVariants);

        $sectionsData = [];

        foreach ($sections as $section) {
            $sectionBlocks = $resolvedBlocks->where('section_id', $section->id)->values();
            $hydrated = $this->hydrateBlocks($sectionBlocks, $country);

            if ($hydrated->isNotEmpty()) {
                $sectionsData[] = [
                    'id'       => $section->id,
                    'position' => $section->position,
                    'blocks'   => $hydrated->all(),
                ];
            }
        }

        // Blocks not assigned to any section go in a virtual section.
        $unsectioned = $resolvedBlocks->whereNull('section_id')->values();
        if ($unsectioned->isNotEmpty()) {
            $hydrated = $this->hydrateBlocks($unsectioned, $country);
            if ($hydrated->isNotEmpty()) {
                $sectionsData[] = [
                    'id'       => null,
                    'position' => 0,
                    'blocks'   => $hydrated->all(),
                ];
            }
        }

        return [
            'page' => [
                'type'    => $page->page_type,
                'slug'    => $page->slug,
                'version' => $page->version,
            ],
            'sections' => $sectionsData,
        ];
    }

    private function filterAbBlocks(Collection $blocks, array $chosenVariants): Collection
    {
        return $blocks->filter(function (PageBlock $block) use ($chosenVariants) {
            if ($block->ab_test_id === null) {
                return true;
            }
            // Skip blocks whose test is not in our running set.
            if (! isset($chosenVariants[$block->ab_test_id])) {
                return false;
            }
            return $block->ab_variant === $chosenVariants[$block->ab_test_id];
        })->values();
    }

    private function hydrateBlocks(Collection $blocks, Country $country): Collection
    {
        return $blocks
            ->map(fn(PageBlock $block) => $this->hydrateBlock($block, $country))
            ->filter()
            ->values();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function hydrateBlock(PageBlock $block, Country $country): ?array
    {
        $data = match ($block->block_type) {
            'hero_slider'    => $this->hydrateHeroSlider($block),
            'ad_grid'        => $this->hydrateAdGrid($block),
            'product_rail'   => $this->hydrateProductRail($block, $country),
            'seller_rail'    => $this->hydrateSellerRail($block, $country),
            'category_tiles' => $this->hydrateCategoryTiles($block),
            'paid_banner'    => $this->hydratePaidBanner($block),
            default          => $block->config ?? [],
        };

        if ($data === null) {
            return null;
        }

        return [
            'id'       => $block->id,
            'type'     => $block->block_type,
            'position' => $block->position,
            'data'     => $data,
        ];
    }

    public function resolveAbVariant(AbTest $test, string $identityKey): string
    {
        $bucket = (crc32($identityKey . ':' . $test->id) & 0x7FFFFFFF) % 100;
        return $bucket < $test->traffic_split_pct ? 'b' : 'a';
    }

    // ─── Block hydrators ──────────────────────────────────────────────────────

    private function hydrateHeroSlider(PageBlock $block): array
    {
        $now = now();

        $slides = $block->slides()
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('visible_from')->orWhere('visible_from', '<=', $now))
            ->where(fn($q) => $q->whereNull('visible_until')->orWhere('visible_until', '>=', $now))
            ->orderBy('position')
            ->get();

        return [
            'config' => $block->config ?? [],
            'slides' => $slides->map(fn($s) => [
                'id'               => $s->id,
                'position'         => $s->position,
                'desktop_url'      => $s->desktop_url,
                'mobile_url'       => $s->mobile_url,
                'title_en'         => $s->title_en,
                'title_ar'         => $s->title_ar,
                'subtitle_en'      => $s->subtitle_en,
                'subtitle_ar'      => $s->subtitle_ar,
                'cta_label_en'     => $s->cta_label_en,
                'cta_label_ar'     => $s->cta_label_ar,
                'cta_url'          => $s->cta_url,
                'cta_open_new_tab' => $s->cta_open_new_tab,
                'text_color'       => $s->text_color,
                'text_position'    => $s->text_position,
                'overlay_opacity'  => (float) $s->overlay_opacity,
                'link_type'        => $s->link_type,
                'link_reference_id' => $s->link_reference_id,
            ])->all(),
        ];
    }

    private function hydrateAdGrid(PageBlock $block): array
    {
        $items = $block->adImageItems()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        return [
            'config' => $block->config ?? [],
            'items'  => $items->map(fn($i) => [
                'id'                => $i->id,
                'position'          => $i->position,
                'url'               => $i->file_url,
                'title_en'          => $i->title_en,
                'title_ar'          => $i->title_ar,
                'link_url'          => $i->link_url,
                'link_open_new_tab' => $i->link_open_new_tab,
                'alt_text_en'       => $i->alt_text_en,
                'alt_text_ar'       => $i->alt_text_ar,
                'show_title_overlay' => $i->show_title_overlay,
                'aspect_ratio'      => $i->aspect_ratio,
            ])->all(),
        ];
    }

    private function hydrateProductRail(PageBlock $block, Country $country): array
    {
        $blockProducts = PageBlockProduct::where('page_block_id', $block->id)
            ->with(['productVariant.product.images'])
            ->orderBy('position')
            ->get();

        $productIds = $blockProducts
            ->map(fn($bp) => optional($bp->productVariant)->product_id)
            ->filter()
            ->unique()
            ->all();

        // Build set of product IDs explicitly marked unavailable in this country.
        $unavailableIds = ProductCountrySetting::whereIn('product_id', $productIds)
            ->where('country_id', $country->id)
            ->where('is_available', false)
            ->pluck('product_id')
            ->flip()
            ->all();

        $products = $blockProducts
            ->filter(fn($bp) => $bp->productVariant
                && $bp->productVariant->product_id
                && ! isset($unavailableIds[$bp->productVariant->product_id]))
            ->map(fn($bp) => $bp->productVariant->product)
            ->filter()
            ->values();

        return [
            'config'   => $block->config ?? [],
            'products' => $products->map(fn($p) => (new ProductListResource($p))->toArray(request()))->all(),
        ];
    }

    private function hydrateSellerRail(PageBlock $block, Country $country): array
    {
        $blockSellers = PageBlockSeller::where('page_block_id', $block->id)
            ->with('seller')
            ->orderBy('position')
            ->get();

        $sellers = $blockSellers
            ->filter(function ($bs) use ($country) {
                if (! $bs->seller) {
                    return false;
                }
                return VendorListing::where('vendor_id', $bs->seller->id)
                    ->where('country_id', $country->id)
                    ->where('status', 'active')
                    ->exists();
            })
            ->map(fn($bs) => [
                'id'         => $bs->seller->id,
                'store_name' => $bs->seller->store_name,
                'store_slug' => $bs->seller->store_slug,
                'avatar'     => $bs->seller->avatar,
                'rating_avg' => (float) $bs->seller->store_rating_avg,
                'rating_count' => (int) $bs->seller->store_rating_count,
            ])
            ->values();

        return [
            'config'  => $block->config ?? [],
            'sellers' => $sellers->all(),
        ];
    }

    private function hydrateCategoryTiles(PageBlock $block): array
    {
        $blockCategories = PageBlockCategory::where('page_block_id', $block->id)
            ->with('category')
            ->orderBy('position')
            ->get();

        return [
            'config'     => $block->config ?? [],
            'categories' => $blockCategories
                ->filter(fn($bc) => $bc->category !== null)
                ->map(fn($bc) => [
                    'id'      => $bc->category->id,
                    'name_en' => $bc->category->name_en,
                    'name_ar' => $bc->category->name_ar,
                    'slug'    => $bc->category->slug,
                ])
                ->values()
                ->all(),
        ];
    }

    private function hydratePaidBanner(PageBlock $block): ?array
    {
        $today = now()->toDateString();

        $booking = PaidBannerBooking::where('page_block_id', $block->id)
            ->where('status', 'active')
            ->where('booked_from', '<=', $today)
            ->where('booked_until', '>=', $today)
            ->first();

        if (! $booking) {
            return null;
        }

        return [
            'image_url' => $booking->image_url,
            'link_url'  => $booking->link_url,
            'alt_text'  => $booking->alt_text,
        ];
    }
}
