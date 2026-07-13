<?php

namespace App\Http\Controllers\Customer;

use App\Enums\GlobalSystemType;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\VendorListing;
use App\Models\Wishlist;
use App\Services\Customer\ListingIdentifierService;
use App\Services\Customer\ProductViewService;
use App\Services\ListingShippingResolver;
use App\Support\Bilingual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingDetailController extends Controller
{
    public function __construct(
        private readonly ListingIdentifierService $identifiers,
        private readonly ListingShippingResolver $shipping,
        private readonly ProductViewService $viewService,
    ) {
    }

    public function show(Request $request, $country, string $identifier): JsonResponse
    {
        $country = $request->attributes->get('country');

        $listing = $this->resolveListing($identifier, $country);

        if (!$listing) {
            return ApiResponse::error('Listing not found or not available in this country.', [], 404);
        }

        $siblings = $this->identifiers->getSiblings($listing, $country);
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

        $reviews = $product->reviews()
            ->where('status', 'published')
            ->with(['vendorReply', 'customer:id,name'])
            ->orderByDesc('helpful_count')
            ->limit(5)
            ->get();

        return ApiResponse::success([
            'listing' => $this->listingShape($listing, $country, $isWishlisted),
            'seller' => $this->sellerShape($listing),
            'delivery_options' => $deliveryOptions->map(fn($method) => $this->deliveryOptionShape($method))->values()->all(),
            'product' => $this->productShape($product, $listing),
            'variant' => $this->variantShape($listing->productVariant),
            'other_sellers' => $siblings['same_variant']->map(fn(VendorListing $l) => $this->otherSellerShape($l, $country))->values()->all(),
            'other_variants' => $siblings['other_variants']->map(fn(VendorListing $l) => $this->otherVariantShape($l))->values()->all(),
            'reviews' => [
                'rating_avg' => (float) $listing->rating_avg,
                'rating_count' => (int) $listing->rating_count,
                'items' => $reviews->map(fn($review) => $this->reviewShape($review))->values()->all(),
            ],
        ]);
    }

    private function resolveListing(string $identifier, $country): ?VendorListing
    {
        if (str_contains($identifier, '--')) {
            $parsed = $this->identifiers->parseListingRef($identifier);

            if (!$parsed) {
                return null;
            }

            return VendorListing::whereHas('productVariant', fn($q) => $q->where('sku', $parsed['sku']))
                ->where('id', 'like', $parsed['listing_id_prefix'] . '%')
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->with([
                    'productVariant.product.images',
                    'productVariant.product.category',
                    'productVariant.product.brand',
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

    private function listingShape(VendorListing $listing, $country, bool $isWishlisted): array
    {
        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'vendor_sku' => $listing->vendor_sku,
            'sku' => $listing->productVariant->sku,
            'price_cents' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $country->currency_code,
            'condition' => $listing->condition,
            'condition_notes' => $listing->condition_notes,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'is_express_fbn' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
            'fulfillment_model' => $listing->fulfillment_model,
            'global_system_type' => $listing->global_system_type?->value,
            'status' => $listing->status,
            'max_order_quantity' => $listing->max_order_quantity,
            'total_sold' => $listing->total_sold,
            'rating_avg' => $listing->rating_avg,
            'rating_count' => $listing->rating_count,
            'is_global_shipping' => $listing->fulfillment_model === 'marketplace',
            'is_wishlisted' => $isWishlisted,
        ];
    }

    private function sellerShape(VendorListing $listing): array
    {
        return [
            'id' => $listing->vendor->id,
            'store_name' => $listing->vendor->store_name,
            'rating_avg' => $listing->vendor->store_rating_avg,
            'rating_count' => $listing->vendor->store_rating_count,
            'is_admin_listing' => $listing->global_system_type === GlobalSystemType::ExpressFbn,
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

    private function productShape($product, VendorListing $listing): array
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

    private function otherSellerShape(VendorListing $listing, $country): array
    {
        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'seller_name' => $listing->vendor->store_name,
            'seller_rating' => $listing->vendor->store_rating_avg,
            'price_cents' => $listing->price,
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
        ];
    }

    private function otherVariantShape(VendorListing $listing): array
    {
        return [
            'listing_id' => $listing->id,
            'listing_ref' => $this->identifiers->buildListingRef($listing),
            'sku' => $listing->productVariant->sku,
            'variant_name' => $listing->productVariant->variant_name,
            'price_cents' => $listing->price,
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
            'vendor_reply' => $review->vendorReply ? [
                'body' => $review->vendorReply->body,
                'created_at' => $review->vendorReply->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
