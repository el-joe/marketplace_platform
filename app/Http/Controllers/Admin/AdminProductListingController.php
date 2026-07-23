<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminProductListingCommissionType;
use App\Enums\AdminProductListingStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\Country;
use App\Models\ProductCostReference;
use App\Models\ProductVariant;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProductListingController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        return view('admin.admin-product-listings.index', [
            'countries' => Country::query()->orderBy('name_en')->pluck('name_en', 'id'),
            'nawyCategories' => Category::query()->orderBy('name_en')->pluck('name_en', 'id'),
            'stats' => $this->buildStats(),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings'],
            ],
        ]);
    }

    /** Quick stats block for the Nawy Now index page. */
    private function buildStats(): array
    {
        $activeListings = AdminProductListing::where('status', AdminProductListingStatus::Active)->count();

        $countriesActive = AdminProductListing::where('status', AdminProductListingStatus::Active)
            ->distinct()
            ->count('country_id');

        $revenueByCurrency = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.admin_product_listing_id')
            ->select('orders.currency', DB::raw('SUM(order_items.line_total) as total'))
            ->groupBy('orders.currency')
            ->orderByDesc('total')
            ->get();

        $topSelling = DB::table('order_items')
            ->join('admin_product_listings as apl', 'apl.id', '=', 'order_items.admin_product_listing_id')
            ->join('product_variants as pv', 'pv.id', '=', 'apl.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->whereNotNull('order_items.admin_product_listing_id')
            ->select('p.name_en', DB::raw('SUM(order_items.quantity) as units_sold'))
            ->groupBy('p.name_en')
            ->orderByDesc('units_sold')
            ->first();

        return [
            'active_listings' => $activeListings,
            'countries_active' => $countriesActive,
            'revenue_by_currency' => $revenueByCurrency,
            'top_selling' => $topSelling,
        ];
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['p.name_en', 'p.name_ar', 'pv.sku'], 'orderable_column' => 'p.name_en'],
            ['orderable_column' => 'co.name_en'],
            ['orderable_column' => 'admin_product_listings.price'],
            ['orderable_column' => 'admin_product_listings.fulfillment_type'],
            ['orderable_column' => 'admin_product_listings.payment_options'],
            ['orderable_column' => 'admin_product_listings.featured_in_nawy'],
            ['orderable_column' => 'admin_product_listings.status'],
            [],
        ];

        $query = AdminProductListing::query()
            ->join('product_variants as pv', 'pv.id', '=', 'admin_product_listings.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->join('countries as co', 'co.id', '=', 'admin_product_listings.country_id')
            ->whereNull('p.deleted_at')
            ->select([
                'admin_product_listings.id',
                'admin_product_listings.price',
                'admin_product_listings.currency',
                'admin_product_listings.fulfillment_type',
                'admin_product_listings.payment_options',
                'admin_product_listings.featured_in_nawy',
                'admin_product_listings.status',
                'admin_product_listings.nawy_category_id',
                'p.name_en as product_name',
                'pv.sku as variant_sku',
                'co.name_en as country_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'country_id' => fn($q, $v) => $q->where('admin_product_listings.country_id', $v),
            'fulfillment_type' => fn($q, $v) => $q->where('admin_product_listings.fulfillment_type', $v),
            'nawy_category_id' => fn($q, $v) => $q->where('admin_product_listings.nawy_category_id', $v),
            'status' => fn($q, $v) => $q->where('admin_product_listings.status', $v),
        ]);

        $paymentLabels = ['cod_only' => 'COD only', 'electronic_only' => 'Electronic only', 'both' => 'COD + Electronic'];

        return $this->dataTableResponse($request, $query, $columns, function ($row) use ($paymentLabels) {
            return [
                'id' => $row->id,
                'product_name' => e($row->product_name),
                'variant_sku' => e($row->variant_sku),
                'country' => e($row->country_name),
                'price' => number_format($row->price / 100, 2) . ' ' . $row->currency,
                'fulfillment_type' => $row->fulfillment_type,
                'payment_options' => $paymentLabels[$row->payment_options] ?? $row->payment_options,
                'featured_in_nawy' => (bool) $row->featured_in_nawy,
                'status' => $row->status?->value,
                'show_url' => route('admin.admin-product-listings.show', $row->id),
                'edit_url' => route('admin.admin-product-listings.edit', $row->id),
                'delete_url' => route('admin.admin-product-listings.destroy', $row->id),
                'toggle_featured_url' => route('admin.admin-product-listings.toggle-featured', $row->id),
            ];
        });
    }

    public function toggleFeatured(AdminProductListing $adminProductListing): JsonResponse
    {
        $adminProductListing->featured_in_nawy = !$adminProductListing->featured_in_nawy;
        $adminProductListing->save();

        return response()->json([
            'success' => true,
            'featured_in_nawy' => (bool) $adminProductListing->featured_in_nawy,
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-product-listings.create', [
            'countries'       => Country::orderBy('name_en')->get(),
            'nawyCategories'  => Category::orderBy('name_en')->get(),
            'selectedVariant' => null,
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
        $data['currency'] = Country::findOrFail($data['country_id'])->currency_code;

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
            'selectedVariant' => $adminProductListing->productVariant()->with('product')->first(),
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
        $data['currency'] = Country::findOrFail($data['country_id'])->currency_code;
        $adminProductListing->update($data);

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing updated successfully.');
    }

    public function destroy(AdminProductListing $adminProductListing): JsonResponse|RedirectResponse
    {
        $adminProductListing->status = AdminProductListingStatus::Archived;
        $adminProductListing->save();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Listing archived.']);
        }

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing archived.');
    }

    /** Nawy preview: render the storefront feed as it would appear. */
    public function nawyPreview(AdminProductListing $adminProductListing): View
    {
        $listing = $adminProductListing->load(['productVariant.product.images', 'nawyCategory', 'country']);

        return view('admin.admin-product-listings.nawy-preview', compact('listing'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Show (tabbed: listing details + confidential product reference)
    // ──────────────────────────────────────────────────────────────────────────

    public function show(AdminProductListing $adminProductListing): View
    {
        $adminProductListing->load(['productVariant.product', 'country', 'nawyCategory']);

        $productId = $adminProductListing->productVariant->product_id;
        $costReference = ProductCostReference::where('product_id', $productId)->first();

        return view('admin.admin-product-listings.show', [
            'listing' => $adminProductListing,
            'costReference' => $costReference,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings', 'url' => route('admin.admin-product-listings.index')],
                ['label' => e($adminProductListing->productVariant->product->name_en)],
            ],
        ]);
    }

    /**
     * Save (create or update) the confidential product-cost reference for this
     * listing's underlying product. Independent of the main listing form.
     */
    public function saveReference(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        $productId = $adminProductListing->productVariant->product_id;

        $data = $request->validate([
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'manufacturer_url' => ['nullable', 'url', 'max:500'],
            'manufacturer_sku' => ['nullable', 'string', 'max:100'],
            'manufacturer_cost' => ['nullable', 'integer', 'min:0'],
            'shipping_cost' => ['nullable', 'integer', 'min:0'],
            'landed_cost' => ['nullable', 'integer', 'min:0'],
            'competitor_links' => ['nullable', 'array'],
            'competitor_links.*.name' => ['required_with:competitor_links', 'string', 'max:255'],
            'competitor_links.*.url' => ['required_with:competitor_links', 'url', 'max:500'],
            'competitor_links.*.price' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $adminId = Auth::guard('admin')->id();

        $landed = $data['landed_cost']
            ?? (($data['manufacturer_cost'] ?? 0) + ($data['shipping_cost'] ?? 0));
        $marginPct = ($landed > 0 && $adminProductListing->price > 0)
            ? round(($adminProductListing->price - $landed) / $adminProductListing->price * 100, 2)
            : null;

        $ref = ProductCostReference::where('product_id', $productId)->first();

        if ($ref) {
            $ref->fill(array_merge($data, [
                'platform_margin_pct' => $marginPct,
                'updated_by_admin_id' => $adminId,
            ]));
            $ref->save();
            $message = 'Product reference updated.';
        } else {
            $ref = ProductCostReference::create(array_merge($data, [
                'product_id' => $productId,
                'platform_margin_pct' => $marginPct,
                'is_confidential' => 1,
                'created_by_admin_id' => $adminId,
            ]));
            $message = 'Product reference created.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'ref' => $this->serializeRef($ref->fresh()),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Nawy Now sidebar category management
    // ──────────────────────────────────────────────────────────────────────────

    /** Sortable list of categories that have at least one active admin listing. */
    public function categories(): View
    {
        $categories = Category::query()
            ->withCount(['adminProductListings as product_count' => function ($q) {
                $q->where('status', AdminProductListingStatus::Active);
            }])
            ->whereHas('adminProductListings', function ($q) {
                $q->where('status', AdminProductListingStatus::Active);
            })
            ->orderBy('nawy_is_featured', 'desc')
            ->orderBy('nawy_sort_order')
            ->orderBy('name_en')
            ->get();

        return view('admin.admin-product-listings.categories', [
            'categories' => $categories,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings', 'url' => route('admin.admin-product-listings.index')],
                ['label' => 'Nawy Now Sidebar Categories'],
            ],
        ]);
    }

    /** Persist drag-and-drop order for the Nawy sidebar. */
    public function reorderCategories(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:categories,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            Category::where('id', $id)->update(['nawy_sort_order' => $position]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /** Upload/replace the Nawy sidebar icon for a category. */
    public function saveCategoryIcon(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'icon' => ['required', 'image', 'max:1024'],
        ]);

        if ($category->nawy_icon_path) {
            Storage::disk('public')->delete($category->nawy_icon_path);
        }

        $category->nawy_icon_path = $request->file('icon')->store('nawy-category-icons', 'public');
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Icon updated.',
            'icon_url' => Storage::disk('public')->url($category->nawy_icon_path),
        ]);
    }

    /** Toggle whether a category is pinned to the top of the Nawy sidebar. */
    public function toggleCategoryFeatured(Category $category): JsonResponse
    {
        $category->nawy_is_featured = !$category->nawy_is_featured;
        $category->save();

        return response()->json([
            'success' => true,
            'nawy_is_featured' => (bool) $category->nawy_is_featured,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Select2 search endpoints
    // ──────────────────────────────────────────────────────────────────────────

    public function searchVariants(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        $variants = ProductVariant::query()
            ->join('products as p', 'p.id', '=', 'product_variants.product_id')
            ->whereNull('product_variants.deleted_at')
            ->whereNull('p.deleted_at')
            ->where(function ($q) use ($term) {
                $q->where('p.name_en', 'like', "%{$term}%")
                    ->orWhere('product_variants.sku', 'like', "%{$term}%");
            })
            ->orderBy('p.name_en')
            ->limit(30)
            ->get(['product_variants.id', 'p.name_en', 'product_variants.sku', 'product_variants.variant_name']);

        return response()->json([
            'results' => $variants->map(fn($v) => [
                'id' => $v->id,
                'text' => $v->name_en . ($v->variant_name ? " ({$v->variant_name})" : '') . " — {$v->sku}",
            ]),
        ]);
    }

    private function serializeRef(ProductCostReference $ref): array
    {
        return [
            'id' => $ref->id,
            'product_id' => $ref->product_id,
            'manufacturer_name' => $ref->manufacturer_name,
            'manufacturer_url' => $ref->manufacturer_url,
            'manufacturer_sku' => $ref->manufacturer_sku,
            'manufacturer_cost' => $ref->manufacturer_cost,
            'shipping_cost' => $ref->shipping_cost,
            'landed_cost' => $ref->landed_cost,
            'platform_margin_pct' => $ref->platform_margin_pct,
            'competitor_links' => $ref->competitorLinksNormalized(),
            'competitor_last_checked' => $ref->competitor_last_checked?->toISOString(),
            'notes' => $ref->notes,
            'created_by' => $ref->createdByAdmin?->name ?? 'System',
            'updated_by' => $ref->updatedByAdmin?->name ?? null,
            'updated_at' => $ref->updated_at?->toISOString(),
            'manufacturer_cost_formatted' => $ref->manufacturerCostFormatted(),
            'shipping_cost_formatted' => $ref->shippingCostFormatted(),
            'landed_cost_formatted' => $ref->landedCostFormatted(),
        ];
    }

    private function rules(?AdminProductListing $listing = null): array
    {
        return [
            'product_variant_id'   => ['required', 'exists:product_variants,id'],
            'country_id'           => ['required', 'exists:countries,id'],
            'price'          => ['required', 'integer', 'min:0'],
            'cost_price'     => ['nullable', 'integer', 'min:0'],
            'commission_type'      => ['required', Rule::enum(AdminProductListingCommissionType::class)],
            'commission_value'     => ['required', 'numeric', 'min:0'],
            'payment_options'      => ['required', Rule::in(['cod_only', 'electronic_only', 'both'])],
            'fulfillment_type'     => ['required', Rule::in(['express', 'global', 'mixed'])],
            'featured_in_nawy'     => ['boolean'],
            'nawy_category_id'     => ['nullable', 'exists:categories,id'],
            'shipping_cost'  => ['required', 'integer', 'min:0'],
            'is_exclusive'         => ['boolean'],
            'status'               => ['required', Rule::enum(AdminProductListingStatus::class)],
            'available_for_vendors'    => ['boolean'],
            'available_for_marketers'  => ['boolean'],
        ];
    }
}
