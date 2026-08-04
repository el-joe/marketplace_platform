<?php

namespace App\Observers;

use App\Models\AdminListing;
use App\Services\CachedListingResolver;

class AdminListingObserver
{
    public function __construct(
        private readonly CachedListingResolver $cachedListingResolver,
    ) {
    }

    public function created(AdminListing $listing): void
    {
        $this->cachedListingResolver->bustAdminListing($listing);
    }

    public function updated(AdminListing $listing): void
    {
        if ($listing->wasChanged(['status', 'price', 'score', 'rating_avg', 'rating_count'])) {
            $this->cachedListingResolver->bustAdminListing($listing);
        }
    }

    public function deleted(AdminListing $listing): void
    {
        $this->cachedListingResolver->bustAdminListing($listing);
    }
}
