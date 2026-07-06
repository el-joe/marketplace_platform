<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClassifiedCategory;
use App\Models\Country;
use App\Models\TravelCategory;
use App\Services\Customer\CategoryService;
use App\Services\Customer\ListingQueryService;
use App\Services\Customer\ProductQueryService;
use App\Services\Customer\UnifiedCategoryService;
use App\Services\Shared\PageBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrowseController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly ProductQueryService $products,
        private readonly ListingQueryService $listings,
        private readonly UnifiedCategoryService $unifiedCategories,
        private readonly PageBuilderService $pageBuilder,
    ) {}

    /**
     * GET /api/customer/v1/{country}/browse/{type}/{id}
     */
    public function show(Request $request, $country, string $type, string $id): JsonResponse
    {
        $country = $request->attributes->get('country');

        $request->validate([
            'type' => [Rule::in(['product', 'classified', 'travel'])],
        ]);

        if (!in_array($type, ['product', 'classified', 'travel'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid browse type.'], 404);
        }

        return match ($type) {
            'product'    => $this->browseProduct($request, $country, $id),
            'classified' => $this->browseClassified($request, $country, $id),
            'travel'     => $this->browseTravel($request, $country, $id),
        };
    }

    /**
     * GET /api/customer/v1/{country}/travel
     * Same as browse/travel/all — every active package, unfiltered by category.
     */
    public function travelIndex(Request $request, $country): JsonResponse
    {
        $country = $request->attributes->get('country');
        return $this->browseTravel($request, $country, 'all');
    }

    // ── Products ──────────────────────────────────────────────────────────────

    private function browseProduct(Request $request, $country, string $id): JsonResponse
    {
        $country = $request->attributes->get('country');

        $category = Category::where('id', $id)->where('is_active', true)->firstOrFail();

        $filters     = $request->only([
            'price_min', 'price_max', 'brand', 'rating_min', 'condition',
            'fulfillment_model', 'include_oos', 'attributes', 'sort',
        ]);
        $perPage     = $request->integer('per_page', 20);
        $page        = $request->integer('page', 1);
        $categoryIds = $this->categories->getDescendantIds($category);

        $categoryNode = $this->unifiedCategories->findById($id, 'product');

        $pageBuilder = $this->pageBuilder->resolve(
            $country,
            'category',
            $category->id,
            $this->pageBuilder->detectDevice($request),
            auth('customer')->check() ? 'authenticated' : 'guest',
        );

        $paginator = $this->products->paginate($country, $filters, $perPage, $categoryIds);
        $facets    = $this->products->facets($country, $filters, $categoryIds);
        $payload   = $this->products->buildProductsPayload($paginator, $country, $page, 'category_top');

        return response()->json([
            'success' => true,
            'data'    => [
                'category'      => $this->productCategoryPayload($category),
                'category_node' => $categoryNode,
                'page_builder'  => $pageBuilder,
                'listings'      => array_merge($payload, ['facets' => $facets]),
            ],
        ]);
    }

    private function productCategoryPayload(Category $category): array
    {
        return [
            'id'             => $category->id,
            'name_en'        => $category->name_en,
            'name_ar'        => $category->name_ar,
            'slug'           => $category->slug,
            'description_en' => $category->description_en,
            'description_ar' => $category->description_ar,
            'parent'         => $category->parent ? [
                'id'      => $category->parent->id,
                'name_en' => $category->parent->name_en,
                'name_ar' => $category->parent->name_ar,
                'slug'    => $category->parent->slug,
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

    // ── Classifieds ───────────────────────────────────────────────────────────

    private function browseClassified(Request $request, $country, string $id): JsonResponse
    {
        $country = $request->attributes->get('country');

        $categoryNode = $this->unifiedCategories->findById($id, 'classified');

        if (!$categoryNode) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $category = ClassifiedCategory::where('id', $categoryNode['id'])->firstOrFail();

        $pageBuilder = $this->pageBuilder->resolve(
            $country,
            'category',
            $category->id,
            $this->pageBuilder->detectDevice($request),
            auth('customer')->check() ? 'authenticated' : 'guest',
        );

        $perPage   = $request->integer('per_page', 20);
        $paginator = $this->listings->paginateForClassifiedCategory($category->id, $perPage);

        $items = $paginator->getCollection()
            ->map(fn ($listing) => $this->listings->toClassifiedCardShape($listing))
            ->toArray();

        return response()->json([
            'success' => true,
            'data'    => [
                'category' => [
                    'id'          => $category->id,
                    'source_type' => 'classified',
                    'name_en'     => $category->name_en,
                    'name_ar'     => $category->name_ar,
                    'slug'        => $category->slug,
                    'icon'        => $category->icon,
                    'link'        => "/browse/classified/{$category->id}",
                ],
                'page_builder' => $pageBuilder,
                'listings'     => [
                    'items' => $items,
                    'meta'  => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                    ],
                ],
            ],
        ]);
    }

    // ── Travel ────────────────────────────────────────────────────────────────

    private function browseTravel(Request $request, $country, string $id): JsonResponse
    {
        $country = $request->attributes->get('country');

        $travelCategory = null;

        if ($id !== 'all' && $id !== '') {
            $travelCategory = TravelCategory::where('id', $id)->where('is_active', 1)->first();

            if (!$travelCategory) {
                return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
            }
        }

        $pageBuilder = $travelCategory
            ? $this->pageBuilder->resolve(
                $country,
                'category',
                $travelCategory->id,
                $this->pageBuilder->detectDevice($request),
                auth('customer')->check() ? 'authenticated' : 'guest',
            )
            : null;

        $perPage   = $request->integer('per_page', 20);
        $paginator = $this->listings->paginateTravelPackages($travelCategory?->id, $perPage);

        $items = $paginator->getCollection()
            ->map(fn ($package) => $this->listings->toTravelCardShape($package))
            ->toArray();

        $availableCategories = TravelCategory::where('is_active', 1)
            ->orderBy('sort_order')
            ->withCount('packages')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'name_en'        => $c->name_en,
                'name_ar'        => $c->name_ar,
                'slug'           => $c->slug,
                'icon'           => $c->icon,
                'package_count'  => $c->packages_count,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data'    => [
                'category' => [
                    'id'                   => $travelCategory?->id,
                    'source_type'          => 'travel',
                    'name_en'              => $travelCategory?->name_en ?? 'All Travel',
                    'name_ar'              => $travelCategory?->name_ar ?? 'كل رحلات السفر',
                    'slug'                 => $travelCategory?->slug ?? 'all',
                    'link'                 => '/travel',
                    'travel_category_slug' => $travelCategory?->slug,
                ],
                'available_categories' => $availableCategories,
                'page_builder'          => $pageBuilder,
                'listings'              => [
                    'items' => $items,
                    'meta'  => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                    ],
                ],
            ],
        ]);
    }
}
