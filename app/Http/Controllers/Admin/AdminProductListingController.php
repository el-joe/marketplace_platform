<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\Country;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProductListingController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $listings = AdminProductListing::with(['productVariant.product', 'country', 'nawyCategory'])
            ->when($search, fn($q) => $q->whereHas('productVariant.product', function ($pq) use ($search) {
                $pq->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('short_desc_en', 'like', "%{$search}%")
                    ->orWhere('model_number', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.admin-product-listings.index', [
            'listings'    => $listings,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings'],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-product-listings.create', [
            'countries'       => Country::orderBy('name_en')->get(),
            'nawyCategories'  => Category::orderBy('name_en')->get(),
            'productVariants' => ProductVariant::with('product')->get(),
            'breadcrumbs'     => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings', 'url' => route('admin.admin-product-listings.index')],
                ['label' => 'New Listing'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['id'] = Str::uuid()->toString();
        $data['created_by_admin_id'] = auth('admin')->id();

        AdminProductListing::create($data);

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing created successfully.');
    }

    public function edit(AdminProductListing $adminProductListing): View
    {
        return view('admin.admin-product-listings.edit', [
            'listing'         => $adminProductListing,
            'countries'       => Country::orderBy('name_en')->get(),
            'nawyCategories'  => Category::orderBy('name_en')->get(),
            'productVariants' => ProductVariant::with('product')->get(),
            'breadcrumbs'     => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings', 'url' => route('admin.admin-product-listings.index')],
                ['label' => 'Edit Listing'],
            ],
        ]);
    }

    public function update(Request $request, AdminProductListing $adminProductListing): RedirectResponse
    {
        $data = $request->validate($this->rules($adminProductListing));
        $adminProductListing->update($data);

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing updated successfully.');
    }

    public function destroy(AdminProductListing $adminProductListing): RedirectResponse
    {
        $adminProductListing->delete();

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing removed.');
    }

    /** Nawy preview: render the storefront feed as it would appear. */
    public function nawyPreview(AdminProductListing $adminProductListing): View
    {
        $listing = $adminProductListing->load(['productVariant.product.images', 'nawyCategory', 'country']);

        return view('admin.admin-product-listings.nawy-preview', compact('listing'));
    }

    private function rules(?AdminProductListing $listing = null): array
    {
        return [
            'product_variant_id'   => ['required', 'exists:product_variants,id'],
            'country_id'           => ['required', 'exists:countries,id'],
            'price_cents'          => ['required', 'integer', 'min:0'],
            'cost_price_cents'     => ['nullable', 'integer', 'min:0'],
            'commission_type'      => ['required', Rule::in(['fixed', 'percentage', 'mixed'])],
            'commission_value'     => ['required', 'numeric', 'min:0'],
            'currency'             => ['required', 'string', 'size:3'],
            'payment_options'      => ['required', Rule::in(['cod_only', 'electronic_only', 'both'])],
            'fulfillment_type'     => ['required', Rule::in(['express', 'global', 'mixed'])],
            'featured_in_nawy'     => ['boolean'],
            'nawy_category_id'     => ['nullable', 'exists:categories,id'],
            'shipping_cost_cents'  => ['required', 'integer', 'min:0'],
            'is_exclusive'         => ['boolean'],
            'status'               => ['required', Rule::in(['active', 'paused', 'archived'])],
            'available_for_vendors'    => ['boolean'],
            'available_for_marketers'  => ['boolean'],
        ];
    }
}
