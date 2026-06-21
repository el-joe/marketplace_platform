<?php

namespace App\Services\Customer;

use App\Models\Country;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        private readonly ProductQueryService $productQuery,
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
            });

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

        return $products->map(fn($p) => [
            'id'   => $p->id,
            'slug' => $p->slug,
            'name' => app()->getLocale() === 'ar' ? $p->name_ar : $p->name_en,
            'type' => 'product',
        ])->toArray();
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
