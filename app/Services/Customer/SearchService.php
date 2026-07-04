<?php

namespace App\Services\Customer;

use App\Models\ClassifiedListing;
use App\Models\Country;
use App\Models\Product;
use App\Models\TravelPackage;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        private readonly ProductQueryService $productQuery,
        private readonly UnifiedCategoryService $unifiedCategories,
    ) {}

    public function search(
        Country $country,
        string $query,
        array $filters = [],
        int $perPage = 20,
        ?string $customerId = null,
        string $sessionId = '',
    ): LengthAwarePaginator {
        $builder = $this->productQuery->baseQuery($country)
            ->where(function ($q) use ($query) {
                $q->where('products.name_en', 'like', "%{$query}%")
                  ->orWhere('products.name_ar', 'like', "%{$query}%")
                  ->orWhere('products.short_desc_en', 'like', "%{$query}%")
                  ->orWhere('products.model_number', 'like', "%{$query}%");
            })
            ->whereHas('variants.vendorListings', fn ($q) => $q
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->whereHas('vendor', fn ($q2) => $q2->where('global_status', 'active')));

        $builder = $this->productQuery->applyFilters($builder, $filters);
        $builder = $this->productQuery->applySort($builder, $filters['sort'] ?? 'relevance');

        $paginator = $builder->paginate($perPage);

        dispatch(new \App\Jobs\LogSearchJob(
            query: $query,
            countryId: $country->id,
            resultsCount: $paginator->total(),
            filters: $filters,
            customerId: $customerId,
            sessionId: $sessionId,
            language: app()->getLocale(),
        ))->afterResponse();

        return $paginator;
    }

    public function searchClassifieds(string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return ClassifiedListing::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title_en', 'like', "%{$query}%")
                  ->orWhere('title_ar', 'like', "%{$query}%")
                  ->orWhere('description_en', 'like', "%{$query}%");
            })
            ->with(['images', 'classifiedCategory', 'city'])
            ->when(!empty($filters['category']), fn ($q) => $q->where('classified_category_id', $filters['category']))
            ->when(!empty($filters['price_min']), fn ($q) => $q->where('price_cents', '>=', (int) ($filters['price_min'] * 100)))
            ->when(!empty($filters['price_max']), fn ($q) => $q->where('price_cents', '<=', (int) ($filters['price_max'] * 100)))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function searchTravel(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return TravelPackage::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title_en', 'like', "%{$query}%")
                  ->orWhere('title_ar', 'like', "%{$query}%")
                  ->orWhere('destination_country', 'like', "%{$query}%")
                  ->orWhere('destination_city', 'like', "%{$query}%");
            })
            ->with([
                'agency:id,name',
                'categories:id,name_en,name_ar,slug',
                'media' => fn ($q) => $q->orderBy('position')->limit(1),
            ])
            ->orderByDesc('departure_date')
            ->paginate($perPage);
    }

    public function suggestions(Country $country, string $query): array
    {
        $products = Product::query()
            ->select('products.id', 'products.name_en', 'products.name_ar', 'products.slug')
            ->join('product_country_settings as pcs', function ($j) use ($country) {
                $j->on('pcs.product_id', '=', 'products.id')
                  ->where('pcs.country_id', $country->id)
                  ->where('pcs.is_available', true);
            })
            ->where('products.status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('products.name_en', 'like', "%{$query}%")
                  ->orWhere('products.name_ar', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        $productSuggestions = $products->map(fn ($p) => [
            'id'   => $p->id,
            'slug' => $p->slug,
            'name' => app()->getLocale() === 'ar' ? $p->name_ar : $p->name_en,
            'type' => 'product',
        ]);

        $queries = $productSuggestions->pluck('name')->filter()->unique()->values()->take(5)->all();

        $vendors = Vendor::query()
            ->where('store_name', 'like', "%{$query}%")
            ->where('global_status', 'active')
            ->select('id', 'store_name', 'store_slug', 'store_rating_avg')
            ->limit(3)
            ->get()
            ->map(fn ($vendor) => [
                'id'         => $vendor->id,
                'store_name' => $vendor->store_name,
                'slug'       => $vendor->store_slug,
                'rating'     => $vendor->store_rating_avg,
            ]);

        return [
            'queries'    => $queries,
            'products'   => $productSuggestions->toArray(),
            'categories' => $this->unifiedCategories->search($query),
            'vendors'    => $vendors->toArray(),
        ];
    }

    // Delegates to ProductQueryService so /search and /products share one query implementation.

    public function listing(Country $country, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->productQuery->paginate($country, $filters, $perPage);
    }

    public function listingByCategories(
        Country $country,
        array $categoryIds,
        array $filters = [],
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->productQuery->paginate($country, $filters, $perPage, $categoryIds);
    }

    public function facets(Country $country, array $filters = [], ?array $categoryIds = null): array
    {
        return $this->productQuery->facets($country, $filters, $categoryIds);
    }
}
