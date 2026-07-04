<?php

namespace App\Services\Customer;

use App\Models\ClassifiedCategory;
use App\Models\ClassifiedListing;
use App\Models\Country;
use App\Models\Product;
use App\Models\TravelPackage;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListingQueryService
{
    /**
     * Base listing query for a category (+ descendants) grid: active listings,
     * active vendor, active product within the given category IDs.
     *
     * @param  list<string>  $categoryIds
     */
    public function baseCategoryQuery(Country $country, array $categoryIds)
    {
        return VendorListing::where('country_id', $country->id)
            ->where('status', 'active')
            ->whereHas(
                'productVariant.product',
                fn($q) => $q->whereIn('category_id', $categoryIds)->where('status', 'active'),
            )
            ->whereHas('vendor', fn($q) => $q->where('global_status', 'active'));
    }

    /**
     * Base listing query for text search: active listings whose product matches
     * the query, active vendor. Kept listing-centric so the same product sold by
     * multiple vendors appears once per vendor listing, not deduped per product.
     */
    public function baseSearchQuery(Country $country, string $query)
    {
        return VendorListing::where('country_id', $country->id)
            ->where('status', 'active')
            ->whereHas('productVariant.product', function ($q) use ($query) {
                $q->where('status', 'active')
                    ->where(function ($q2) use ($query) {
                        $q2->where('name_en', 'like', "%{$query}%")
                            ->orWhere('name_ar', 'like', "%{$query}%")
                            ->orWhere('short_desc_en', 'like', "%{$query}%")
                            ->orWhere('model_number', 'like', "%{$query}%");
                    });
            })
            ->whereHas('vendor', fn($q) => $q->where('global_status', 'active'));
    }

    /**
     * Apply the standard grid filters (price, brand, rating, condition, fulfillment,
     * stock, attributes) to a VendorListing query built from baseCategoryQuery().
     *
     * @param  array<string,mixed>  $filters
     */
    public function applyFilters($builder, array $filters)
    {
        if (!empty($filters['category'])) {
            $builder->whereHas('productVariant.product', fn($q) => $q->where('category_id', $filters['category']));
        }
        if (!empty($filters['brand'])) {
            $builder->whereHas('productVariant.product', fn($q) => $q->where('brand_id', $filters['brand']));
        }
        if (!empty($filters['price_min'])) {
            $builder->where('price', '>=', (int) ($filters['price_min'] * 100));
        }
        if (!empty($filters['price_max'])) {
            $builder->where('price', '<=', (int) ($filters['price_max'] * 100));
        }
        if (!empty($filters['rating_min'])) {
            $builder->whereHas('productVariant.product', fn($q) => $q->where('rating_avg', '>=', $filters['rating_min']));
        }
        if (!empty($filters['condition'])) {
            $builder->where('condition', $filters['condition']);
        }
        if (!empty($filters['fulfillment_model'])) {
            $builder->where('fulfillment_model', $filters['fulfillment_model']);
        }
        if (empty($filters['include_oos'])) {
            $builder->whereHas('warehouseInventories', fn($q) => $q->where('quantity_available', '>', 0));
        }
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attrCode => $values) {
                $values = (array) $values;
                $builder->whereExists(function ($sub) use ($attrCode, $values) {
                    $sub->select(DB::raw(1))
                        ->from('product_variant_attributes as pva')
                        ->join('attributes as a', 'a.id', '=', 'pva.attribute_id')
                        ->join('attribute_values as av', 'av.id', '=', 'pva.attribute_value_id')
                        ->whereColumn('pva.product_variant_id', 'vendor_listings.product_variant_id')
                        ->where('a.code', $attrCode)
                        ->whereIn('av.value_en', $values);
                });
            }
        }

        return $builder;
    }

    /**
     * Apply sort to a VendorListing category query, falling back to the default
     * buy-box ordering (express_fbn/merchant_fbp/marketplace, then price).
     */
    public function applySort($builder, string $sort)
    {
        return match ($sort) {
            'price_asc' => $builder->orderBy('price', 'asc'),
            'price_desc' => $builder->orderBy('price', 'desc'),
            'rating' => $builder->orderByDesc('rating_avg'),
            'newest' => $builder->orderByDesc('created_at'),
            'best_selling' => $builder->orderByDesc('total_sold'),
            default => $builder
                ->orderByRaw("FIELD(global_system_type,'express_fbn','merchant_fbp','marketplace')")
                ->orderBy('price'),
        };
    }

    /**
     * Paginate category listings with filters, sort, and eager loads applied.
     *
     * @param  list<string>  $categoryIds
     * @param  array<string,mixed>  $filters
     */
    public function paginateForCategory(
        Country $country,
        array $categoryIds,
        array $filters,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $builder = $this->baseCategoryQuery($country, $categoryIds)
            ->with([
                'vendor:id,store_name,store_rating_avg',
                'productVariant:id,sku,product_id',
                'productVariant.product.images',
                'productVariant.product.category:id,name_en,name_ar,slug',
                'primaryShippingMethod:id,badge_label_en,badge_label_ar,badge_color_hex,badge_text_color_hex,min_delivery_days,max_delivery_days',
            ]);

        $builder = $this->applyFilters($builder, $filters);
        $builder = $this->applySort($builder, $filters['sort'] ?? 'relevance');

        return $builder->paginate($perPage);
    }

    /**
     * All active listings for one product variant in a country, buy-box ordered.
     * Used on product detail page (shows the full "More sellers" list).
     */
    public function getForVariant(
        string $productVariantId,
        Country $country,
        int $limit = 10,
    ): Collection {
        return VendorListing::query()
            ->where('product_variant_id', $productVariantId)
            ->where('country_id', $country->id)
            ->where('status', 'active')
            ->whereHas('vendor', fn($q) => $q->where('global_status', 'active'))
            ->with([
                'vendor:id,store_name,store_rating_avg,store_rating_count',
                'primaryShippingMethod:id,name,badge_label_en,badge_label_ar,badge_color_hex,badge_text_color_hex,min_delivery_days,max_delivery_days',
                'productVariant:id,sku',
            ])
            ->orderByRaw("FIELD(global_system_type,'express_fbn','merchant_fbp','marketplace')")
            ->orderBy('price')
            ->orderByDesc('total_sold')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get();
    }

    /**
     * Best listing per product (buy-box winner) for a collection of products.
     * Returns array keyed by product.id => VendorListing|null.
     * Used on listing grids (category, search, home carousels).
     */
    public function getBuyBoxForProducts(
        Collection $products,
        Country $country,
    ): array {
        $variantIds = $products
            ->flatMap(fn(Product $product) => $product->variants->pluck('id'))
            ->unique()
            ->values();

        $listings = VendorListing::query()
            ->whereIn('product_variant_id', $variantIds)
            ->where('country_id', $country->id)
            ->where('status', 'active')
            ->whereHas('vendor', fn($q) => $q->where('global_status', 'active'))
            ->with([
                'primaryShippingMethod:id,name,badge_label_en,badge_label_ar,badge_color_hex,badge_text_color_hex,min_delivery_days,max_delivery_days',
                'vendor:id,store_name,store_rating_avg',
                'productVariant:id,sku,product_id',
            ])
            ->orderByRaw("FIELD(global_system_type,'express_fbn','merchant_fbp','marketplace')")
            ->orderBy('price')
            ->orderByDesc('total_sold')
            ->orderByDesc('rating_avg')
            ->get()
            ->groupBy('product_variant_id');

        $result = [];

        foreach ($products as $product) {
            $bestListing = null;

            foreach ($product->variants as $variant) {
                if ($listings->has($variant->id)) {
                    $bestListing = $listings->get($variant->id)->first();
                    break;
                }
            }

            $result[$product->id] = $bestListing;
        }

        return $result;
    }

    /**
     * Canonical listing card shape returned by EVERY listing grid endpoint.
     * All grid APIs must use this method — never build listing card arrays inline.
     */
    public function toCardShape(
        VendorListing $listing,
        Product $product,
        Country $country,
        bool $isWishlisted = false,
        bool $isSponsored = false,
    ): array {
        return [
            'listing_id' => $listing->id,
            'listing_ref' => app(\App\Services\Customer\ListingIdentifierService::class)->buildListingRef($listing),
            'sku' => $listing->productVariant->sku,
            'vendor_sku' => $listing->vendor_sku,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'slug' => $product->slug,
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar,
            'thumbnail' => $product->images->first()?->url ?? null,
            'price_cents' => $listing->price,
            'price_formatted' => number_format($listing->price / 100, 2),
            'currency' => $country->currency_code,
            'condition' => $listing->condition,
            'is_admin_listing' => $listing->global_system_type === 'express_fbn',
            'is_express_fbn' => $listing->global_system_type === 'express_fbn',
            'fulfillment_model' => $listing->fulfillment_model,
            'vendor' => [
                'id' => $listing->vendor->id,
                'store_name' => $listing->vendor->store_name,
                'rating' => $listing->vendor->store_rating_avg,
            ],
            'shipping_badge' => $listing->primaryShippingMethod ? [
                'label_en' => $listing->primaryShippingMethod->badge_label_en,
                'label_ar' => $listing->primaryShippingMethod->badge_label_ar,
                'color_hex' => $listing->primaryShippingMethod->badge_color_hex,
                'text_color_hex' => $listing->primaryShippingMethod->badge_text_color_hex,
                'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
            ] : null,
            'rating_avg' => $product->rating_avg,
            'rating_count' => $product->rating_count,
            'total_sold' => $listing->total_sold,
            'is_wishlisted' => $isWishlisted,
            'is_sponsored' => $isSponsored,
        ];
    }

    public function wishlistProductIds(?string $customerId): array
    {
        if ($customerId === null) {
            return [];
        }

        return Wishlist::where('customer_id', $customerId)->pluck('product_id')->toArray();
    }

    /**
     * Paginate ClassifiedListing for a resolved category, including its direct children.
     */
    public function paginateForClassifiedCategory(
        string $categoryId,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $childIds = ClassifiedCategory::where('parent_id', $categoryId)->pluck('id');

        return ClassifiedListing::where('status', 'active')
            ->where(function ($q) use ($categoryId, $childIds) {
                $q->where('classified_category_id', $categoryId)
                    ->orWhereIn('classified_category_id', $childIds);
            })
            ->with(['images', 'seller'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Canonical card shape for classified listings on browse/search grids.
     */
    public function toClassifiedCardShape(ClassifiedListing $listing): array
    {
        return [
            'listing_id' => $listing->id,
            'listing_number' => $listing->listing_number,
            'source_type' => 'classified',
            'title_en' => $listing->title_en,
            'title_ar' => $listing->title_ar,
            'slug' => $listing->listing_number,
            'thumbnail' => $listing->images->first()?->file_path
                ? \Illuminate\Support\Facades\Storage::url($listing->images->first()->file_path)
                : null,
            'price_cents' => $listing->price_cents,
            'price_formatted' => number_format($listing->price_cents / 100, 2),
            'currency' => $listing->currency,
            'price_negotiable' => (bool) $listing->price_negotiable,
            'listing_purpose' => $listing->listing_purpose,
            'location' => $listing->city?->name_en,
            'seller_type' => $listing->seller_type === Vendor::class ? 'vendor' : 'customer',
            'created_at' => $listing->created_at?->toIso8601String(),
        ];
    }

    /**
     * Paginate active TravelPackage, optionally filtered by travel category.
     */
    public function paginateTravelPackages(?string $travelCategoryId, int $perPage = 20): LengthAwarePaginator
    {
        return TravelPackage::where('status', 'active')
            ->when($travelCategoryId, fn($q) => $q->whereHas(
                'categories',
                fn($q2) => $q2->where('travel_categories.id', $travelCategoryId),
            ))
            ->with([
                'agency:id,name',
                'categories:id,name_en,name_ar,slug',
                'media' => fn($q) => $q->orderBy('position')->limit(1),
            ])
            ->orderByDesc('departure_date')
            ->paginate($perPage);
    }

    /**
     * Canonical card shape for travel packages on browse grids.
     */
    public function toTravelCardShape(TravelPackage $package): array
    {
        return [
            'package_id' => $package->id,
            'source_type' => 'travel',
            'title_en' => $package->title_en,
            'title_ar' => $package->title_ar,
            'slug' => $package->id,
            'thumbnail' => $package->media->first()?->url(),
            'destination_country' => $package->destination_country,
            'destination_city' => $package->destination_city,
            'departure_date' => $package->departure_date?->toDateString(),
            'return_date' => $package->return_date?->toDateString(),
            'duration_days' => $package->duration_days,
            'duration_nights' => $package->duration_nights,
            'price_cents' => $package->price_cents,
            'price_formatted' => number_format($package->price_cents / 100, 2),
            'currency' => $package->currency,
            'available_seats' => $package->available_seats,
            'seats_remaining' => $package->seatsRemaining(),
            'agency_name' => $package->agency?->name,
            'categories' => $package->categories->map(fn($c) => [
                'name_en' => $c->name_en,
                'slug' => $c->slug,
            ])->toArray(),
            'link' => '/travel',
        ];
    }
}
