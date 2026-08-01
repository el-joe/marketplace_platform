<?php

namespace App\Observers;

use App\Models\AdminProductListing;
use App\Services\CachedListingResolver;

class AdminProductListingObserver
{
    public function __construct(
        private readonly CachedListingResolver $cachedListingResolver,
    ) {
    }

    public function created(AdminProductListing $listing): void
    {
        $this->cachedListingResolver->bustAdminListing($listing);
    }

    public function updated(AdminProductListing $listing): void
    {
        if ($listing->wasChanged(['status', 'price', 'score', 'rating_avg', 'rating_count'])) {
            $this->cachedListingResolver->bustAdminListing($listing);
        }
    }

    public function deleted(AdminProductListing $listing): void
    {
        $this->cachedListingResolver->bustAdminListing($listing);
    }
}
