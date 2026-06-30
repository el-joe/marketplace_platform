<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MarketerSampleRequest;
use App\Models\VendorListing;

class SampleQuotaResolver
{
    public function resolveFromListingIds(array $listingIds): ?Category
    {
        $firstId = $listingIds[0] ?? null;
        if (!$firstId) {
            return null;
        }

        return VendorListing::with('productVariant.product.category')
            ->find($firstId)
            ?->productVariant?->product?->category;
    }

    public function resolveFromRequest(MarketerSampleRequest $req): ?Category
    {
        $firstListingId = $req->items()->value('vendor_listing_id');
        if (!$firstListingId) {
            return null;
        }

        return VendorListing::with('productVariant.product.category')
            ->find($firstListingId)
            ?->productVariant?->product?->category;
    }
}
