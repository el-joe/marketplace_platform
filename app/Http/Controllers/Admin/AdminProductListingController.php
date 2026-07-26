<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminProductListingCommissionType;
use App\Enums\AdminProductListingStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Enums\MarketplaceShippingRuleCommissionType;
use App\Models\Country;
use App\Models\MarketplaceShippingRule;
use App\Models\ProductCostReference;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
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
            'statuses' => collect(AdminProductListingStatus::cases())
                ->mapWithKeys(fn($status) => [$status->value => Str::headline($status->value)]),
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
            ['searchable_columns' => ['p.name_en', 'p.name_ar', 'pv.sku', 'admin_product_listings.platform_sku'], 'orderable_column' => 'p.name_en'],
            ['orderable_column' => 'pv.variant_name'],
            ['orderable_column' => 'admin_product_listings.platform_sku'],
            ['searchable_columns' => ['co.name_en'], 'orderable_column' => 'co.name_en'],
            ['orderable_column' => 'admin_product_listings.currency'],
            ['orderable_column' => 'admin_product_listings.price'],
            ['orderable_column' => 'admin_product_listings.compare_at_price'],
            ['orderable_column' => 'admin_product_listings.status'],
            ['orderable_column' => 'admin_product_listings.fulfillment_type'],
            ['orderable_column' => 'admin_product_listings.featured_in_nawy'],
            ['orderable_column' => 'admin_product_listings.rating_avg'],
            ['orderable_column' => 'admin_product_listings.total_sold'],
            ['orderable_column' => 'wh.name'],
            ['orderable_column' => 'admin_product_listings.created_at'],
            [],
        ];

        $query = AdminProductListing::query()
            ->join('product_variants as pv', 'pv.id', '=', 'admin_product_listings.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->join('countries as co', 'co.id', '=', 'admin_product_listings.country_id')
            ->leftJoin('warehouses as wh', 'wh.id', '=', 'admin_product_listings.warehouse_id')
            ->whereNull('p.deleted_at')
            ->select([
                'admin_product_listings.id',
                'admin_product_listings.price',
                'admin_product_listings.compare_at_price',
                'admin_product_listings.currency',
                'admin_product_listings.platform_sku',
                'admin_product_listings.fulfillment_type',
                'admin_product_listings.payment_options',
                'admin_product_listings.featured_in_nawy',
                'admin_product_listings.status',
                'admin_product_listings.nawy_category_id',
                'admin_product_listings.rating_avg',
                'admin_product_listings.rating_count',
                'admin_product_listings.total_sold',
                'admin_product_listings.created_at',
                'p.name_en as product_name',
                'pv.sku as variant_sku',
                'pv.variant_name as variant_name',
                'co.name_en as country_name',
                'wh.name as warehouse_name',
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
                'variant_name' => e($row->variant_name),
                'variant_sku' => e($row->variant_sku),
                'platform_sku' => e($row->platform_sku),
                'country' => e($row->country_name),
                'currency' => e($row->currency),
                'price' => $row->price,
                'compare_at_price' => $row->compare_at_price,
                'fulfillment_type' => $row->fulfillment_type,
                'payment_options' => $paymentLabels[$row->payment_options] ?? $row->payment_options,
                'featured_in_nawy' => (bool) $row->featured_in_nawy,
                'status' => $row->status?->value,
                'rating_avg' => $row->rating_avg,
                'rating_count' => $row->rating_count,
                'total_sold' => $row->total_sold,
                'warehouse_name' => $row->warehouse_name ? e($row->warehouse_name) : null,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'show_url' => route('admin.admin-product-listings.show', $row->id),
                'edit_url' => route('admin.admin-product-listings.edit', $row->id),
                'delete_url' => route('admin.admin-product-listings.destroy', $row->id),
                'activate_url' => route('admin.admin-product-listings.activate', $row->id),
                'toggle_featured_url' => route('admin.admin-product-listings.toggle-featured', $row->id),
            ];
        });
    }

    /** Bulk status change (active/paused/archived) from the index table's selection bar. */
    public function bulkAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string', 'exists:admin_product_listings,id'],
        ]);

        $count = AdminProductListing::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['action']]);

        return response()->json([
            'success' => true,
            'message' => __('admin.admin_product_listings.bulk_action_updated', ['count' => $count]),
        ]);
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
            'countries'       => Country::where('is_active', true)->orderBy('name_en')->get(),
            'warehouses'      => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('name')->get(),
            'nawyCategories'  => $this->nawyCategories(),
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

        $listing = AdminProductListing::create($data);

        return redirect()
            ->route('admin.admin-product-listings.show', $listing)
            ->with('success', 'Listing created successfully.');
    }

    public function edit(AdminProductListing $adminProductListing): View
    {
        return view('admin.admin-product-listings.edit', [
            'listing'         => $adminProductListing,
            'countries'       => Country::where('is_active', true)->orderBy('name_en')->get(),
            'warehouses'      => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('name')->get(),
            'nawyCategories'  => $this->nawyCategories(),
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
            ->route('admin.admin-product-listings.show', $adminProductListing)
            ->with('success', 'Listing updated successfully.');
    }

    /** Categories eligible for the Nawy category picker: featured or explicitly sorted. */
    private function nawyCategories()
    {
        return Category::query()
            ->where(function ($q) {
                $q->where('nawy_is_featured', true)->orWhere('nawy_sort_order', '>', 0);
            })
            ->orderBy('name_en')
            ->get();
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

    public function activate(AdminProductListing $adminProductListing): JsonResponse|RedirectResponse
    {
        $adminProductListing->status = AdminProductListingStatus::Active;
        $adminProductListing->save();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Listing activated.']);
        }

        return redirect()
            ->route('admin.admin-product-listings.index')
            ->with('success', 'Listing activated.');
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
        $adminProductListing->load([
            'productVariant.product.brand',
            'productVariant.product.category',
            'country',
            'nawyCategory',
            'warehouse',
            'primaryShippingMethod',
            'createdByAdmin',
            'warehouseInventories.warehouse',
            'reviews.customer',
            'marketerCampaignProducts.campaign',
            'flashSaleSubmissions.flashSale',
            'marketplaceShippingRule',
            'productCostReferences',
        ]);

        $productId = $adminProductListing->productVariant->product_id;
        $canViewCost = auth('admin')->user()?->hasPermissionTo('products.cost_data.view') ?? false;
        $costReference = $canViewCost ? ProductCostReference::where('product_id', $productId)->first() : null;

        return view('admin.admin-product-listings.show', [
            'listing' => $adminProductListing,
            'costReference' => $costReference,
            'canViewCost' => $canViewCost,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'statuses' => collect(AdminProductListingStatus::cases())
                ->mapWithKeys(fn($status) => [$status->value => Str::headline($status->value)]),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Admin Listings', 'url' => route('admin.admin-product-listings.index')],
                ['label' => e($adminProductListing->productVariant->product->name_en)],
            ],
        ]);
    }

    /** Quick AJAX status change from the show page sidebar. */
    public function updateStatus(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AdminProductListingStatus::class)],
        ]);

        $adminProductListing->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'status' => $adminProductListing->status->value,
        ]);
    }

    /**
     * Adjust warehouse stock for this listing. Finds or creates the
     * warehouse_inventories row scoped to this admin listing + warehouse and
     * applies a delta to quantity_on_hand. quantity_available is a MySQL
     * GENERATED VIRTUAL column and is never written directly.
     */
    public function adjustStock(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $inventory = WarehouseInventory::firstOrNew([
            'admin_product_listing_id' => $adminProductListing->id,
            'warehouse_id' => $data['warehouse_id'],
        ]);

        $inventory->quantity_on_hand = max(0, ($inventory->quantity_on_hand ?? 0) + $data['adjustment']);
        $inventory->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted.',
            'inventory' => $inventory->fresh('warehouse'),
        ]);
    }

    /** Create or update the marketplace_shipping_rules row for this listing. */
    public function saveShippingRule(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        $data = $request->validate([
            'requires_special_vehicle' => ['boolean'],
            'requires_refrigeration' => ['boolean'],
            'max_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'max_dimensions_cm' => ['nullable', 'string', 'max:100'],
            'special_handling_notes' => ['nullable', 'string', 'max:1000'],
            'commission_type' => ['required', Rule::enum(MarketplaceShippingRuleCommissionType::class)],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'extra_delivery_fee' => ['nullable', 'integer', 'min:0'],
        ]);

        $rule = MarketplaceShippingRule::updateOrCreate(
            ['admin_product_listing_id' => $adminProductListing->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Shipping rule saved.',
            'rule' => $rule,
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
    // Cost References tab (admin-only, confidential — products.cost_data.*)
    // ──────────────────────────────────────────────────────────────────────────

    /** DataTable feed of cost references linked directly to this admin listing. */
    public function costReferences(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        abort_unless(
            auth('admin')->user()?->hasPermissionTo('products.cost_data.view'),
            403
        );

        $query = ProductCostReference::query()
            ->where('admin_product_listing_id', $adminProductListing->id);

        $columns = [
            ['searchable_columns' => ['manufacturer_name'], 'orderable_column' => 'manufacturer_name'],
            ['orderable_column' => 'manufacturer_cost'],
            ['orderable_column' => 'shipping_cost'],
            ['orderable_column' => 'landed_cost'],
            ['orderable_column' => 'platform_margin_pct'],
            ['orderable_column' => 'competitor_last_checked'],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (ProductCostReference $ref) {
            return array_merge($this->serializeRef($ref), [
                'competitor_count' => count($ref->competitorLinksNormalized()),
                'last_checked' => $ref->competitor_last_checked?->toISOString(),
            ]);
        });
    }

    /** Create a cost reference scoped to this admin listing. */
    public function storeCostReference(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        abort_unless(
            auth('admin')->user()?->hasPermissionTo('products.cost_data.view'),
            403
        );

        $data = $this->validateCostReference($request);

        $ref = ProductCostReference::create(array_merge($data, [
            'product_id' => $adminProductListing->productVariant->product_id,
            'admin_product_listing_id' => $adminProductListing->id,
            'vendor_listing_id' => null,
            'is_confidential' => 1,
            'created_by_admin_id' => Auth::guard('admin')->id(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cost reference created.',
            'ref' => $this->serializeRef($ref->fresh()),
        ]);
    }

    /** Update a cost reference belonging to this admin listing. */
    public function updateCostReference(Request $request, AdminProductListing $adminProductListing, ProductCostReference $costReference): JsonResponse
    {
        abort_unless(
            auth('admin')->user()?->hasPermissionTo('products.cost_data.view'),
            403
        );
        abort_unless($costReference->admin_product_listing_id === $adminProductListing->id, 404);

        $data = $this->validateCostReference($request);

        $costReference->fill(array_merge($data, [
            'updated_by_admin_id' => Auth::guard('admin')->id(),
        ]));
        $costReference->save();

        return response()->json([
            'success' => true,
            'message' => 'Cost reference updated.',
            'ref' => $this->serializeRef($costReference->fresh()),
        ]);
    }

    /** Delete a cost reference belonging to this admin listing. */
    public function destroyCostReference(AdminProductListing $adminProductListing, ProductCostReference $costReference): JsonResponse
    {
        abort_unless(
            auth('admin')->user()?->hasPermissionTo('products.cost_data.view'),
            403
        );
        abort_unless($costReference->admin_product_listing_id === $adminProductListing->id, 404);

        $costReference->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cost reference deleted.',
        ]);
    }

    private function validateCostReference(Request $request): array
    {
        return $request->validate([
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'manufacturer_url' => ['nullable', 'url', 'max:500'],
            'manufacturer_sku' => ['nullable', 'string', 'max:100'],
            'manufacturer_cost' => ['nullable', 'integer', 'min:0'],
            'shipping_cost' => ['nullable', 'integer', 'min:0'],
            'landed_cost' => ['nullable', 'integer', 'min:0'],
            'platform_margin_pct' => ['nullable', 'numeric', 'min:-999', 'max:999'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'competitor_links' => ['nullable', 'array'],
            'competitor_links.*.name' => ['required_with:competitor_links', 'string', 'max:255'],
            'competitor_links.*.url' => ['required_with:competitor_links', 'url', 'max:500'],
            'competitor_links.*.price' => ['nullable', 'integer', 'min:0'],
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
            'warehouse_id'         => ['nullable', 'exists:warehouses,id'],
            'price'                => ['required', 'integer', 'min:0'],
            'compare_at_price'     => ['nullable', 'integer', 'min:0', 'gt:price'],
            'cost_price'           => ['nullable', 'integer', 'min:0'],
            'commission_type'      => ['required', Rule::enum(AdminProductListingCommissionType::class)],
            'commission_value'     => ['required', 'numeric', 'min:0'],
            'payment_options'      => ['required', Rule::in(['cod_only', 'electronic_only', 'both'])],
            'fulfillment_type'     => ['required', Rule::in(['express', 'global', 'mixed'])],
            'primary_shipping_method_id' => ['nullable', 'exists:shipping_methods,id'],
            'is_global_shipping'   => ['boolean'],
            'platform_sku'         => [
                'nullable', 'string', 'max:100',
                Rule::unique('admin_product_listings', 'platform_sku')->ignore($listing?->id),
            ],
            'shipping_cost'        => ['required', 'integer', 'min:0'],
            'is_exclusive'         => ['boolean'],
            'featured_in_nawy'     => ['boolean'],
            'nawy_category_id'     => ['nullable', 'exists:categories,id'],
            'available_for_vendors'   => ['boolean'],
            'available_for_marketers' => ['boolean'],
            'status'               => ['required', Rule::enum(AdminProductListingStatus::class)],
            'max_order_quantity'   => ['nullable', 'integer', 'min:1'],
            'low_stock_threshold'  => ['integer', 'min:0'],
            'condition'            => ['required', Rule::in(['new', 'like_new', 'good', 'acceptable', 'refurbished'])],
            'condition_notes'      => ['nullable', 'string'],
            'weight_class'         => ['nullable', Rule::in(['light', 'medium', 'heavy'])],
            'handling_class'       => ['required', Rule::in(['standard', 'refrigerated', 'fragile', 'special_tech'])],
            'declared_weight_grams' => ['nullable', 'integer', 'min:0'],
            'declared_length_cm'   => ['nullable', 'numeric', 'min:0'],
            'declared_width_cm'    => ['nullable', 'numeric', 'min:0'],
            'declared_height_cm'   => ['nullable', 'numeric', 'min:0'],
            'influencer_commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'affiliate_commission_percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'influencer_sample_quota' => ['nullable', 'integer', 'min:0'],
            'affiliate_sample_quota'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
