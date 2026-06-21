<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProductListRequest;
use App\Http\Resources\Customer\CategoryPageResource;
use App\Models\Category;
use App\Models\Country;
use App\Services\Customer\CategoryService;
use App\Services\Customer\ProductQueryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly ProductQueryService $products,
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
     * Full category page payload: category metadata, page_blocks, and live product grid.
     */
    public function show(ProductListRequest $request, Country $country, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $filters       = $request->validated();
        $perPage       = (int) ($filters['per_page'] ?? 20);
        $page          = (int) ($filters['page'] ?? 1);
        $categoryIds   = $this->categories->getDescendantIds($category);

        $paginator     = $this->products->paginate($country, $filters, $perPage, $categoryIds);
        $facets        = $this->products->facets($country, $filters, $categoryIds);
        $productsPayload = $this->products->buildProductsPayload($paginator, $country, $page, 'category_top');

        $pageBlocks    = $this->categories->resolvePageBlocks($category, $country);

        return response()->json([
            'success' => true,
            'data'    => [
                'category'    => (new CategoryPageResource($category))->toArray($request),
                'page_blocks' => $pageBlocks,
                'products'    => array_merge($productsPayload, ['facets' => $facets]),
            ],
        ]);
    }
}
