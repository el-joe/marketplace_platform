<?php

namespace App\Observers;

use App\Jobs\RecomputeListingShippingMethodsJob;
use App\Models\VendorListing;

class VendorListingObserver
{
    public function created(VendorListing $listing): void
    {
        dispatch(new RecomputeListingShippingMethodsJob(
            [$listing->productVariant->product->category_id]
        ));
    }

    public function updated(VendorListing $listing): void
    {
        if ($listing->wasChanged('global_system_type')) {
            dispatch(new RecomputeListingShippingMethodsJob(
                [$listing->productVariant->product->category_id]
            ));
        }
    }
}
