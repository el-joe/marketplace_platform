<?php

namespace App\Services\Customer;

use App\Models\Country;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class BuyBoxService
{
    /**
     * Return active vendor listings for the product's variants in the given country,
     * buy-box winner (highest score) listed first.
     *
     * buy_box_winners table is not yet live; winner is derived from vendor_listings.score
     * which is populated by the platform's scoring job.
     */
    public function getListings(Product $product, Country $country): Collection
    {
        $variantIds = $product->variants()
            ->where('is_active', true)
            ->pluck('id');

        return \App\Models\VendorListing::query()
            ->with(['vendor:id,store_name,store_slug,store_rating_avg', 'warehouseInventories'])
            ->whereIn('product_variant_id', $variantIds)
            ->where('country_id', $country->id)
            ->where('status', 'active')
            ->whereHas('warehouseInventories', function ($q) {
                $q->where('quantity_available', '>', 0);
            })
            ->orderByDesc('score')
            ->orderBy('price')
            ->get();
    }
}
