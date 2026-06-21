<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\WishlistStoreRequest;
use App\Http\Resources\Customer\WishlistResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index(Country $country): JsonResponse
    {
        $items = Wishlist::where('customer_id', auth('customer')->id())
            ->with(['product.images'])
            ->latest('added_at')
            ->paginate(20);

        return ApiResponse::paginated($items, WishlistResource::class);
    }

    public function store(WishlistStoreRequest $request, Country $country): JsonResponse
    {
        $customerId = auth('customer')->id();
        $data       = $request->validated();

        $existing = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            return ApiResponse::error('Product already in wishlist', [], 422);
        }

        $wishlist = Wishlist::create([
            'id'                 => Str::uuid(),
            'customer_id'        => $customerId,
            'product_id'         => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
        ]);

        $wishlist->load('product.images');

        return ApiResponse::success(
            (new WishlistResource($wishlist))->toArray($request),
            'Added to wishlist',
            201,
        );
    }

    public function destroy(Country $country, string $productId): JsonResponse
    {
        $deleted = Wishlist::where('customer_id', auth('customer')->id())
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            return ApiResponse::error('Item not found in wishlist', [], 404);
        }

        return ApiResponse::success(null, 'Removed from wishlist');
    }
}
