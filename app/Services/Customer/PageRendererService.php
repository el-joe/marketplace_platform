<?php

namespace App\Services\Customer;

use App\Enums\VendorListingStatus;
use App\Http\Resources\Customer\FlashSaleItemResource;
use App\Http\Resources\Customer\ProductListResource;
use App\Jobs\FlushBlockImpressionJob;
use App\Jobs\LogAbImpressionJob;
use App\Models\AbTest;
use App\Models\AdImageItem;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Country;
use App\Models\Customer;
use App\Models\FlashSale;
use App\Models\FlashSaleSubmission;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockCategory;
use App\Models\PageBlockProduct;
use App\Models\PageBlockSeller;
use App\Models\PageSection;
use App\Models\ProductCountrySetting;
use App\Models\ProductVariant;
use App\Models\VendorListing;
use App\Support\Bilingual;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Renders customer-facing pages by resolving a Page, its PageSections, and the
 * PageBlocks within them, applying visibility/audience/country/A-B filtering,
 * then hydrating each surviving block's data per its block_type.
 *
 * This is the highest read-volume endpoint in the system — every block's
 * hydrated payload is cached individually (see hydrateBlockCached()); the
 * assembled page response itself is NOT cached because audience/A-B state is
 * resolved per-visitor before hydration even starts.
 */
class PageRendererService
{
    /**
     * Known block_type strings. 16 distinct types total, counting
     * countdown_deal/countdown_timer and ad_images_2col/ad_images_4col as
     * separate types (both pairs share a hydrator method).
     */
    private const BLOCK_TYPES = [
        'hero_slider', 'countdown_deal', 'countdown_timer', 'video_banner',
        'product_row', 'flash_sale', 'deal_of_day', 'ad_images_2col',
        'ad_images_4col', 'full_banner', 'category_pills',
        'brand_strip', 'search_trends', 'text_block', 'divider', 'newsletter_signup',
    ];

    public function __construct(
        private readonly ProductQueryService $productQuery,
    ) {
    }

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

        if (!$page) {
            return [];
        }

        $identityKey = $customer ? 'c:' . $customer->id : 's:' . $sessionId;

        // Resolve A/B variants for all running tests on this page, ASYNC log the
        // impression so it never blocks the response.
        $runningTests = AbTest::where('page_id', $page->id)
            ->where('status', 'running')
            ->get();

        $chosenVariants = [];
        foreach ($runningTests as $test) {
            $variant = $this->resolveAbVariant($test, $identityKey);
            $chosenVariants[$test->id] = $variant;
            dispatch(new LogAbImpressionJob($test->id, $variant));
        }

        return $this->assemble($page, $country, $customer, $chosenVariants);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assemble(Page $page, Country $country, ?Customer $customer, array $chosenVariants): array
    {
        $sections = PageSection::where('page_id', $page->id)
            ->where('is_visible', true)
            ->orderBy('position')
            ->get();

        $allBlocks = PageBlock::where('page_id', $page->id)
            ->orderBy('position')
            ->get();

        $resolvedBlocks = $this->filterBlocks($allBlocks, $country, $customer, $chosenVariants);

        $sectionsData = [];

        foreach ($sections as $section) {
            $sectionBlocks = $resolvedBlocks->where('section_id', $section->id)->values();
            $hydrated = $this->hydrateBlocks($sectionBlocks, $page, $country, $customer);

            if ($hydrated->isNotEmpty()) {
                $sectionsData[] = [
                    'id' => $section->id,
                    'position' => $section->position,
                    'blocks' => $hydrated->all(),
                ];
            }
        }

        // Blocks not assigned to any section go in a virtual section.
        $unsectioned = $resolvedBlocks->whereNull('section_id')->values();
        if ($unsectioned->isNotEmpty()) {
            $hydrated = $this->hydrateBlocks($unsectioned, $page, $country, $customer);
            if ($hydrated->isNotEmpty()) {
                $sectionsData[] = [
                    'id' => null,
                    'position' => 0,
                    'blocks' => $hydrated->all(),
                ];
            }
        }

        return [
            'page' => [
                'type' => $page->page_type,
                'slug' => $page->slug,
                'version' => $page->version,
            ],
            'sections' => $sectionsData,
        ];
    }

