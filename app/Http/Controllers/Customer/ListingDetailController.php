<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\VendorListing;
use App\Models\Wishlist;
use App\Services\Customer\ListingIdentifierService;
use App\Services\Customer\ProductViewService;
use App\Services\ListingShippingResolver;
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

    public function show(Request $request, Country $country, string $identifier): JsonResponse
    {
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
                ->where('product_id', $product->id)
                ->exists();
        }

        $this->viewService->logView(
            product: $product,
            country: $country,
            customerId: auth('customer')->id(),
            sessionId: $request->session()->getId() ?? '',
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
            'delivery_options' => $deliveryOptions->map(fn ($method) => $this->deliveryOptionShape($method))->values()->all(),
            'product' => $this->productShape($product),
            'variant' => $this->variantShape($listing->productVariant),
            'other_sellers' => $siblings['same_variant']->map(fn (VendorListing $l) => $this->otherSellerShape($l, $country))->values()->all(),
            'other_variants' => $siblings['other_variants']->map(fn (VendorListing $l) => $this->otherVariantShape($l))->values()->all(),
            'reviews' => [
                'rating_avg' => (float) $product->rating_avg,
                'rating_count' => (int) $product->rating_count,
                'items' => $reviews->map(fn ($review) => $this->reviewShape($review))->values()->all(),
            ],
        ]);
    }

    private function resolveListing(string $identifier, Country $country): ?VendorListing
    {
        if (str_contains($identifier, '--')) {
            $parsed = $this->identifiers->parseListingRef($identifier);

            if (!$parsed) {
                return null;
            }

            return VendorListing::whereHas('productVariant', fn ($q) => $q->where('sku', $parsed['sku']))
                ->where('id', 'like', $parsed['listing_id_prefix'].'%')
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

    private function listingShape(VendorListing $listing, Country $country, bool $isWishlisted): array
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
            'is_admin_listing' => $listing->global_system_type === 'express_fbn',
            'is_express_fbn' => $listing->global_system_type === 'express_fbn',
            'fulfillment_model' => $listing->fulfillment_model,
            'global_system_type' => $listing->global_system_type,
            'status' => $listing->status,
            'max_order_quantity' => $listing->max_order_quantity,
            'total_sold' => $listing->total_sold,
            'rating_avg' => $listing->rating_avg,
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
            'is_admin_listing' => $listing->global_system_type === 'express_fbn',
        ];
    }

    private function deliveryOptionShape($method): array
    {
        return [
            'method_code' => $method->code,
            'method_name' => $method->name,
            'badge_label_en' => $method->badge_label_en,
            'badge_label_ar' => $method->badge_label_ar,
            'badge_color_hex' => $method->badge_color_hex,
            'badge_text_color_hex' => $method->badge_text_color_hex,
            'delivery_label_en' => $method->delivery_label_en,
            'delivery_label_ar' => $method->delivery_label_ar,
            'delivery_days_min' => $method->min_delivery_days,
            'delivery_days_max' => $method->max_delivery_days,
        ];
    }

    private function productShape($product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar,
            'description_en' => $product->description_en,
            'description_ar' => $product->description_ar,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name_en' => $product->brand->name_en,
                'name_ar' => $product->brand->name_ar,
                'slug' => $product->brand->slug,
                'is_verified' => $product->brand->is_verified,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name_en' => $product->category->name_en,
                'name_ar' => $product->category->name_ar,
                'slug' => $product->category->slug,
            ] : null,
            'images' => $product->images->map(fn ($img) => [
                'url' => $img->url,
                'is_primary' => $img->is_primary,
            ])->values()->all(),
            'rating_avg' => (float) $product->rating_avg,
            'rating_count' => (int) $product->rating_count,
            'attributes_summary' => $this->attributesSummary($product),
            'seo' => [
                'title_en' => $product->seo_title_en,
                'title_ar' => $product->seo_title_ar,
                'description_en' => $product->seo_description_en,
                'description_ar' => $product->seo_description_ar,
            ],
        ];
    }

    private function attributesSummary($product): ?string
    {
        $variant = $product->variants->first();

        if (!$variant || $variant->variantAttributes->isEmpty()) {
            return null;
        }

        return $variant->variantAttributes
            ->map(fn ($va) => ($va->attribute?->name_en).': '.($va->attributeValue?->value_en ?? $va->value_text_en))
            ->implode(', ');
    }

    private function variantShape($variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'variant_name' => $variant->variant_name,
            'is_default' => $variant->is_default,
            'attributes' => $variant->variantAttributes->map(fn ($va) => [
                'attribute_name' => $va->attribute?->name_en,
                'value' => $va->attributeValue?->value_en ?? $va->value_text_en,
            ])->values()->all(),
        ];
    }

    private function otherSellerShape(VendorListing $listing, Country $country): array
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
            'is_admin_listing' => $listing->global_system_type === 'express_fbn',
            'is_express_fbn' => $listing->global_system_type === 'express_fbn',
            'shipping_badge' => $listing->primaryShippingMethod ? [
                'label_en' => $listing->primaryShippingMethod->badge_label_en,
                'label_ar' => $listing->primaryShippingMethod->badge_label_ar,
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
            'is_admin_listing' => $listing->global_system_type === 'express_fbn',
            'attributes' => $listing->productVariant->variantAttributes->map(fn ($va) => [
                'attribute_name' => $va->attribute?->name_en,
                'value' => $va->attributeValue?->value_en ?? $va->value_text_en,
            ])->values()->all(),
            'shipping_badge' => $listing->primaryShippingMethod ? [
                'label_en' => $listing->primaryShippingMethod->badge_label_en,
                'label_ar' => $listing->primaryShippingMethod->badge_label_ar,
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
