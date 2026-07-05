<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\WishlistStoreRequest;
use App\Http\Resources\Customer\WishlistResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\VendorListing;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index($country): JsonResponse
    {
        $country = request()->attributes->get("country");
        $items = Wishlist::where('customer_id', auth('customer')->id())
            ->with(['product.images'])
            ->latest('added_at')
            ->paginate(20);

        $items->getCollection()->transform(function (Wishlist $wishlistItem) use ($country) {
            $wishlistItem->setRelation('bestListing', $this->resolveBestListing($wishlistItem->product_id, $country));

            return $wishlistItem;
        });

        return ApiResponse::paginated($items, WishlistResource::class);
    }

    private function resolveBestListing(string $productId, $country): ?VendorListing
    {
        $country = request()->attributes->get("country");
        return VendorListing::whereHas(
            'productVariant',
            fn ($q) => $q->where('product_id', $productId)
        )
            ->where('country_id', $country->id)
            ->where('status', 'active')
            ->orderByRaw("FIELD(global_system_type,'express_fbn','merchant_fbp','marketplace')")
            ->orderBy('price')
            ->with([
                'productVariant:id,sku,product_id',
                'vendor:id,store_name',
                'primaryShippingMethod',
            ])
            ->first();
    }

    public function store(WishlistStoreRequest $request, $country): JsonResponse
    {
        $country = request()->attributes->get("country");
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

    public function destroy($country, string $productId): JsonResponse
    {
        $country = request()->attributes->get("country");
        $deleted = Wishlist::where('customer_id', auth('customer')->id())
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            return ApiResponse::error('Item not found in wishlist', [], 404);
        }

        return ApiResponse::success(null, 'Removed from wishlist');
    }
}
