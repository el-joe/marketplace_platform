<?php

namespace App\Observers;

use App\Http\Controllers\Api\Customer\ProductDetailController;
use App\Models\AdminProductListing;
use App\Services\CachedListingResolver;
use Illuminate\Support\Facades\Cache;

class AdminProductListingObserver
{
    public function __construct(
        private readonly CachedListingResolver $cachedListingResolver,
    ) {
    }

    public function created(AdminProductListing $listing): void
    {
        $this->forgetVariantMatrixCache($listing);
        $this->cachedListingResolver->bustAdminListing($listing);
    }

    public function updated(AdminProductListing $listing): void
    {
        if ($listing->wasChanged(['status', 'price'])) {
            $this->forgetVariantMatrixCache($listing);
        }

        if ($listing->wasChanged(['status', 'price', 'score', 'rating_avg', 'rating_count'])) {
            $this->cachedListingResolver->bustAdminListing($listing);
        }
    }

    public function deleted(AdminProductListing $listing): void
    {
        $this->forgetVariantMatrixCache($listing);
        $this->cachedListingResolver->bustAdminListing($listing);
    }

    private function forgetVariantMatrixCache(AdminProductListing $listing): void
    {
        $variant = $listing->productVariant;

        if (!$variant) {
            return;
        }

        $productId = $variant->product_id;

        foreach ($variant->product->variants()->pluck('id') as $variantId) {
            Cache::forget(
                ProductDetailController::variantMatrixCacheKey($productId, $listing->country_id, true) . ".{$variantId}"
            );
        }
    }
}
