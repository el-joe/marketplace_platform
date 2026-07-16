<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'success' => true,
            'data' => [
                'items' => collect($listings->items())->map(fn (AdminProductListing $listing) => $this->itemShape($listing))->values()->all(),
                'meta' => [
                    'current_page' => $listings->currentPage(),
                    'last_page' => $listings->lastPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                ],
            ],
        ]);
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

        return ApiResponse::success(
            $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
            ])->values()->all()
        );
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
            'listing' => $this->itemShape($listing),
        ]);
    }

    private function itemShape(AdminProductListing $listing): array
    {
        $product = $listing->productVariant->product;
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $listing->id,
            'price' => $listing->price,
            'currency' => $listing->currency,
            'payment_options' => $listing->payment_options,
            'fulfillment_type' => $listing->fulfillment_type,
            'is_exclusive' => (bool) $listing->is_exclusive,
            'rating_avg' => (float) $listing->rating_avg,
            'rating_count' => (int) $listing->rating_count,
            'product' => [
                'name_en' => $product->name_en,
                'name_ar' => $product->name_ar,
                'slug' => $product->slug,
                'primary_image_url' => $primaryImage?->url,
            ],
            'variant' => [
                'attributes' => $listing->productVariant->attributeValues->map(fn ($av) => [
                    'name_en' => $av->attribute?->name_en,
                    'name_ar' => $av->attribute?->name_ar,
                    'value_en' => $av->value_en,
                    'value_ar' => $av->value_ar,
                ])->values()->all(),
            ],
            'category' => $listing->nawyCategory ? [
                'id' => $listing->nawyCategory->id,
                'name_en' => $listing->nawyCategory->name_en,
                'name_ar' => $listing->nawyCategory->name_ar,
            ] : null,
            'fulfillment_badge' => $this->fulfillmentBadge($listing->fulfillment_type),
        ];
    }

    private function fulfillmentBadge(?string $fulfillmentType): array
    {
        return match ($fulfillmentType) {
            'express' => ['label_en' => 'Express', 'label_ar' => 'إكسبريس', 'color' => 'green'],
            'global' => ['label_en' => 'Global', 'label_ar' => 'عالمي', 'color' => 'blue'],
            'mixed' => ['label_en' => 'Express & Global', 'label_ar' => 'إكسبريس وعالمي', 'color' => 'purple'],
            default => ['label_en' => 'Global', 'label_ar' => 'عالمي', 'color' => 'blue'],
        };
    }
}
