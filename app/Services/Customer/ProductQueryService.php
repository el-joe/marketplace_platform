<?php

namespace App\Services\Customer;

use App\Http\Resources\Customer\ProductListResource;
use App\Models\Attribute;
use App\Models\Country;
use App\Models\Product;
use App\Models\Wishlist;
use App\Support\Bilingual;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductQueryService
{
    public function __construct(
        private readonly SponsoredProductService $sponsored,
    ) {
    }

    /**
     * Paginate products with optional category scope.
     *
     * @param  array<string,mixed>  $filters
     * @param  list<string>|null  $categoryIds  When provided, restricts to these category IDs
     */
    public function paginate(
        Country $country,
        array $filters,
        int $perPage = 20,
        ?array $categoryIds = null,
    ): LengthAwarePaginator {
        $builder = $this->baseQuery($country);

        if ($categoryIds !== null) {
            $builder->whereIn('products.category_id', $categoryIds);
        }

        $builder = $this->applyFilters($builder, $filters);
        $builder = $this->applySort($builder, $filters['sort'] ?? 'relevance');

        return $builder->paginate($perPage);
    }

    /**
     * Return price-range facets for the current filter set.
     *
     * @param  array<string,mixed>  $filters
     * @param  list<string>|null  $categoryIds
     */
    public function facets(Country $country, array $filters, ?array $categoryIds = null): array
    {
        $base = $this->baseQuery($country);

        if ($categoryIds !== null) {
            $base->whereIn('products.category_id', $categoryIds);
        }

        $base = $this->applyFilters($base, $filters);

        $priceRange = (clone $base)
            ->selectRaw('MIN(vl.price) as low, MAX(vl.price) as high')
            ->first();

        return [
            'price_range' => [
                'min' => $priceRange ? round($priceRange->low / 100, 2) : 0,
                'max' => $priceRange ? round($priceRange->high / 100, 2) : 0,
            ],
            'attributes' => $this->attributeFacets($base, $categoryIds ?? (!empty($filters['category']) ? [$filters['category']] : [])),
        ];
    }

    /**
     * Filterable attributes for the given categories, with per-value product counts
     * scoped to the already-filtered product set in $base.
     *
     * @param  list<string>  $categoryIds
     */
    private function attributeFacets($base, array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $productIds = (clone $base)->pluck('products.id');

        if ($productIds->isEmpty()) {
            return [];
        }

        $attributes = Attribute::query()
            ->where('is_filterable', true)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->with('values')
            ->orderBy('sort_order')
            ->get();

        return $attributes->map(function (Attribute $attribute) use ($productIds) {
            $counts = DB::table('product_variant_attributes as pva')
                ->join('product_variants as pv', 'pv.id', '=', 'pva.product_variant_id')
                ->whereIn('pv.product_id', $productIds)
                ->where('pva.attribute_id', $attribute->id)
                ->selectRaw('pva.attribute_value_id, COUNT(DISTINCT pv.product_id) as cnt')
                ->groupBy('pva.attribute_value_id')
                ->pluck('cnt', 'attribute_value_id');

            return [
                'id'     => $attribute->id,
                'code'   => $attribute->code,
                'name'   => Bilingual::pair($attribute, 'name'),
                'type'   => $attribute->type->value,
                'unit'   => $attribute->unit,
                'values' => $attribute->values->map(fn ($value) => [
                    'id'        => $value->id,
                    'value'     => Bilingual::pair($value, 'value'),
                    'color_hex' => $value->color_hex,
                    'count'     => (int) ($counts[$value->id] ?? 0),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Convert a paginator into the standard products payload:
     * { items, meta } — with wishlist flags and sponsored injection applied.
     */
    public function buildProductsPayload(
        LengthAwarePaginator $paginator,
        Country $country,
        int $page,
        string $placement = 'category_top',
    ): array {
        $wishlistIds = $this->wishlistIds();

        $items = ProductListResource::collection($paginator->load('images'))
            ->map(function (ProductListResource $r) use ($wishlistIds) {
                $r->resource->is_sponsored = false;
                $r->resource->is_wishlisted = in_array($r->resource->id, $wishlistIds);
                return $r->toArray(request());
            })
            ->toArray();

        $items = $this->sponsored->inject($items, $country, $page, $placement);

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    // ─── Query building ───────────────────────────────────────────────────────

    public function baseQuery(Country $country)
    {
        $buyBoxListingSubquery = 'SELECT vl_b.id FROM vendor_listings vl_b '
            .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
            .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
            ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC LIMIT 1";

        return Product::query()
            ->select(
                'products.*',
                'pcs.name_override_en',
                'pcs.name_override_ar',
                DB::raw('MIN(vl.price) as min_price'),
                DB::raw('MAX(vl.price) as max_price'),
                DB::raw('COUNT(DISTINCT vl.id) as active_seller_count'),
                DB::raw('COALESCE(SUM(wi.quantity_available), 0) as total_stock'),
                DB::raw('COALESCE(SUM(vl.rating_avg * vl.rating_count) / NULLIF(SUM(vl.rating_count), 0), 0) as rating_avg'),
                DB::raw('COALESCE(SUM(vl.rating_count), 0) as rating_count'),
            )
            ->selectRaw("({$buyBoxListingSubquery}) as buy_box_listing_id", [$country->id, 'active'])
            ->selectRaw(
                '(SELECT pv_b.slug FROM vendor_listings vl_b '
                .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
                .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
                ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC LIMIT 1"
                .') as buy_box_variant_slug',
                [$country->id, 'active'],
            )
            ->selectRaw(
                '(SELECT pv_b.variant_name FROM vendor_listings vl_b '
                .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
                .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
                ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC LIMIT 1"
                .') as buy_box_variant_name',
                [$country->id, 'active'],
            )
            ->selectRaw(
                '(SELECT pv_b.id FROM vendor_listings vl_b '
                .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
                .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
                ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC LIMIT 1"
                .') as buy_box_variant_id',
                [$country->id, 'active'],
            )
            ->selectRaw(
                '(SELECT pi.path FROM vendor_listings vl_b '
                .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
                .'JOIN product_images pi ON pi.product_variant_id = pv_b.id '
                .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
                ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC, pi.position ASC LIMIT 1"
                .') as buy_box_variant_image_path',
                [$country->id, 'active'],
            )
            ->selectRaw(
                '(SELECT pi.disk FROM vendor_listings vl_b '
                .'JOIN product_variants pv_b ON pv_b.id = vl_b.product_variant_id '
                .'JOIN product_images pi ON pi.product_variant_id = pv_b.id '
                .'WHERE pv_b.product_id = products.id AND vl_b.country_id = ? AND vl_b.status = ? AND vl_b.deleted_at IS NULL '
                ."ORDER BY FIELD(vl_b.global_system_type,'express_fbn','merchant_fbp','marketplace'), vl_b.price ASC, pi.position ASC LIMIT 1"
                .') as buy_box_variant_image_disk',
                [$country->id, 'active'],
            )
            ->join('product_country_settings as pcs', function ($j) use ($country) {
                $j->on('pcs.product_id', '=', 'products.id')
                    ->where('pcs.country_id', $country->id)
                    ->where('pcs.is_available', true);
            })
            ->leftJoin('product_variants as pv', function ($j) {
                $j->on('pv.product_id', '=', 'products.id')
                    ->where('pv.is_active', true)
                    ->whereNull('pv.deleted_at');
            })
            ->leftJoin('vendor_listings as vl', function ($j) use ($country) {
                $j->on('vl.product_variant_id', '=', 'pv.id')
                    ->where('vl.country_id', $country->id)
                    ->where('vl.status', 'active')
                    ->whereNull('vl.deleted_at');
            })
            ->leftJoin('warehouse_inventories as wi', 'wi.vendor_listing_id', '=', 'vl.id')
            ->where('products.status', 'active')
            ->groupBy('products.id', 'pcs.name_override_en', 'pcs.name_override_ar');
    }

    public function applyFilters($builder, array $filters)
    {
        if (!empty($filters['category'])) {
            $builder->where('products.category_id', $filters['category']);
        }
        if (!empty($filters['brand'])) {
            $builder->where('products.brand_id', $filters['brand']);
        }
        if (!empty($filters['price_min'])) {
            $builder->havingRaw('MIN(vl.price) >= ?', [(int) ($filters['price_min'] * 100)]);
        }
        if (!empty($filters['price_max'])) {
            $builder->havingRaw('MAX(vl.price) <= ?', [(int) ($filters['price_max'] * 100)]);
        }
        if (!empty($filters['rating_min'])) {
            $builder->havingRaw('COALESCE(SUM(vl.rating_avg * vl.rating_count) / NULLIF(SUM(vl.rating_count), 0), 0) >= ?', [$filters['rating_min']]);
        }
        if (!empty($filters['condition'])) {
            $builder->where('vl.condition', $filters['condition']);
        }
        if (!empty($filters['fulfillment_model'])) {
            $builder->where('vl.fulfillment_model', $filters['fulfillment_model']);
        }
        if (empty($filters['include_oos'])) {
            $builder->havingRaw('COALESCE(SUM(wi.quantity_available), 0) > 0');
        }
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attrCode => $values) {
                $values = (array) $values;
                $builder->whereExists(function ($sub) use ($attrCode, $values) {
                    $sub->select(DB::raw(1))
                        ->from('product_variant_attributes as pva')
                        ->join('attributes as a', 'a.id', '=', 'pva.attribute_id')
                        ->join('attribute_values as av', 'av.id', '=', 'pva.attribute_value_id')
                        ->whereColumn('pva.product_variant_id', 'pv.id')
                        ->where('a.code', $attrCode)
                        ->whereIn('av.value_en', $values);
                });
            }
        }

        return $builder;
    }

    public function applySort($builder, string $sort)
    {
        return match ($sort) {
            'price_asc' => $builder->orderByRaw('MIN(vl.price) ASC'),
            'price_desc' => $builder->orderByRaw('MAX(vl.price) DESC'),
            'rating' => $builder->orderByRaw('COALESCE(SUM(vl.rating_avg * vl.rating_count) / NULLIF(SUM(vl.rating_count), 0), 0) desc'),
            'newest' => $builder->orderBy('products.published_at', 'desc'),
            'best_selling' => $builder->orderBy('products.total_sold', 'desc'),
            default => $builder->orderBy('products.is_featured', 'desc')
                ->orderByRaw('COALESCE(SUM(vl.rating_avg * vl.rating_count) / NULLIF(SUM(vl.rating_count), 0), 0) desc'),
        };
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function wishlistIds(): array
    {
        $customerId = auth('customer')->id();
        if (!$customerId) {
            return [];
        }

        return Wishlist::where('wishlists.customer_id', $customerId)
            ->join('vendor_listings', 'vendor_listings.id', '=', 'wishlists.vendor_listing_id')
            ->join('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
            ->pluck('product_variants.product_id')
            ->unique()
            ->toArray();
    }
}
