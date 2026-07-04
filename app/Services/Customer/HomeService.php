<?php

namespace App\Services\Customer;

use App\Models\Category;
use App\Models\Country;
use App\Models\CountryCategory;
use App\Models\Customer;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductCountrySetting;
use App\Services\Shared\PageBuilderService;
use Illuminate\Support\Carbon;

class HomeService
{
    private const BUY_BOX_ORDER = [
        'express_fbn' => 0,
        'merchant_fbp' => 1,
        'marketplace' => 2,
    ];

    public function __construct(
        private readonly PageRendererService $pageRenderer,
        private readonly NavigationService $navigation,
        private readonly PageBuilderService $pageBuilder,
        private readonly ListingQueryService $listingQuery,
    ) {}

    public function getHomeData(
        Country $country,
        ?Customer $customer,
        string $sessionId,
        string $deviceTarget = 'all',
        string $audience = 'guest',
    ): array {
        $page = $this->pageRenderer->render('home', null, $country, $customer, $sessionId);
        $nav  = $this->navigation->getTree($country);

        $wishlistProductIds = $customer
            ? $this->listingQuery->wishlistProductIds($customer->id)
            : [];

        return [
            'page' => $page,
            'nav' => $nav,
            'flash_sale_banner' => $this->activeFlashSaleBanner($country),
            'page_builder' => $this->pageBuilder->resolve($country, 'home', null, $deviceTarget, $audience),
            'sections' => $this->buildSections($country, $wishlistProductIds),
            'meta' => [
                'country_code' => strtolower($country->iso_code_2),
                'currency' => $country->currency_code,
                'locale' => $country->default_locale ?? app()->getLocale(),
            ],
        ];
    }

    private function activeFlashSaleBanner(Country $country): ?array
    {
        $sale = $this->activeFlashSale($country);

        if (! $sale) {
            return null;
        }

        return [
            'id'       => $sale->id,
            'name'     => $sale->name_en,
            'name_ar'  => $sale->name_ar,
            'ends_at'  => $sale->sale_ends_at,
        ];
    }

    private function buildSections(Country $country, array $wishlistProductIds): array
    {
        $sections = [
            [
                'section_type' => 'banner_carousel',
                'items' => [],
            ],
        ];

        $flashSaleSection = $this->flashSaleSection($country, $wishlistProductIds);
        if ($flashSaleSection !== null) {
            $sections[] = $flashSaleSection;
        }

        $sections[] = [
            'section_type' => 'featured_categories',
            'title' => 'Shop by Category',
            'items' => $this->featuredCategories($country),
        ];

        $sections[] = [
            'section_type' => 'product_carousel',
            'title' => 'Top Picks for You',
            'items' => $this->topPicks($country, $wishlistProductIds),
        ];

        return $sections;
    }

    private function activeFlashSale(Country $country): ?FlashSale
    {
        $now = Carbon::now();

        return FlashSale::where('country_id', $country->id)
            ->where('status', 'live')
            ->where('sale_starts_at', '<=', $now)
            ->where('sale_ends_at', '>', $now)
            ->orderBy('sale_ends_at')
            ->first();
    }

    private function flashSaleSection(Country $country, array $wishlistProductIds): ?array
    {
        $sale = $this->activeFlashSale($country);

        if (! $sale) {
            return null;
        }

        $submissions = $sale->submissions()
            ->where('status', 'live')
            ->with([
                'vendorListing.vendor',
                'vendorListing.primaryShippingMethod',
                'vendorListing.productVariant.product.images',
            ])
            ->get()
            ->filter(fn ($submission) => $submission->vendorListing && $submission->vendorListing->productVariant?->product);

        $ordered = $submissions
            ->sortBy(fn ($submission) => self::BUY_BOX_ORDER[$submission->vendorListing->global_system_type] ?? 3)
            ->take(8)
            ->values();

        $items = $ordered
            ->map(function ($submission) use ($country, $wishlistProductIds) {
                $listing = $submission->vendorListing;
                $product = $listing->productVariant->product;

                return $this->listingQuery->toCardShape(
                    $listing,
                    $product,
                    $country,
                    in_array($product->id, $wishlistProductIds, true),
                );
            })
            ->all();

        return [
            'section_type' => 'flash_sale',
            'title' => 'Flash Deals',
            'ends_at' => $sale->sale_ends_at?->toIso8601String(),
            'items' => $items,
        ];
    }

    private function featuredCategories(Country $country): array
    {
        $categoryIds = CountryCategory::where('country_id', $country->id)
            ->where('is_available', true)
            ->pluck('category_id');

        return Category::where('is_featured', true)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->with('files')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug' => $category->slug,
                'icon' => null,
                'image_url' => $category->files->first()?->url,
            ])
            ->values()
            ->all();
    }

    private function topPicks(Country $country, array $wishlistProductIds): array
    {
        $unavailableIds = ProductCountrySetting::where('country_id', $country->id)
            ->where('is_available', false)
            ->pluck('product_id');

        $products = Product::whereNotIn('id', $unavailableIds)
            ->with(['variants', 'images'])
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->limit(12)
            ->get();

        $buyBox = $this->listingQuery->getBuyBoxForProducts($products, $country);

        $pairs = $products
            ->map(fn (Product $product) => [$product, $buyBox[$product->id] ?? null])
            ->filter(fn (array $pair) => $pair[1] !== null)
            ->values();

        $sorted = $pairs
            ->sortBy(fn (array $pair) => self::BUY_BOX_ORDER[$pair[1]->global_system_type] ?? 3)
            ->values();

        return $sorted
            ->map(fn (array $pair) => $this->listingQuery->toCardShape(
                $pair[1],
                $pair[0],
                $country,
                in_array($pair[0]->id, $wishlistProductIds, true),
            ))
            ->all();
    }
}
