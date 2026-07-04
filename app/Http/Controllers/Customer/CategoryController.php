<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProductListRequest;
use App\Models\Category;
use App\Models\Country;
use App\Services\Customer\CategoryService;
use App\Services\Customer\ListingQueryService;
use App\Services\Customer\ProductQueryService;
use App\Services\Shared\PageBuilderService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly ProductQueryService $products,
        private readonly ListingQueryService $listings,
        private readonly PageBuilderService $pageBuilder,
    ) {}

    /**
     * GET /categories
     * Full nested tree for nav/menu. Cached 10 min per country.
     */
    public function index(Country $country): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->categories->getTree($country),
        ]);
    }

    /**
     * GET /categories/{slug}
     * Full category page payload: category metadata, page_builder, and live listing grid.
     */
    public function show(ProductListRequest $request, Country $country, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $filters     = $request->validated();
        $perPage     = (int) ($filters['per_page'] ?? 20);
        $categoryIds = $this->categories->getDescendantIds($category);

        $pageBuilder = $this->pageBuilder->resolve(
            $country,
            'category',
            $category->id,
            $this->pageBuilder->detectDevice($request),
            auth('customer')->check() ? 'authenticated' : 'guest',
        );

        $paginator = $this->listings->paginateForCategory($country, $categoryIds, $filters, $perPage);
        $facets    = $this->products->facets($country, $filters, $categoryIds);

        $wishlistProductIds = $this->listings->wishlistProductIds(auth('customer')->id());

        $items = $paginator->getCollection()->map(function ($listing) use ($country, $wishlistProductIds) {
            $product = $listing->productVariant->product;

            return $this->listings->toCardShape(
                $listing,
                $product,
                $country,
                in_array($product->id, $wishlistProductIds),
            );
        })->toArray();

        return response()->json([
            'success' => true,
            'data'    => [
                'category'    => $this->categoryPayload($category),
                'page_builder' => $pageBuilder,
                'listings'    => [
                    'items'  => $items,
                    'facets' => $facets,
                    'meta'   => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                    ],
                ],
            ],
        ]);
    }

    private function categoryPayload(Category $category): array
    {
        return [
            'id'              => $category->id,
            'name_en'         => $category->name_en,
            'name_ar'         => $category->name_ar,
            'slug'            => $category->slug,
            'description_en'  => $category->description_en,
            'description_ar'  => $category->description_ar,
            'parent'          => $category->parent ? [
                'id'   => $category->parent->id,
                'name_en' => $category->parent->name_en,
                'name_ar' => $category->parent->name_ar,
                'slug' => $category->parent->slug,
            ] : null,
            'children' => $category->children()
                ->where('is_active', true)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($child) => [
                    'id'      => $child->id,
                    'name_en' => $child->name_en,
                    'name_ar' => $child->name_ar,
                    'slug'    => $child->slug,
                    'icon'    => $child->icon,
                ])
                ->toArray(),
        ];
    }
}
