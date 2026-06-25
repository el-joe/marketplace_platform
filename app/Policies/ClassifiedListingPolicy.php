<?php

namespace App\Policies;

use App\Models\ClassifiedInquiry;
use App\Models\ClassifiedListing;
use App\Models\Vendor;
use App\Models\VendorAdmin;

class ClassifiedListingPolicy
{
    private function ownsListing(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $listing->seller_type === Vendor::class
            && $listing->seller_id === $admin->vendor_id;
    }

    public function view(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing);
    }

    public function update(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing)
            && in_array($listing->status, ['draft', 'rejected'], true);
    }

    public function pause(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing) && $listing->status === 'active';
    }

    public function resume(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing) && $listing->status === 'paused';
    }

    public function markSold(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing)
            && in_array($listing->status, ['active', 'paused'], true);
    }

    public function delete(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing)
            && in_array($listing->status, ['draft', 'rejected', 'expired'], true);
    }

    public function viewContract(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing)
            && $listing->status === 'pending_contract';
    }

    public function acceptContract(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing)
            && $listing->status === 'pending_contract';
    }

    public function viewInquiries(VendorAdmin $admin, ClassifiedListing $listing): bool
    {
        return $this->ownsListing($admin, $listing);
    }
}
