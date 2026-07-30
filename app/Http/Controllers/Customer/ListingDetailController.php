<?php

namespace App\Http\Controllers\Customer;

use App\Enums\GlobalSystemType;
use App\Enums\VendorListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\ListingDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\AdminProductListing;
use App\Models\Country;
use App\Models\VendorListing;
use App\Models\Wishlist;
use App\Models\Product;
use App\Services\AppContextService;
use App\Services\Customer\ListingIdentifierService;
use App\Services\Customer\ListingQueryService;
use App\Services\Customer\ProductDetailEnrichmentService;
use App\Services\Customer\ProductViewService;
use App\Services\Customer\ReviewService;
use App\Services\ListingShippingResolver;
use App\Services\WarrantyPlanService;
use App\Support\Bilingual;
use App\Support\Concerns\BuildsProductAttributeSelector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingDetailController extends Controller
{
    use BuildsProductAttributeSelector;

    public function __construct(
        private readonly ListingIdentifierService $identifiers,
        private readonly ListingShippingResolver $shipping,
        private readonly ProductViewService $viewService,
        private readonly ReviewService $reviewService,
        private readonly ProductDetailEnrichmentService $enrichment,
        private readonly WarrantyPlanService $warrantyPlanService,
        private readonly ListingQueryService $listings,
        private readonly AppContextService $appContext,
    ) {
    }

    public function show(Request $request, $country, string $identifier): JsonResponse
    {
        $country = $request->attributes->get('country');

        $listing = $this->resolveListing($identifier, $country);

        if (!$listing) {
            return ApiResponse::error('Listing not found or not available in this country.', [], 404);
        }

        $siblings = $listing instanceof VendorListing
            ? $this->identifiers->getSiblings($listing, $country)
            : ['same_variant' => collect(), 'other_variants' => collect()];
        $deliveryOptions = $this->shipping->resolveForListing($listing);

        $product = $listing->productVariant->product;

        $isWishlisted = false;
        if ($customerId = auth('customer')->id()) {
            $isWishlisted = Wishlist::where('customer_id', $customerId)
                ->where('vendor_listing_id', $listing->id)
                ->exists();
        }

        $this->viewService->logView(
            product: $product,
            country: $country,
            customerId: auth('customer')->id(),
            sessionId: $request->hasSession() ? $request->session()->getId() : '',
            source: $request->query('source', 'direct'),
            referrerUrl: $request->header('Referer'),
        );

        $customer = auth('customer')->user();

        $bestSellerBadge = $this->enrichment->getBestSellerBadge($product, $country);
        $coupons = $this->enrichment->getApplicableCoupons($product, $country, $customer, $listing instanceof VendorListing ? $listing : null);
        $paymentOptions = $this->enrichment->getPaymentOptions($country, $listing->price, $customer);
        $warrantyPlans = $this->warrantyPlanService->getPlansForProduct($product, $country->id, $country->currency_code);

        $reviews = $product->reviews()
            ->where('status', 'published')
            ->with([
                'vendorReply',
                'customer:id,name',
                'files',
                'vendorListing.vendor:id,store_name',
                'vendorListing.productVariant.variantAttributes.attribute',
                'vendorListing.productVariant.variantAttributes.attributeValue',
            ])
            ->orderByDesc('helpful_count')
            ->limit(5)
            ->get();

        $productAttributes = $this->productAttributesForListing($product, $listing->productVariant, $country);

        return ApiResponse::success(new ListingDetailResource([
            'listing' => $this->listingShape($listing, $country, $isWishlisted),
            'seller' => $this->sellerShape($listing),
            'delivery_options' => $deliveryOptions->map(fn($method) => $this->deliveryOptionShape($method))->values()->all(),
            'best_seller_badge' => $bestSellerBadge,
            'coupons' => $coupons,
            'payment_options' => $paymentOptions,
            'product' => $this->productShape($product, $listing),
            'product_attributes' => $productAttributes,
            'variant' => $this->variantShape($listing->productVariant),
            'other_sellers' => ($listing instanceof VendorListing ? $siblings['same_variant']->push($listing) : $siblings['same_variant'])
                ->sortBy('price')
                ->map(fn(VendorListing $l) => $this->otherSellerShape($l, $country, $l->id === $listing->id))
                ->values()
                ->all(),
            'other_variants' => $siblings['other_variants']->map(fn(VendorListing $l) => $this->otherVariantShape($l))->values()->all(),
            'reviews' => [
                'rating_avg' => (float) $listing->rating_avg,
                'rating_count' => (int) $listing->rating_count,
                'rating_percentage' => ($listing->rating_avg / 5) * 100,
                'rating_breakdown' => $this->reviewService->ratingBreakdown($product),
                'items' => $reviews->map(fn($review) => $this->reviewShape($review))->values()->all(),
            ],
            'frequently_bought_together' => $this->frequentlyBoughtTogetherShape($product, $listing, $country),
            'related_products' => $this->relatedProductsShape($product, $country),
            'warranty_plans' => $warrantyPlans,
        ]));
    }

    private function resolveListing(string $identifier, $country): VendorListing|AdminProductListing|null
    {
        if ($this->appContext->isNawyNow()) {
            return $this->identifiers->resolveAdminListing($identifier, $country->id);
        }

        if (str_contains($identifier, '--')) {
            $parsed = $this->identifiers->parseListingRef($identifier);

            if (!$parsed) {
                return null;
            }

            return VendorListing::whereHas('productVariant', fn($q) => $q->where('id', $parsed['product_variant_id']))
                ->where('id', 'like', $parsed['listing_id_prefix'] . '%')
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->with([
                    'productVariant.product.images',
                    'productVariant.product.category',
                    'productVariant.product.brand',
                    'productVariant.product.highlights',
                    'productVariant.product.specifications',
                    'productVariant.variantAttributes.attribute',
                    'productVariant.variantAttributes.attributeValue',
                    'vendor:id,store_name,store_rating_avg,store_rating_count',
                    'primaryShippingMethod',
                ])
                ->first();
        }

        $type = $this->identifiers->detectType($identifier);

        return $this->identifiers->resolve($identifier, $type, $country);
    }

    private function listingShape(VendorListing|AdminProductListing $listing, $country, bool $isWishlisted): array
    {
        if ($listing instanceof AdminProductListing) {
            return [
                'listing_id' => $listing->id,
                'listing_ref' => $this->identifiers->buildListingRef($listing),
                'vendor_sku' => $listing->platform_sku,
                'sku' => $listing->productVariant->sku,
                'price' => $listing->price,
                'price_formatted' => number_format($listing->price / 100, 2),
                'currency' => $country->currency_code,
                'condition' => $listing->condition,
                'condition_notes' => $listing->condition_notes,
                'is_admin_listing' => true,
                'is_express_fbn' => true,
                'fulfillment_model' => $listing->fulfillment_type,
                'global_system_type' => GlobalSystemType::ExpressFbn->value,
                'status' => $listing->status?->value,
                'max_order_quantity' => $listing->max_order_quantity,
                'total_sold' => $listing->total_sold,
                'rating_avg' => $listing->rating_avg,
                'rating_count' => $listing->rating_count,
                'is_global_shipping' => (bool) $listing->is_global_shipping,
                'is_wishlisted' => $isWishlisted,
            ];
        }

        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'vendor_sku' => $listing->vendor_sku,
            'sku' => $listing->productVariant->sku,
            'price' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $country->currency_code,
            'condition' => $listing->condition,
            'condition_notes' => $listing->condition_notes,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'is_express_fbn' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'fulfillment_model' => $listing->fulfillment_model,
            'global_system_type' => $listing->global_system_type?->value,
            'status' => $listing->status?->value,
            'max_order_quantity' => $listing->max_order_quantity,
            'total_sold' => $listing->total_sold,
            'rating_avg' => $listing->rating_avg,
            'rating_count' => $listing->rating_count,
            'is_global_shipping' => $listing->fulfillment_model === 'marketplace',
            'is_wishlisted' => $isWishlisted,
        ];
    }

    private function sellerShape(VendorListing|AdminProductListing $listing): array
    {
        if ($listing instanceof AdminProductListing) {
            return [
                'id' => null,
                'store_name' => 'Nawy',
                'rating_avg' => $listing->rating_avg,
                'rating_count' => $listing->rating_count,
                'is_admin_listing' => true,
                'vendor_details' => null,
            ];
        }

        return [
            'id' => $listing->vendor->id,
            'store_name' => $listing->vendor->store_name,
            'rating_avg' => $listing->vendor->store_rating_avg,
            'rating_count' => $listing->vendor->store_rating_count,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'vendor_details' => $this->vendorDetailsShape($listing->vendor),
        ];
    }

    private function vendorDetailsShape(?\App\Models\Vendor $vendor): ?array
    {
        if (!$vendor) {
            return null;
        }

        return [
            'rating_avg' => (float) $vendor->store_rating_avg,
            'rating_count' => (int) $vendor->store_rating_count,
            'positive_rating_pct' => $vendor->positive_rating_pct,
            'item_as_shown_pct' => $vendor->positive_rating_pct,
            'partner_since_years' => $vendor->partner_years,
            'warranty_months' => $vendor->warranty_months,
            'easy_returns_enabled' => (bool) $vendor->easy_returns_enabled,
            'secure_payments_enabled' => (bool) $vendor->secure_payments_enabled,
        ];
    }

    private function deliveryOptionShape($method): array
    {
        return [
            'method_code' => $method->code,
            'method_name' => $method->name,
            'badge_label' => Bilingual::pair($method, 'badge_label'),
            'badge_color_hex' => $method->badge_color_hex,
            'badge_text_color_hex' => $method->badge_text_color_hex,
            'delivery_label' => Bilingual::pair($method, 'delivery_label'),
            'delivery_days_min' => $method->min_delivery_days,
            'delivery_days_max' => $method->max_delivery_days,
        ];
    }

    private function productShape($product, VendorListing|AdminProductListing $listing): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => Bilingual::pair($product, 'name'),
            'description' => Bilingual::pair($product, 'description'),
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => Bilingual::pair($product->brand, 'name'),
                'slug' => $product->brand->slug,
                'is_verified' => $product->brand->is_verified,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => Bilingual::pair($product->category, 'name'),
                'slug' => $product->category->slug,
            ] : null,
            'breadcrumbs' => $product->category ? $this->breadcrumbs($product->category) : [],
            'images' => $product->images->map(fn($img) => [
                'url' => $img->url,
                'is_primary' => $img->is_primary,
            ])->values()->all(),
            'rating_avg' => (float) $listing->rating_avg,
            'rating_count' => (int) $listing->rating_count,
            'attributes_summary' => $this->attributesSummary($product),
            'highlights' => $product->highlights->map(fn($h) => [
                'id' => $h->id,
                'text' => Bilingual::pair($h, 'text'),
                'position' => $h->position,
            ])->values()->all(),
            'specifications' => $product->specifications->map(fn($s) => [
                'id' => $s->id,
                'key' => Bilingual::pair($s, 'key'),
                'value' => Bilingual::pair($s, 'value'),
                'position' => $s->position,
            ])->values()->all(),
            'seo' => [
                'title' => Bilingual::pairFromKeys($product, 'seo_title_ar', 'seo_title_en'),
                'description' => Bilingual::pairFromKeys($product, 'seo_description_ar', 'seo_description_en'),
            ],
        ];
    }

    private function breadcrumbs($category): array
    {
        $crumbs = [];

        foreach ($category->ancestors()->get() as $ancestor) {
            $crumbs[] = [
                'id' => $ancestor->id,
                'name' => Bilingual::pair($ancestor, 'name'),
                'slug' => $ancestor->slug,
            ];
        }

        $crumbs[] = [
            'id' => $category->id,
            'name' => Bilingual::pair($category, 'name'),
            'slug' => $category->slug,
        ];

        return $crumbs;
    }

    private function attributesSummary($product): ?array
    {
        $variant = $product->variants->first();

        if (!$variant || $variant->variantAttributes->isEmpty()) {
            return null;
        }

        return [
            'ar' => $variant->variantAttributes
                ->map(fn($va) => ($va->attribute?->name_ar) . ': ' . ($va->attributeValue?->value_ar ?? $va->value_text_ar))
                ->implode(', '),
            'en' => $variant->variantAttributes
                ->map(fn($va) => ($va->attribute?->name_en) . ': ' . ($va->attributeValue?->value_en ?? $va->value_text_en))
                ->implode(', '),
        ];
    }

    private function productAttributesForListing($product, $productVariant, $country): array
    {
        $variants = $product->variants->loadMissing('variantAttributes.attribute', 'variantAttributes.attributeValue');
        $listingsByVariant = $this->variantListingsMap($product, $country);

        return $this->productAttributesShape($variants, $productVariant, $listingsByVariant);
    }

    private function variantListingsMap($product, $country): array
    {
        $variantIds = $product->variants->pluck('id')->all();

        if ($this->appContext->isNawyNow()) {
            $listings = AdminProductListing::whereIn('product_variant_id', $variantIds)
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->orderBy('price')
                ->get()
                ->groupBy('product_variant_id');
        } else {
            $listings = VendorListing::whereIn('product_variant_id', $variantIds)
                ->where('country_id', $country->id)
                ->where('status', VendorListingStatus::Active)
                ->orderBy('price')
                ->get()
                ->groupBy('product_variant_id');
        }

        return $listings
            ->map(fn($group) => [
                'listing_id' => $group->first()->id,
                'listing_ref' => $this->identifiers->buildListingRef($group->first()),
            ])
            ->all();
    }

    private function variantShape($variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'variant_name' => $variant->variant_name,
            'is_default' => $variant->is_default,
            'attributes' => $variant->variantAttributes->map(fn($va) => [
                'attribute_name' => [
                    'ar' => $va->attribute?->name_ar,
                    'en' => $va->attribute?->name_en,
                ],
                'value' => [
                    'ar' => $va->attributeValue?->value_ar ?? $va->value_text_ar,
                    'en' => $va->attributeValue?->value_en ?? $va->value_text_en,
                ],
            ])->values()->all(),
        ];
    }

    private function otherSellerShape(VendorListing $listing, $country, bool $isSelected = false): array
    {
        return [
            'listing_id' => $listing->id,
            'is_selected' => $isSelected,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'url' => route('customer.listing.show', [$country->site_code, $listing->product_variant_id . '--' . $listing->id]),
            'seller_name' => $listing->vendor->store_name,
            'seller_rating' => $listing->vendor->store_rating_avg,
            'price' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $country->currency_code,
            'condition' => $listing->condition,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'is_express_fbn' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'shipping_badge' => $listing->primaryShippingMethod ? [
                'label' => Bilingual::pair($listing->primaryShippingMethod, 'badge_label'),
                'color_hex' => $listing->primaryShippingMethod->badge_color_hex,
                'text_color_hex' => $listing->primaryShippingMethod->badge_text_color_hex,
                'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
            ] : null,
            'vendor_details' => $this->vendorDetailsShape($listing->vendor),
        ];
    }

    private function otherVariantShape(VendorListing $listing): array
    {
        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'sku' => $listing->productVariant->sku,
            'variant_name' => $listing->productVariant->variant_name,
            'price' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $listing->currency,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'attributes' => $listing->productVariant->variantAttributes->map(fn($va) => [
                'attribute_name' => [
                    'ar' => $va->attribute?->name_ar,
                    'en' => $va->attribute?->name_en,
                ],
                'value' => [
                    'ar' => $va->attributeValue?->value_ar ?? $va->value_text_ar,
                    'en' => $va->attributeValue?->value_en ?? $va->value_text_en,
                ],
            ])->values()->all(),
            'shipping_badge' => $listing->primaryShippingMethod ? [
                'label' => Bilingual::pair($listing->primaryShippingMethod, 'badge_label'),
                'color_hex' => $listing->primaryShippingMethod->badge_color_hex,
                'text_color_hex' => $listing->primaryShippingMethod->badge_text_color_hex,
                'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
            ] : null,
        ];
    }

    private function frequentlyBoughtTogetherShape($product, VendorListing|AdminProductListing $listing, $country): array
    {
        $relatedProducts = $product->frequentlyBoughtTogether()
            ->with(['images', 'variants'])
            ->limit(4)
            ->get();

        $items = collect([$this->fbtItemShape($listing, $product, $country)]);

        foreach ($relatedProducts as $relatedProduct) {
            $relatedListing = VendorListing::query()
                ->whereHas('productVariant', fn($q) => $q->where('product_id', $relatedProduct->id))
                ->where('country_id', $country->id)
                ->where('status', VendorListingStatus::Active)
                ->orderBy('price')
                ->with('productVariant')
                ->first();

            if (!$relatedListing) {
                continue;
            }

            $items->push($this->fbtItemShape($relatedListing, $relatedProduct, $country));
        }

        return [
            'items' => $items->values()->all(),
            'total_price' => $items->sum('price'),
            'total_price_formatted' => number_format($items->sum('price') / 100, 2),
            'currency' => $country->currency_code,
        ];
    }

    private function relatedProductsShape($product, $country): array
    {
        $candidates = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            // ->whereHas(
            //     'countrySettings',
            //     fn($q) => $q->where('country_id', $country->id)->where('is_available', true)
            // )
            ->with(['images', 'variants'])
            ->orderByRating()
            ->limit(8)
            ->get();

        $wishlistIds = $this->listings->wishlistListingIds(auth('customer')->id());

        $items = [];
        foreach ($candidates as $candidate) {
            $relatedListing = VendorListing::query()
                ->whereHas('productVariant', fn($q) => $q->where('product_id', $candidate->id))
                ->where('country_id', $country->id)
                ->where('status', VendorListingStatus::Active)
                ->orderBy('price')
                ->with(['productVariant', 'vendor:id,store_name,store_rating_avg', 'primaryShippingMethod'])
                ->first();

            if (!$relatedListing) {
                continue;
            }

            $items[] = $this->listings->toCardShape(
                listing: $relatedListing,
                product: $candidate,
                country: $country,
                isWishlisted: in_array($relatedListing->id, $wishlistIds),
            );
        }

        return $items;
    }

    private function fbtItemShape(VendorListing|AdminProductListing $listing, $product, $country): array
    {
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'product_id' => $product->id,
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'name' => Bilingual::pair($product, 'name'),
            'image_url' => $primaryImage?->url,
            'price' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $country->currency_code,
        ];
    }

    private function reviewShape($review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'reviewer_name' => $review->customer?->name,
            'helpful_count' => $review->helpful_count,
            'created_at' => $review->created_at?->toIso8601String(),
            'images' => $review->files->map(fn($f) => $f->full_path)->values()->all(),
            'listing_id' => $review->vendor_listing_id,
            'seller' => $review->vendorListing?->vendor ? [
                'id' => $review->vendorListing->vendor->id,
                'store_name' => $review->vendorListing->vendor->store_name,
            ] : null,
            'variant' => $review->vendorListing?->productVariant ? [
                'id' => $review->vendorListing->productVariant->id,
                'variant_name' => $review->vendorListing->productVariant->variant_name,
                'attributes' => $review->vendorListing->productVariant->variantAttributes->map(fn($va) => [
                    'name' => [
                        'ar' => $va->attribute?->name_ar,
                        'en' => $va->attribute?->name_en,
                    ],
                    'value' => [
                        'ar' => $va->attributeValue?->value_ar ?? $va->value_text_ar,
                        'en' => $va->attributeValue?->value_en ?? $va->value_text_en,
                    ],
                ])->values()->all(),
            ] : null,
            'vendor_reply' => $review->vendorReply ? [
                'body' => $review->vendorReply->body,
                'created_at' => $review->vendorReply->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