    /**
     * Apply all block-level filtering rules BEFORE any hydration happens.
     * A block is skipped (removed from the collection) if any rule matches.
     */
    private function filterBlocks(Collection $blocks, Country $country, ?Customer $customer, array $chosenVariants): Collection
    {
        $now = now();

        return $blocks->filter(function (PageBlock $block) use ($now, $country, $customer, $chosenVariants) {
            if (!$block->is_visible) {
                return false;
            }
            if ($block->visible_from !== null && $block->visible_from->gt($now)) {
                return false;
            }
            if ($block->visible_until !== null && $block->visible_until->lt($now)) {
                return false;
            }
            if ($block->country_override !== null && $block->country_override !== $country->id) {
                return false;
            }
            // NOTE: the page_blocks.audience column uses 'guest' (singular), not the
            // spec's 'guests'. Semantics preserved: guest-only blocks hidden from
            // authenticated customers and vice versa.
            if ($block->audience === 'logged_in' && !$customer) {
                return false;
            }
            if ($block->audience === 'guest' && $customer) {
                return false;
            }
            if ($block->ab_test_id !== null) {
                if (!isset($chosenVariants[$block->ab_test_id])) {
                    return false;
                }
                if ($block->ab_variant !== $chosenVariants[$block->ab_test_id]) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    private function hydrateBlocks(Collection $blocks, Page $page, Country $country, ?Customer $customer): Collection
    {
        return $blocks
            ->map(function (PageBlock $block) use ($page, $country, $customer) {
                $cached = $this->hydrateBlockCached($block, $country, $customer);

                if ($cached === null) {
                    return null;
                }

                // Fire-and-forget impression tracking — never blocks the response.
                dispatch(new FlushBlockImpressionJob($block->id, $page->id, $country->id));

                return [
                    'id' => $block->id,
                    'type' => $block->block_type,
                    'position' => $block->position,
                    'device_target' => $block->device_target,
                    'audience' => $block->audience,
                    'cache_ttl_seconds' => $block->cache_ttl_seconds,
                    'data' => $cached,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Per-block cache wrapper. Cache key is scoped to (block, country) only —
     * none of the 17 hydrators below use $customer as a query filter, so the
     * cached payload is safe to share across every visitor who reaches this
     * block (audience/A-B gating already happened in filterBlocks(), before
     * we ever get here).
     */
    private function hydrateBlockCached(PageBlock $block, Country $country, ?Customer $customer): ?array
    {
        $ttl = max(1, (int) $block->cache_ttl_seconds);

        return Cache::remember(
            "page_block:{$block->id}:{$country->id}",
            $ttl,
            fn() => $this->hydrateBlock($block, $country, $customer),
        );
    }

    public function resolveAbVariant(AbTest $test, string $identityKey): string
    {
        $bucket = (crc32($identityKey . ':' . $test->id) & 0x7FFFFFFF) % 100;
        return $bucket < $test->traffic_split_pct ? 'b' : 'a';
    }

    // ─── Dispatcher ─────────────────────────────────────────────────────────

    public function hydrateBlock(PageBlock $block, Country $country, ?Customer $customer): ?array
    {
        return match ($block->block_type) {
            'hero_slider' => $this->hydrateHeroSlider($block),
            'countdown_deal', 'countdown_timer' => $this->hydrateCountdown($block),
            'video_banner' => $this->hydrateVideoBanner($block),
            'product_row' => $this->hydrateProductRow($block, $country),
            'flash_sale' => $this->hydrateFlashSale($block),
            'deal_of_day' => $this->hydrateDealOfDay($block, $country),
            'ad_images_2col' => $this->hydrateAdImages($block, 2),
            'ad_images_4col' => $this->hydrateAdImages($block, 4),
            'full_banner' => $this->hydrateFullBanner($block),
            'category_pills' => $this->hydrateCategoryPills($block, $country),
            'brand_strip' => $this->hydrateBrandStrip($block),
            'search_trends' => $this->hydrateSearchTrends($block, $country),
            'text_block' => $this->hydrateTextBlock($block),
            'divider' => $this->hydrateDivider($block),
            'newsletter_signup' => $this->hydrateNewsletterSignup($block),
            default => $block->config ?? [],
        };
    }

    // ─── 1. hero_slider ─────────────────────────────────────────────────────

    private function hydrateHeroSlider(PageBlock $block): array
    {
        $now = now();

        $slides = $block->slides()
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('visible_from')->orWhere('visible_from', '<=', $now))
            ->where(fn($q) => $q->whereNull('visible_until')->orWhere('visible_until', '>=', $now))
            ->orderBy('position')
            ->get();

        $cfg = $block->config ?? [];

        return [
            'config' => [
                'height_desktop' => $cfg['height_desktop'] ?? null,
                'autoplay_seconds' => $cfg['autoplay_seconds'] ?? null,
                'show_dots' => $cfg['show_dots'] ?? true,
                'show_arrows' => $cfg['show_arrows'] ?? true,
                'loop' => $cfg['loop'] ?? true,
                'transition' => $cfg['transition'] ?? 'slide',
            ],
            // desktop_file_id/mobile_file_id are intentionally NEVER exposed —
            // only their resolved URLs (see SliderSlide::getDesktopUrlAttribute()).
            'slides' => $slides->map(fn($s) => [
                'desktop_image_url' => $s->desktop_url,
                'mobile_image_url' => $s->mobile_url,
                'title' => Bilingual::pair($s, 'title'),
                'subtitle' => Bilingual::pair($s, 'subtitle'),
                'cta_label' => Bilingual::pair($s, 'cta_label'),
                'cta_url' => $s->cta_url,
                'cta_open_new_tab' => (bool) $s->cta_open_new_tab,
                'text_color' => $s->text_color,
                'text_position' => $s->text_position,
                'overlay_opacity' => (float) $s->overlay_opacity,
                'link_type' => $s->link_type,
                'link_reference_id' => $s->link_reference_id,
            ])->all(),
        ];
    }

    // ─── 2. countdown_deal / countdown_timer ───────────────────────────────

    private function hydrateCountdown(PageBlock $block): ?array
    {
        $cfg = $block->config ?? [];
        $endsAt = isset($cfg['ends_at']) ? \Illuminate\Support\Carbon::parse($cfg['ends_at']) : null;

        if ($endsAt === null || $endsAt->lt(now())) {
            return null;
        }

        return [
            'ends_at' => $endsAt->toIso8601String(),
            'seconds_remaining' => max(0, now()->diffInSeconds($endsAt, false)),
            'title' => $cfg['title'] ?? null,
            'colors' => $cfg['colors'] ?? null,
        ];
    }

    // ─── 3. video_banner ────────────────────────────────────────────────────

    private function hydrateVideoBanner(PageBlock $block): array
    {
        $cfg = $block->config ?? [];

        return [
            'video_url' => $cfg['video_url'] ?? null,
            'poster_url' => $cfg['poster_url'] ?? null,
            'autoplay' => (bool) ($cfg['autoplay'] ?? false),
            'muted' => (bool) ($cfg['muted'] ?? true),
        ];
    }

    // ─── 4. product_row ─────────────────────────────────────────────────────

    private function hydrateProductRow(PageBlock $block, Country $country): array
    {
        $cfg = $block->config ?? [];
        $source = $cfg['source'] ?? 'manual';
        $maxProducts = (int) ($cfg['max_products'] ?? 10);

        $products = match ($source) {
            'manual' => $this->productRowManual($block, $country),
            'flash_sale_products' => $this->productRowFlashSale($cfg, $maxProducts),
            default => $this->productRowQueried($source, $cfg, $country, $maxProducts),
        };

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'source' => $source,
            'items_per_row' => $cfg['items_per_row'] ?? 4,
            'scrollable_row' => (bool) ($cfg['scrollable_row'] ?? true),
            'show_view_all' => (bool) ($cfg['show_view_all'] ?? true),
            'show_ratings' => (bool) ($cfg['show_ratings'] ?? true),
            'show_discount_badge' => (bool) ($cfg['show_discount_badge'] ?? true),
            // CRITICAL: admin_share_pct / product_value_cents from
            // marketer_secret_promotions must NEVER be surfaced here, even for the
            // flash_sale_products source — those columns are never selected below.
            'products' => $products,
        ];
    }

    private function productRowManual(PageBlock $block, Country $country): array
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

        $unavailableIds = ProductCountrySetting::whereIn('product_id', $productIds)
            ->where('country_id', $country->id)
            ->where('is_available', false)
            ->pluck('product_id')
            ->flip()
            ->all();

        $variantIds = $blockProducts->pluck('product_variant_id')->all();
        $priceByVariant = $this->minPricesByVariant($variantIds, $country);

        return $blockProducts
            ->filter(fn($bp) => $bp->productVariant
                && $bp->productVariant->product_id
                && !isset($unavailableIds[$bp->productVariant->product_id]))
            ->map(function ($bp) use ($priceByVariant) {
                $product = $bp->productVariant->product;
                $price = $priceByVariant[$bp->product_variant_id] ?? null;
                $product->setAttribute('min_price', $price['min_price'] ?? null);
                $product->setAttribute('max_price', $price['max_price'] ?? null);
                $product->setAttribute('active_seller_count', $price['seller_count'] ?? 0);
                $product->setAttribute('total_stock', ($price['seller_count'] ?? 0) > 0 ? 1 : 0);
                $product->setAttribute('rating_avg', 0);
                $product->setAttribute('rating_count', 0);

                return (new ProductListResource($product))->toArray(request());
            })
            ->values()
            ->all();
    }

    private function productRowQueried(string $source, array $cfg, Country $country, int $maxProducts): array
    {
        $sort = match ($source) {
            'best_sellers' => 'best_selling',
            'new_arrivals' => 'newest',
            'top_rated' => 'rating',
            'featured' => 'relevance',
            default => 'relevance',
        };

        $categoryIds = isset($cfg['category_id']) ? [$cfg['category_id']] : null;

        $paginator = $this->productQuery->paginate(
            $country,
            ['sort' => $sort],
            $maxProducts,
            $categoryIds,
        );

        return $this->productQuery->buildProductsPayload($paginator, $country, 1, 'page_block')['items'];
    }

    private function productRowFlashSale(array $cfg, int $maxProducts): array
    {
        if (empty($cfg['flash_sale_id'])) {
            return [];
        }

        $submissions = FlashSaleSubmission::where('flash_sale_id', $cfg['flash_sale_id'])
            ->where('status', 'approved')
            ->with(['vendorListing.productVariant.product.images'])
            ->orderBy('created_at')
            ->limit($maxProducts)
            ->get();

        return $submissions
            ->filter(fn($s) => $s->vendorListing && $s->vendorListing->productVariant && $s->vendorListing->productVariant->product)
            ->map(function (FlashSaleSubmission $s) {
                $product = $s->vendorListing->productVariant->product;
                // Only non-sensitive, already-public pricing fields are used here —
                // flash_price/original_price on flash_sale_submissions, never the
                // marketer_secret_promotions table's admin_share_pct/product_value_cents.
                $product->setAttribute('min_price', $s->flash_price);
                $product->setAttribute('max_price', $s->flash_price);
                $product->setAttribute('active_seller_count', 1);
                $product->setAttribute('total_stock', $s->quantity_remaining > 0 ? 1 : 0);
                $product->setAttribute('rating_avg', 0);
                $product->setAttribute('rating_count', 0);

                return (new ProductListResource($product))->toArray(request());
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $variantIds
     * @return array<string, array{min_price:int,max_price:int,seller_count:int}>
     */
    private function minPricesByVariant(array $variantIds, Country $country): array
    {
        if (empty($variantIds)) {
            return [];
        }

        return VendorListing::query()
            ->whereIn('product_variant_id', $variantIds)
            ->where('country_id', $country->id)
            ->where('status', VendorListingStatus::Active->value)
            ->whereNull('deleted_at')
            ->selectRaw('product_variant_id, MIN(price) as min_price, MAX(price) as max_price, COUNT(*) as seller_count')
            ->groupBy('product_variant_id')
            ->get()
            ->keyBy('product_variant_id')
            ->map(fn($row) => [
                'min_price' => (int) $row->min_price,
                'max_price' => (int) $row->max_price,
                'seller_count' => (int) $row->seller_count,
            ])
            ->all();
    }

    // ─── 5. flash_sale ──────────────────────────────────────────────────────

    private function hydrateFlashSale(PageBlock $block): ?array
    {
        $cfg = $block->config ?? [];

        if (empty($cfg['flash_sale_id'])) {
            return null;
        }

        $flashSale = FlashSale::find($cfg['flash_sale_id']);

        if (!$flashSale) {
            return null;
        }

        $now = now();
        if (!($flashSale->sale_starts_at <= $now && $now <= $flashSale->sale_ends_at)) {
            return null;
        }

        $maxItems = (int) ($cfg['max_items_shown'] ?? 10);

        // NOTE: flash_sale_submissions has no `position` column in the actual
        // schema (spec assumed one) — ordering falls back to created_at.
        $submissions = FlashSaleSubmission::where('flash_sale_id', $flashSale->id)
            ->where('status', 'approved')
            ->with(['vendorListing.productVariant.product.images'])
            ->orderBy('created_at')
            ->limit($maxItems)
            ->get();

        $items = $submissions
            ->filter(fn($s) => $s->vendorListing)
            ->map(function (FlashSaleSubmission $s) use ($flashSale) {
                // quantity_remaining is read directly off the model — it's a MySQL
                // GENERATED VIRTUAL column (max_quantity_total - quantity_sold);
                // never recompute it in PHP.
                $remaining = (int) $s->quantity_remaining;

                return (new FlashSaleItemResource($s))->toArray(request()) + [
                    'seconds_left' => max(0, now()->diffInSeconds($flashSale->sale_ends_at, false)),
                    'is_sold_out' => $remaining <= 0,
                ];
            })
            ->values()
            ->all();

        return [
            'flash_sale' => [
                'name' => Bilingual::pair($flashSale, 'name'),
                'ends_at' => $flashSale->sale_ends_at->toIso8601String(),
                'seconds_left' => max(0, now()->diffInSeconds($flashSale->sale_ends_at, false)),
            ],
            'show_countdown' => (bool) ($cfg['show_countdown'] ?? true),
            'show_stock_bar' => (bool) ($cfg['show_stock_bar'] ?? true),
            'background_color' => $cfg['background_color'] ?? null,
            'badge_label' => $cfg['badge_label'] ?? null,
            'items' => $items,
        ];
    }

    // ─── 6. deal_of_day ─────────────────────────────────────────────────────

    private function hydrateDealOfDay(PageBlock $block, Country $country): ?array
    {
        $cfg = $block->config ?? [];

        if (empty($cfg['vendor_listing_id'])) {
            return null;
        }

        $listing = VendorListing::with('productVariant.product.images')
            ->find($cfg['vendor_listing_id']);

        if (!$listing || $listing->status !== VendorListingStatus::Active) {
            return null;
        }

        $endsAt = isset($cfg['ends_at']) ? \Illuminate\Support\Carbon::parse($cfg['ends_at']) : null;
        if ($endsAt === null || $endsAt->lt(now())) {
            return null;
        }

        $product = $listing->productVariant?->product;
        if (!$product) {
            return null;
        }

        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $comparePrice = (int) ($listing->compare_at_price ?? 0);
        $discountPct = $comparePrice > 0
            ? round((($comparePrice - $listing->price) / $comparePrice) * 100, 2)
            : null;

        return [
            'title' => $cfg['title'] ?? null,
            'product' => [
                'name' => Bilingual::pair($product, 'name'),
                'image' => $image?->url,
                'price_cents' => (int) $listing->price,
                'compare_at_price_cents' => $listing->compare_at_price !== null ? (int) $listing->compare_at_price : null,
                'discount_pct' => $discountPct,
                'slug' => $product->slug,
            ],
            'seconds_remaining' => max(0, now()->diffInSeconds($endsAt, false)),
        ];
    }

    // ─── 7. ad_images_2col / ad_images_4col ────────────────────────────────

    private function hydrateAdImages(PageBlock $block, int $limit): array
    {
        $cfg = $block->config ?? [];

        $items = AdImageItem::where('page_block_id', $block->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->limit($limit)
            ->get();

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'aspect_ratio' => $cfg['aspect_ratio'] ?? null,
            'items' => $items->map(fn($i) => [
                'image_url' => $i->file_url,
                'title' => Bilingual::pair($i, 'title'),
                'link_url' => $i->link_url,
                'link_open_new_tab' => (bool) $i->link_open_new_tab,
                'alt_text' => Bilingual::pair($i, 'alt_text'),
                'show_title_overlay' => (bool) $i->show_title_overlay,
            ])->all(),
        ];
    }

    // ─── 8. full_banner ─────────────────────────────────────────────────────

    private function hydrateFullBanner(PageBlock $block): ?array
    {
        $cfg = $block->config ?? [];

        if (empty($cfg['banner_id'])) {
            return null;
        }

        $banner = Banner::with('files')->find($cfg['banner_id']);

        if (!$banner) {
            return null;
        }

        $desktopImage = $banner->files->firstWhere('file_type', 'banner_desktop');
        $mobileImage = $banner->files->firstWhere('file_type', 'banner_mobile');

        if (!$desktopImage) {
            return null;
        }

        return [
            'image_url' => $desktopImage->full_path,
            'mobile_image_url' => $mobileImage?->full_path,
            'link_url' => $cfg['link_url'] ?? $banner->cta_url,
            'link_type' => $cfg['link_type'] ?? null,
            'link_reference_id' => $cfg['link_reference_id'] ?? null,
            'alt_text' => Bilingual::pair($banner, 'title'),
            'aspect_ratio' => $cfg['aspect_ratio'] ?? null,
            'mobile_aspect_ratio' => $cfg['mobile_aspect_ratio'] ?? null,
        ];
    }

    // ─── 10. category_pills ─────────────────────────────────────────────────

    private function hydrateCategoryPills(PageBlock $block, Country $country): array
    {
        $cfg = $block->config ?? [];
        $maxItems = (int) ($cfg['max_items'] ?? 10);
        $showProductCount = (bool) ($cfg['show_product_count'] ?? false);

        $manual = PageBlockCategory::where('page_block_id', $block->id)
            ->orderBy('position')
            ->with('category')
            ->get();

        if ($manual->isNotEmpty()) {
            $categories = $manual->pluck('category')->filter()->values();
        } else {
            $categories = Category::whereNull('parent_id')
                ->where('is_active', true)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->limit($maxItems)
                ->get();
        }

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'items' => $categories->map(function ($c) use ($showProductCount) {
                return [
                    'id' => $c->id,
                    'name' => Bilingual::pair($c, 'name'),
                    'slug' => $c->slug,
                    // categories table has no `icon` column in this schema — always null.
                    'icon' => null,
                    'product_count' => $showProductCount ? $this->cachedCategoryProductCount($c->id) : null,
                ];
            })->all(),
        ];
    }

    private function cachedCategoryProductCount(string $categoryId): int
    {
        return (int) $this->cacheRememberTagged(
            "category_product_count:{$categoryId}",
            600,
            ['categories'],
            fn() => (int) Category::where('id', $categoryId)->value('product_count'),
        );
    }

    // ─── 11. brand_strip ────────────────────────────────────────────────────

    private function hydrateBrandStrip(PageBlock $block): array
    {
        $cfg = $block->config ?? [];
        $maxItems = (int) ($cfg['max_items'] ?? 10);

        $blockSellers = PageBlockSeller::where('page_block_id', $block->id)
            ->orderBy('position')
            ->limit($maxItems)
            ->with('seller')
            ->get();

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'show_logo_only' => (bool) ($cfg['show_logo_only'] ?? false),
            'brands' => $blockSellers
                ->filter(fn($bs) => $bs->seller !== null)
                ->map(fn($bs) => [
                    'name' => $bs->seller->store_name,
                    'logo_url' => $bs->seller->avatar,
                    'store_slug' => $bs->seller->store_slug,
                ])
                ->values()
                ->all(),
        ];
    }

    // ─── 12. search_trends ──────────────────────────────────────────────────

    private function hydrateSearchTrends(PageBlock $block, Country $country): array
    {
        $cfg = $block->config ?? [];
        $maxTerms = (int) ($cfg['max_terms'] ?? 10);

        $terms = $this->cacheRememberTagged(
            "search_trends:{$country->id}:{$maxTerms}",
            1800,
            ['search_trends'],
            function () use ($country, $maxTerms) {
                // NOTE: spec assumed a `search_query` column — the actual search_logs
                // table stores this as `query`/`query_normalized`. Semantics preserved.
                return DB::table('search_logs')
                    ->where('country_id', $country->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('query_normalized')
                    ->whereRaw('LENGTH(query_normalized) > 2')
                    ->selectRaw('query_normalized, COUNT(*) as cnt')
                    ->groupBy('query_normalized')
                    ->orderByDesc('cnt')
                    ->limit($maxTerms)
                    ->pluck('query_normalized')
                    ->all();
            },
        );

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'terms' => $terms,
        ];
    }

    // ─── 13. text_block ─────────────────────────────────────────────────────

    private function hydrateTextBlock(PageBlock $block): array
    {
        $cfg = $block->config ?? [];

        return [
            'content_html' => [
                'ar' => $this->sanitizeHtml($cfg['content_html_ar'] ?? null),
                'en' => $this->sanitizeHtml($cfg['content_html_en'] ?? null),
            ],
            'text_align' => $cfg['text_align'] ?? 'left',
            'max_width' => $cfg['max_width'] ?? null,
        ];
    }

    /**
     * No HTML purifier package (e.g. mews/purifier) is installed in this project
     * (composer.json has no `purif`/`sanitiz` requirement). As a stopgap, strip
     * everything except a small text-formatting allowlist and drop dangerous
     * attributes (onclick=, javascript: hrefs handled by strip_tags removing all
     * tag attributes for allowed tags too — content is admin-authored but must
     * never execute in a customer's browser).
     * TODO: replace with mews/purifier (or ezyang/htmlpurifier) once installed —
     * strip_tags does not neutralize javascript: URLs inside attributes it keeps.
     */
    private function sanitizeHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><span><a><h1><h2><h3><h4>';
        $clean = strip_tags($html, $allowed);

        // Defense-in-depth: drop any remaining event-handler / javascript: attempts
        // that could survive inside the allowed tags above.
        $clean = preg_replace('/on\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);

        return $clean;
    }

    // ─── 14. divider ────────────────────────────────────────────────────────

    private function hydrateDivider(PageBlock $block): array
    {
        $cfg = $block->config ?? [];

        return [
            'style' => $cfg['style'] ?? 'solid',
            'color' => $cfg['color'] ?? null,
            'margin_top' => $cfg['margin_top'] ?? 0,
            'margin_bottom' => $cfg['margin_bottom'] ?? 0,
        ];
    }

    // ─── 15. newsletter_signup ──────────────────────────────────────────────

    private function hydrateNewsletterSignup(PageBlock $block): array
    {
        $cfg = $block->config ?? [];

        return [
            'title' => Bilingual::pairFromKeys($cfg, 'title_ar', 'title_en'),
            'subtitle' => Bilingual::pairFromKeys($cfg, 'subtitle_ar', 'subtitle_en'),
        ];
    }

    // ─── Cache helper ───────────────────────────────────────────────────────

    /**
     * Cache::tags() is not supported by the 'database' cache driver configured
     * in this project (CACHE_STORE=database) — only by taggable stores like
     * redis/memcached. Fall back to a plain (untagged) remember() when tags
     * aren't supported so this still works today; tags will kick in for free
     * once the cache store is switched to redis.
     */
    private function cacheRememberTagged(string $key, int $ttl, array $tags, \Closure $callback): mixed
    {
        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\TaggableStore) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }
}
