<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\NawyCategoryResource;
use App\Http\Resources\Customer\NawyListingResource;
use App\Http\Responses\ApiResponse;
use App\Models\AdminProductListing;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NawyController extends Controller
{
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
            return ApiResponse::error('Listing not found or not available.', [], 404);
        }

        return ApiResponse::success([
            'listing' => new NawyListingResource($listing),
        ]);
    }
}
