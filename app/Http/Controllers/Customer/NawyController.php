<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\NawyCategoryResource;
use App\Http\Resources\Customer\NawyListingResource;
use App\Http\Responses\ApiResponse;
use App\Enums\AdminProductListingStatus;
use App\Models\AdminProductListing;
use App\Models\AppContext;
use App\Models\AppContextCountry;
use App\Models\Category;
use App\Models\Country;
use App\Models\Page;
use App\Services\AppContextService;
use App\Services\Customer\PageRendererService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NawyController extends Controller
{
    public function __construct(
        private readonly AppContextService $appContext,
        private readonly PageRendererService $renderer,
    ) {
    }

    /**
     * GET /api/customer/v1/nawy/feed
     */
    public function feed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'fulfillment_type' => ['nullable', Rule::in(['express', 'global', 'all'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $fulfillmentType = $validated['fulfillment_type'] ?? 'all';
        $perPage = $validated['per_page'] ?? 20;

        $listings = AdminProductListing::query()
            ->where('country_id', $validated['country_id'])
            ->where('featured_in_nawy', true)
            ->where('status', 'active')
            ->when($fulfillmentType !== 'all', fn ($q) => $q->where('fulfillment_type', $fulfillmentType))
            ->when(!empty($validated['category_id']), fn ($q) => $q->where('nawy_category_id', $validated['category_id']))
            ->with([
                'productVariant.product.images',
                'productVariant.attributeValues.attribute',
                'nawyCategory',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated($listings, NawyListingResource::class);
    }

    /**
     * GET /api/customer/v1/nawy/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        $categoryIds = DB::table('admin_product_listings')
            ->distinct()
            ->where('country_id', $validated['country_id'])
            ->where('featured_in_nawy', true)
            ->where('status', 'active')
            ->whereNotNull('nawy_category_id')
            ->pluck('nawy_category_id');

        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(NawyCategoryResource::collection($categories));
    }

    /**
     * GET /api/customer/v1/nawy-categories
     */
    public function nawyCategories(): JsonResponse
    {
        if (!$this->appContext->isNawyNow()) {
            return ApiResponse::error(__('common.exceptions.nawy.context_required'), [], 400);
        }

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where('is_visible', 1)
            ->withCount(['adminProductListings as listing_count' => function ($query) {
                $query->where('featured_in_nawy', 1)
                    ->where('status', AdminProductListingStatus::Active->value);
            }])
            ->whereHas('adminProductListings', function ($query) {
                $query->where('featured_in_nawy', 1)
                    ->where('status', AdminProductListingStatus::Active->value);
            })
            ->orderBy('nawy_sort_order')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'nawy_sort_order' => $category->nawy_sort_order,
                'nawy_icon_path' => $category->nawy_icon_path,
                'nawy_is_featured' => (bool) $category->nawy_is_featured,
                'listing_count' => $category->listing_count,
            ]);

        return ApiResponse::success($categories);
    }

    /**
     * GET /api/customer/v1/nawy-home
     */
    public function home(Request $request): JsonResponse
    {
        if (!$this->appContext->isNawyNow()) {
            return ApiResponse::error(__('common.exceptions.nawy.context_required'), [], 400);
        }

        $customer = $request->user('customer');
        $countryId = $customer?->country_id ?? $request->header('X-Country-Id');

        if (!$countryId) {
            return ApiResponse::error(__('common.exceptions.nawy.country_unresolved'), [], 400);
        }

        $country = Country::find($countryId);

        if (!$country) {
            return ApiResponse::error(__('common.exceptions.nawy.country_unresolved'), [], 400);
        }

        $appContextRow = AppContext::where('key', 'nawy_now')->where('is_active', true)->first();

        $contextCountry = $appContextRow
            ? AppContextCountry::where('app_context_id', $appContextRow->id)
                ->where('country_id', $country->id)
                ->where('is_active', true)
                ->first()
            : null;

        if (!$contextCountry || !$contextCountry->home_page_id) {
            return ApiResponse::success(['page_blocks' => []]);
        }

        $page = Page::where('id', $contextCountry->home_page_id)
            ->where('status', 'published')
            ->first();

        if (!$page) {
            return ApiResponse::success(['page_blocks' => []]);
        }

        $sessionId = $request->header('X-Session-Id') ?? $request->cookie('session_id') ?? session()->getId();

        $result = $this->renderer->renderPage($page, $country, $customer, (string) $sessionId, 'nawy_now');

        return ApiResponse::success($result ?: ['page_blocks' => []]);
    }

    /**
     * GET /api/customer/v1/nawy/{id}
     */
    public function show(string $id): JsonResponse
    {
        $listing = AdminProductListing::query()
            ->where('status', 'active')
            ->with([
                'productVariant.product.images',
                'productVariant.product.category',
                'productVariant.product.brand',
                'productVariant.attributeValues.attribute',
                'nawyCategory',
                'country',
            ])
            ->find($id);

        if (!$listing) {
            return ApiResponse::error(__('common.exceptions.nawy.listing_not_found'), [], 404);
        }

        return ApiResponse::success([
            'listing' => new NawyListingResource($listing),
        ]);
    }
}
