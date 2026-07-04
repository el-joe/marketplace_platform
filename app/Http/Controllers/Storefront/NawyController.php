<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Models\VendorListing;
use App\Services\ListingShippingResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NawyController extends Controller
{
    public function index(string $country, Request $request): View
    {
        $countryModel = Country::where('iso_code_2', strtoupper($country))
            ->orWhere('slug', $country)
            ->firstOrFail();

        $filter = $request->query('type'); // express | global | null

        $listings = AdminProductListing::where('status', 'active')
            ->where('country_id', $countryModel->id)
            ->where('featured_in_nawy', 1)
            ->when($filter === 'express', fn($q) => $q->where('fulfillment_type', 'express'))
            ->when($filter === 'global',  fn($q) => $q->where('fulfillment_type', 'global'))
            ->with(['productVariant.product.images'])
            ->paginate(20)
            ->withQueryString();

        $categories = Category::whereIn('id', function ($q) use ($countryModel) {
            $q->select('nawy_category_id')
                ->from('admin_product_listings')
                ->where('status', 'active')
                ->where('country_id', $countryModel->id)
                ->where('featured_in_nawy', 1)
                ->whereNotNull('nawy_category_id')
                ->distinct();
        })->get();

        $shippingMethods = $this->shippingMethodsByCode();

        return view('storefront.nawy.index', compact('listings', 'categories', 'filter', 'countryModel', 'shippingMethods'));
    }

    public function byCategory(string $country, Category $category, Request $request): View
    {
        $countryModel = Country::where('iso_code_2', strtoupper($country))
            ->orWhere('slug', $country)
            ->firstOrFail();

        $filter = $request->query('type');

        $listings = AdminProductListing::where('status', 'active')
            ->where('country_id', $countryModel->id)
            ->where('featured_in_nawy', 1)
            ->where('nawy_category_id', $category->id)
            ->when($filter === 'express', fn($q) => $q->where('fulfillment_type', 'express'))
            ->when($filter === 'global',  fn($q) => $q->where('fulfillment_type', 'global'))
            ->with(['productVariant.product.images'])
            ->paginate(20)
            ->withQueryString();

        $categories = Category::whereIn('id', function ($q) use ($countryModel) {
            $q->select('nawy_category_id')
                ->from('admin_product_listings')
                ->where('status', 'active')
                ->where('country_id', $countryModel->id)
                ->where('featured_in_nawy', 1)
                ->whereNotNull('nawy_category_id')
                ->distinct();
        })->get();

        $shippingMethods = $this->shippingMethodsByCode();

        return view('storefront.nawy.index', compact('listings', 'categories', 'filter', 'countryModel', 'category', 'shippingMethods'));
    }

    public function show(string $country, AdminProductListing $listing, Request $request): View
    {
        $countryModel = Country::where('iso_code_2', strtoupper($country))
            ->orWhere('slug', $country)
            ->firstOrFail();

        $listing->load(['productVariant.product.images', 'productVariant.product.brand', 'productVariant.product.category']);

        // Resolve delivery options via best VendorListing for this variant in this country.
        // Falls back to an empty collection if no vendor listing exists.
        $deliveryOptions = collect();
        $vendorListing = VendorListing::where('product_variant_id', $listing->product_variant_id)
            ->where('country_id', $countryModel->id)
            ->where('status', 'active')
            ->with(['primaryShippingMethod'])
            ->orderByDesc('score')
            ->first();

        if ($vendorListing) {
            $deliveryOptions = app(ListingShippingResolver::class)->resolveForListing($vendorListing);
        }

        return view('storefront.nawy.show', compact('listing', 'countryModel', 'deliveryOptions', 'vendorListing'));
    }

    private function shippingMethodsByCode(): Collection
    {
        return ShippingMethod::where('is_active', true)
            ->whereNotNull('badge_label_en')
            ->where('badge_label_en', '!=', '')
            ->get()
            ->keyBy('code');
    }
}
