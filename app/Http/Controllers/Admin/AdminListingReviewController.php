<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use Illuminate\View\View;

class AdminListingReviewController extends Controller
{
    public function index(string $listingId): View
    {
        $listing = AdminProductListing::findOrFail($listingId);

        $reviews = $listing->reviews()
            ->with('customer')
            ->latest()
            ->paginate(10);

        return view('admin.admin-product-listings.partials.reviews-table', compact('reviews'));
    }
}
