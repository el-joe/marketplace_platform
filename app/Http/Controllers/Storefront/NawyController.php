<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;
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

        return view('storefront.nawy.index', compact('listings', 'categories', 'filter', 'countryModel'));
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

        return view('storefront.nawy.index', compact('listings', 'categories', 'filter', 'countryModel', 'category'));
    }
}
