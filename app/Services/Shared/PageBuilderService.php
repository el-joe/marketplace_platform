<?php

namespace App\Services\Shared;

use App\Http\Resources\Customer\ProductListResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\FlashSale;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PaidBannerBooking;
use App\Models\Product;
use App\Models\ProductCountrySetting;
use App\Services\Customer\ListingQueryService;
use App\Support\Bilingual;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PageBuilderService
{
    private const BUY_BOX_ORDER = [
        'express_fbn' => 0,
        'merchant_fbp' => 1,
        'marketplace' => 2,
    ];

    public function __construct(
        private readonly ListingQueryService $listingQuery,
    ) {
    }

    /**
     * Resolve the active published page for the given type + reference,
     * filtered by device_target, audience, A/B variant, and country_override.
     * Returns null if no matching published page exists — callers degrade gracefully.
     */
    public function resolve(
        Country $country,
        string $pageType,
        ?string $referenceId,
        string $deviceTarget = 'all',
        string $audience = 'guest',
    ): ?array {
        $page = Page::where('country_id', $country->id)
            ->where('page_type', $pageType)
            ->when(
                $referenceId !== null,
                fn($q) => $q->where('reference_id', $referenceId),
                fn($q) => $q->whereNull('reference_id'),
            )
            ->where('status', 'published')
            ->where(fn($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now()))
            ->orderByDesc('is_default')
            ->orderByDesc('published_at')
            ->first();

        if (!$page) {
            return null;
        }

        $now = now();

        $blocks = $page->blocks()
            ->where('is_visible', true)
            ->where(fn($q) => $q->whereNull('visible_from')->orWhere('visible_from', '<=', $now))
            ->where(fn($q) => $q->whereNull('visible_until')->orWhere('visible_until', '>', $now))
            ->whereIn('device_target', ['all', $deviceTarget])
            ->whereIn('audience', ['all', $audience])
            ->where(fn($q) => $q->whereNull('country_override')->orWhere('country_override', $country->id))
            ->orderBy('position')
            ->with([
                'slides' => fn($q) => $q->where('is_active', true)
                    ->where(fn($q2) => $q2->whereNull('visible_from')->orWhere('visible_from', '<=', $now))
                    ->where(fn($q2) => $q2->whereNull('visible_until')->orWhere('visible_until', '>', $now))
                    ->orderBy('position'),
                'adImageItems' => fn($q) => $q->where('is_active', true)->orderBy('position'),
                'blockProducts' => fn($q) => $q->orderBy('position'),
                'blockProducts.productVariant.product.images',
                'blockSellers' => fn($q) => $q->orderBy('position'),
                'blockSellers.seller',
                'blockCategories' => fn($q) => $q->orderBy('position'),
                'blockCategories.category',
            ])
            ->get();

        $bookings = PaidBannerBooking::whereIn('page_block_id', $blocks->pluck('id'))
            ->where('status', 'active')
            ->where('booked_from', '<=', $now)
            ->where('booked_until', '>=', $now)
            ->get()
            ->keyBy('page_block_id');

        return [
            'page_id' => $page->id,
            'page_type' => $page->page_type,
            'version' => $page->version,
            'seo' => [
                'title' => $page->seo_title,
                'description' => $page->seo_description,
                'og_image_url' => $page->og_image_url,
            ],
            'blocks' => $blocks
                ->map(fn(PageBlock $b) => $this->hydrateBlock($b, $bookings->get($b->id), $country))
                ->toArray(),
        ];
    }

    /**
     * Attach whichever block-specific relations have data (slides, ad images,
     * products, sellers, categories, paid banner) alongside the base block fields.
     */
    private function hydrateBlock(PageBlock $b, ?PaidBannerBooking $booking, Country $country): array
    {
        $data = [
            'id' => $b->id,
            'block_type' => $b->block_type,
            'position' => $b->position,
            'device_target' => $b->device_target,
            'config' => $b->config,
        ];

        if ($b->slides->isNotEmpty()) {
            $data['slides'] = $b->slides->map(fn($s) => [
                'id' => $s->id,
                'position' => $s->position,
                'desktop_url' => $s->desktop_url,
                'mobile_url' => $s->mobile_url,
                'title' => Bilingual::pair($s, 'title'),
                'subtitle' => Bilingual::pair($s, 'subtitle'),
                'cta_label' => Bilingual::pair($s, 'cta_label'),
                'cta_url' => $s->cta_url,
                'cta_open_new_tab' => $s->cta_open_new_tab,
                'text_color' => $s->text_color,
                'text_position' => $s->text_position,
                'overlay_opacity' => (float) $s->overlay_opacity,
                'link_type' => $s->link_type,
                'link_reference_id' => $s->link_reference_id,
            ])->values()->all();
        }

        if ($b->adImageItems->isNotEmpty()) {
            $data['items'] = $b->adImageItems->map(fn($i) => [
                'id' => $i->id,
                'position' => $i->position,
                'url' => $i->file_url,
                'title' => Bilingual::pair($i, 'title'),
                'link_url' => $i->link_url,
                'link_open_new_tab' => $i->link_open_new_tab,
                'alt_text' => Bilingual::pair($i, 'alt_text'),
                'show_title_overlay' => $i->show_title_overlay,
                'aspect_ratio' => $i->aspect_ratio,
            ])->values()->all();
        }

        if ($b->blockProducts->isNotEmpty()) {
            $data['products'] = $b->blockProducts
                ->filter(fn($bp) => $bp->productVariant?->product !== null)
                ->map(fn($bp) => (new ProductListResource($bp->productVariant->product))->toArray(request()))
                ->values()
                ->all();
        }

        if ($b->blockSellers->isNotEmpty()) {
            $data['sellers'] = $b->blockSellers
                ->filter(fn($bs) => $bs->seller !== null)
                ->map(fn($bs) => [
                    'id' => $bs->seller->id,
                    'store_name' => $bs->seller->store_name,
                    'store_slug' => $bs->seller->store_slug,
                    'avatar' => $bs->seller->avatar,
                    'rating_avg' => (float) $bs->seller->store_rating_avg,
                    'rating_count' => (int) $bs->seller->store_rating_count,
                ])
                ->values()
                ->all();
        }

        if ($b->blockCategories->isNotEmpty()) {
            $data['categories'] = $b->blockCategories
                ->filter(fn($bc) => $bc->category !== null)
                ->map(fn($bc) => [
                    'id' => $bc->category->id,
                    'name' => Bilingual::pair($bc->category, 'name'),
                    'slug' => $bc->category->slug,
                ])
                ->values()
                ->all();
        }

        if ($booking !== null) {
            $data['paid_banner'] = [
                'image_url' => $booking->image_url,
                'link_url' => $booking->link_url,
                'alt_text' => $booking->alt_text,
            ];
        }

        if (empty($data['products']) && in_array($b->block_type, ['product_row', 'flash_sale', 'deal_of_day'], true)) {
            $dynamicProducts = $this->resolveDynamicProducts($b, $country);
            if ($dynamicProducts !== null) {
                $data['products'] = $dynamicProducts;
            }
        }

        return $data;
    }

    /**
     * Resolve config['source'] (best_sellers/new_arrivals/top_rated/trending/category/
     * flash_sale/personalized) into an actual, buy-box-ordered product list. Returns
     * null when the block has no dynamic source to resolve (e.g. source=manual, which
     * is already covered by the eager-loaded blockProducts pivot above).
     *
     * Listings are resolved via ListingQueryService::getBuyBoxForProducts(), which
     * considers every vendor type (express_fbn/merchant_fbp/marketplace) and only
     * prioritizes admin (express_fbn) listings — it never excludes vendor listings.
     */
    private function resolveDynamicProducts(PageBlock $b, Country $country): ?array
    {
        $config = $b->config ?? [];

        if ($b->block_type === 'flash_sale') {
            return $this->productsFromFlashSale(
                $config['flash_sale_id'] ?? null,
                $country,
                max(1, (int) ($config['max_items_shown'] ?? 8)),
            );
        }

        $source = $config['source'] ?? 'best_sellers';

        if ($source === 'manual') {
            return null;
        }

        $maxProducts = max(1, min(50, (int) ($config['max_products'] ?? $config['max_items_shown'] ?? ($b->block_type === 'deal_of_day' ? 8 : 12))));

        if ($source === 'flash_sale') {
            return $this->productsFromFlashSale($config['flash_sale_id'] ?? null, $country, $maxProducts);
        }

        $query = Product::query()
            ->where('status', 'active')
            ->whereNotIn('id', ProductCountrySetting::where('country_id', $country->id)
                ->where('is_available', false)
                ->pluck('product_id'))
            ->with(['variants', 'images']);

        if (!empty($config['category_id'])) {
            $categoryIds = Category::where('id', $config['category_id'])
                ->orWhere('parent_id', $config['category_id'])
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        match ($source) {
            'new_arrivals' => $query->orderByDesc('published_at')->orderByDesc('created_at'),
            'top_rated' => $query->orderByRating(),
            'trending' => $query->orderByDesc('view_count')->orderByDesc('total_sold'),
            'category' => $query->orderByDesc('total_sold'),
            // No personalization engine yet; fall back to a rating/sales blend.
            'personalized' => $query->orderByRating()->orderByDesc('total_sold'),
            default => $query->orderByDesc('total_sold'), // best_sellers
        };

        $products = $query->limit($maxProducts)->get();

        return $this->productsToCards($products, $country);
    }

    private function productsToCards(Collection $products, Country $country): array
    {
        $buyBox = $this->listingQuery->getBuyBoxForProducts($products, $country);

        return $products
            ->map(fn(Product $p) => [$p, $buyBox[$p->id] ?? null])
            ->filter(fn(array $pair) => $pair[1] !== null)
            ->map(fn(array $pair) => $this->listingQuery->toCardShape($pair[1], $pair[0], $country))
            ->values()
            ->all();
    }

    private function productsFromFlashSale(?string $flashSaleId, Country $country, int $maxProducts): array
    {
        $countryCheck = function ($q) use ($country) {
            return $q->where('country_id', $country->id)->orWhereNull('country_id');
        };

        $sale = $flashSaleId
            ? FlashSale::where('id', $flashSaleId)->where($countryCheck)->first()
            : FlashSale::where($countryCheck)
                ->where('status', 'live')
                ->where('sale_starts_at', '<=', now())
                ->where('sale_ends_at', '>', now())
                ->orderBy('sale_ends_at')
                ->first();

        if (!$sale) {
            return [];
        }

        $submissions = $sale->submissions()
            ->whereIn('status', ['live', 'approved'])
            ->with([
                'vendorListing.vendor',
                'vendorListing.primaryShippingMethod',
                'vendorListing.productVariant.product.images',
            ])
            ->get()
            ->filter(fn($s) => $s->vendorListing && $s->vendorListing->productVariant?->product);

        return $submissions
            ->sortBy(fn($s) => self::BUY_BOX_ORDER[$s->vendorListing->global_system_type->value] ?? 3)
            ->take($maxProducts)
            ->map(fn($s) => $this->listingQuery->toCardShape(
                $s->vendorListing,
                $s->vendorListing->productVariant->product,
                $country,
            ))
            ->values()
            ->all();
    }

    /**
     * Detect mobile vs desktop from User-Agent header.
     */
    public function detectDevice(Request $request): string
    {
        $ua = strtolower($request->header('User-Agent', ''));

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
