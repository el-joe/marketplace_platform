<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\VendorListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\ListingDetailController;
use App\Http\Responses\ApiResponse;
use App\Models\AdminProductListing;
use App\Models\Attribute;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VendorListing;
use App\Services\AppContextService;
use App\Services\VariantSlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductDetailController extends Controller
{
    public function __construct(
        private readonly VariantSlugService $variantSlugService,
        private readonly AppContextService $appContext,
    ) {
    }

    /**
     * $productSlug and $variantSlug arrive already resolved to models via the
     * Route::bind bindings registered in AppServiceProvider (parameter names
     * must match the route segment names for implicit substitution to apply).
     */
    public function show(Product $productSlug, ProductVariant $variantSlug, Request $request): JsonResponse
    {
        $product = $productSlug->loadMissing(['brand', 'category']);
        $variant = $variantSlug;

        $country = $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        $isNawyNow = $this->appContext->isNawyNow();

        [$listing, $listingType] = $this->resolveListing($product, $variant, $country, $isNawyNow, $request);

        return ApiResponse::success([
            'product' => $this->productShape($product),
            'variant' => $this->variantShape($variant),
            'listing' => $listing ? $this->listingShape($listing, $listingType, $country) : null,
            'attributes' => $this->buildAttributeMatrix($product, $variant, $country, $isNawyNow),
            'other_sellers' => $isNawyNow ? [] : $this->buildOtherSellers($variant, $country),
            'images' => $this->buildImages($product, $variant),
        ]);
    }

    public function resolveVariant(Product $productSlug, Request $request): JsonResponse
    {
        $product = $productSlug;

        $data = $request->validate([
            'current_variant_slug' => ['required', 'string'],
            'changed_attribute_id' => ['required', 'uuid'],
            'new_attribute_value_id' => ['required', 'uuid'],
            'country_id' => ['nullable', 'uuid'],
        ]);

        $country = $data['country_id'] ?? null
            ? Country::find($data['country_id'])
            : $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        $currentVariant = $this->variantSlugService->resolveVariant($product, $data['current_variant_slug']);

        if (!$currentVariant) {
            return ApiResponse::error('Variant not found', [], 404);
        }

        $currentAttributeValueIds = $currentVariant->variantAttributeValues()
            ->get()
            ->pluck('id', 'attribute_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $targetVariant = $this->variantSlugService->resolveVariantAfterAttributeChange(
            $product,
            $currentAttributeValueIds,
            $data['changed_attribute_id'],
            $data['new_attribute_value_id'],
        );

        if (!$targetVariant) {
            return ApiResponse::success([
                'available' => false,
                'target_variant_slug' => null,
                'target_listing_id' => null,
            ]);
        }

        $isNawyNow = $this->appContext->isNawyNow();

        $targetListing = $isNawyNow
            ? $this->variantSlugService->bestAdminListing($targetVariant, $country->id)
            : $this->variantSlugService->bestVendorListing($targetVariant, $country->id);

        if (!$targetListing) {
            return ApiResponse::success([
                'available' => false,
                'target_variant_slug' => null,
                'target_listing_id' => null,
            ]);
        }

        return ApiResponse::success([
            'available' => true,
            'target_variant_slug' => $targetVariant->slug,
            'target_listing_id' => $targetListing->id,
            'target_url' => "/products/{$product->slug}/{$targetVariant->slug}?listing={$targetListing->id}",
        ]);
    }

    /**
     * GET /products/{productSlug} — old-style URL without a variant slug.
     * Resolves the product's default variant and best listing, then 301s to
     * the canonical /products/{productSlug}/{variantSlug} URL. Falls back to
     * the legacy identifier-based listing lookup (UUID, SKU, listing ref) for
     * anything that isn't a bare product slug, since this and the pre-existing
     * catch-all identifier route occupy the same single-segment URL shape.
     */
    public function redirectLegacy(string $identifier, Request $request, ListingDetailController $listingDetailController): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $product = Product::where('slug', $identifier)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return $listingDetailController->show($request, $request->route('country'), $identifier);
        }

        $variant = $product->variants()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->first();

        if (!$variant) {
            return ApiResponse::error('Product not found.', [], 404);
        }

        $country = $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        $isNawyNow = $this->appContext->isNawyNow();

        $listing = $isNawyNow
            ? $this->variantSlugService->bestAdminListing($variant, $country->id)
            : $this->variantSlugService->bestVendorListing($variant, $country->id);

        $countrySegment = $request->route('country');
        $url = "/api/customer/v1/{$countrySegment}/products/{$product->slug}/{$variant->slug}"
            . ($listing ? "?listing={$listing->id}" : '');

        return redirect()->away($url, 301);
    }

    /**
     * @return array{0: VendorListing|AdminProductListing|null, 1: string|null}
     */
    private function resolveListing(
        Product $product,
        ProductVariant $variant,
        Country $country,
        bool $isNawyNow,
        Request $request,
    ): array {
        $listingId = $request->query('listing');

        if ($listingId) {
            $resolved = $this->variantSlugService->resolveByListingId($product, $listingId);

            if ($resolved && $resolved['variant']->id === $variant->id) {
                return [$resolved['listing'], $resolved['type']];
            }
        }

        if ($isNawyNow) {
            $listing = $this->variantSlugService->bestAdminListing($variant, $country->id);

            return [$listing, 'admin'];
        }

        $listing = $this->variantSlugService->bestVendorListing($variant, $country->id);

        return [$listing, 'vendor'];
    }

    private function productShape(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar,
            'description_en' => $product->description_en,
            'description_ar' => $product->description_ar,
            'short_desc_en' => $product->short_desc_en,
            'short_desc_ar' => $product->short_desc_ar,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name_en' => $product->brand->name_en,
                'name_ar' => $product->brand->name_ar,
                'logo' => $product->brand->logo_url,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name_en' => $product->category->name_en,
                'name_ar' => $product->category->name_ar,
            ] : null,
            'variants' => $product->variants()
                ->where('is_active', true)
                ->get()
                ->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'slug' => $variant->slug,
                ])->values()->all(),
        ];
    }

    private function variantShape(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'slug' => $variant->slug,
            'sku' => $variant->sku,
            'variant_name' => $variant->variant_name,
            'images' => $variant->images()
                ->orderBy('position')
                ->get()
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                    'is_primary' => (bool) $image->is_primary,
                ])->values()->all(),
        ];
    }

    private function listingShape(VendorListing|AdminProductListing $listing, string $listingType, Country $country): array
    {
        $sellerInfo = $listingType === 'vendor'
            ? [
                'seller_type' => 'vendor',
                'seller_name' => $listing->vendor?->store_name,
                'seller_slug' => $listing->vendor?->store_slug,
            ]
            : [
                'seller_type' => 'admin',
                'seller_name' => 'Nawy Now',
                'seller_slug' => null,
            ];

        return [
            'listing_id' => $listing->id,
            'listing_type' => $listingType,
            'price' => (int) $listing->price,
            'compare_at_price' => $listing->compare_at_price !== null ? (int) $listing->compare_at_price : null,
            'currency' => $listing->currency ?? $country->currency_code,
            'shipping_cost' => $listingType === 'admin' ? (int) $listing->shipping_cost : null,
            'fulfillment_type' => $listingType === 'admin' ? $listing->fulfillment_type : $listing->fulfillment_model,
            'payment_options' => $listingType === 'admin' ? $listing->payment_options : null,
            'rating_avg' => (float) $listing->rating_avg,
            'rating_count' => (int) $listing->rating_count,
            'status' => $listing->status?->value,
        ] + $sellerInfo;
    }

    /**
     * The per-attribute-value availability lookup below runs a resolveVariantAfterAttributeChange
     * + best-listing query for every value of every variant attribute, which gets expensive on
     * products with many attributes/values. That part depends only on product + country + selling
     * context (nawy_now vs. vendor), not on which variant is currently selected, so it's cached
     * separately (TTL 5 min) and invalidated whenever a listing's status/price changes
     * (see VendorListingObserver / AdminProductListingObserver). The "is_selected" / selected-value
     * fields below depend on the current variant and are computed fresh on every request.
     */
    private function buildAttributeMatrix(Product $product, ProductVariant $variant, Country $country, bool $isNawyNow): array
    {
        $cacheKey = sprintf(
            'product.%s.variant_matrix.%s.%s',
            $product->id,
            $country->id,
            $isNawyNow ? 'admin' : 'vendor',
        );

        $cachedAttributes = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($product, $country, $isNawyNow) {
            $attributes = Attribute::query()
                ->join('category_attributes', 'category_attributes.attribute_id', '=', 'attributes.id')
                ->where('category_attributes.category_id', $product->category_id)
                ->where('attributes.is_variant_attribute', true)
                ->orderBy('attributes.sort_order')
                ->select('attributes.*')
                ->with('values')
                ->get();

            return $attributes->map(function (Attribute $attribute) use ($product, $country, $isNawyNow) {
                $values = $attribute->values->map(function ($value) use ($attribute, $product, $country, $isNawyNow) {
                    $targetVariant = $this->variantSlugService->resolveVariantAfterAttributeChange(
                        $product,
                        [],
                        $attribute->id,
                        $value->id,
                    );

                    $targetListing = null;

                    if ($targetVariant) {
                        $targetListing = $isNawyNow
                            ? $this->variantSlugService->bestAdminListing($targetVariant, $country->id)
                            : $this->variantSlugService->bestVendorListing($targetVariant, $country->id);
                    }

                    return [
                        'attribute_value_id' => $value->id,
                        'value_en' => $value->value_en,
                        'value_ar' => $value->value_ar,
                        'slug' => $value->slug,
                        'code_hex' => $attribute->type?->value === 'color' ? $value->code_hex : null,
                        'is_available' => $targetVariant !== null && $targetListing !== null,
                        'target_variant_slug' => $targetVariant?->slug,
                        'target_listing_id' => $targetListing?->id,
                    ];
                })->values()->all();

                return [
                    'attribute_id' => $attribute->id,
                    'attribute_code' => $attribute->code,
                    'attribute_name_en' => $attribute->name_en,
                    'attribute_name_ar' => $attribute->name_ar,
                    'type' => $attribute->type?->value,
                    'values' => $values,
                ];
            })->values()->all();
        });

        $selectedValueIds = $variant->variantAttributeValues()
            ->get()
            ->pluck('id', 'attribute_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return array_map(function (array $attribute) use ($selectedValueIds) {
            $selectedValueId = $selectedValueIds[$attribute['attribute_id']] ?? null;

            $attribute['selected_value_id'] = $selectedValueId;
            $attribute['values'] = array_map(function (array $value) use ($selectedValueId) {
                $value['is_selected'] = $selectedValueId !== null && (string) $value['attribute_value_id'] === $selectedValueId;

                return $value;
            }, $attribute['values']);

            $selectedValue = collect($attribute['values'])->firstWhere('attribute_value_id', $selectedValueId);
            $attribute['selected_value_slug'] = $selectedValue['slug'] ?? null;

            return $attribute;
        }, $cachedAttributes);
    }

    private function buildOtherSellers(ProductVariant $variant, Country $country): array
    {
        return $variant->vendorListings()
            ->where('country_id', $country->id)
            ->where('status', VendorListingStatus::Active->value)
            ->with('vendor')
            ->orderBy('price')
            ->limit(10)
            ->get()
            ->map(fn (VendorListing $listing) => [
                'listing_id' => $listing->id,
                'vendor_name' => $listing->vendor?->store_name,
                'vendor_slug' => $listing->vendor?->store_slug,
                'price' => (int) $listing->price,
                'currency' => $listing->currency ?? $country->currency_code,
                'shipping_cost' => null,
                'rating_avg' => (float) $listing->rating_avg,
            ])->values()->all();
    }

    private function buildImages(Product $product, ProductVariant $variant): array
    {
        $variantImages = $variant->images()->orderBy('position')->get();
        $productImages = $product->images()->orderBy('position')->get();

        return $variantImages->concat($productImages)
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'is_primary' => (bool) $image->is_primary,
            ])->values()->all();
    }

    private function resolveCountry(Request $request): ?Country
    {
        $country = $request->attributes->get('country');

        if ($country instanceof Country) {
            return $country;
        }

        $customerId = auth('customer')->id();

        if ($customerId) {
            return auth('customer')->user()?->country;
        }

        return null;
    }
}
