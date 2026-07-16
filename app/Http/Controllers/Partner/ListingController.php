<?php

namespace App\Http\Controllers\Partner;

use App\Enums\InventoryMovementReferenceType;
use App\Enums\InventoryMovementType;
use App\Enums\ProductStatus;
use App\Enums\VendorListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\VendorListing;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\ListingShippingResolver;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ListingController extends Controller
{
    use HasDataTable;

    public function __construct(private readonly ListingShippingResolver $shippingResolver)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    private function vendor(): \App\Models\Vendor
    {
        return Auth::guard('vendor')->user()->vendor;
    }

    private function authoriseListing(VendorListing $listing): void
    {
        if ($listing->vendor_id !== $this->vendorId()) {
            abort(403);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $vendorId = $this->vendorId();

        $counts = VendorListing::where('vendor_id', $vendorId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $lowStockCount = VendorListing::where('vendor_id', $vendorId)
            ->whereExists(function ($q) {
                $q->from('warehouse_inventories')
                    ->whereColumn('vendor_listing_id', 'vendor_listings.id')
                    ->whereRaw('quantity_on_hand - quantity_reserved <= low_stock_threshold')
                    ->whereRaw('quantity_on_hand > 0');
            })
            ->count();

        return view('partner.listings.index', compact('counts', 'lowStockCount'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTable
    // ─────────────────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $query = VendorListing::where('vendor_listings.vendor_id', $vendorId)
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'vendor_listings.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->select([
                'vendor_listings.id',
                'vendor_listings.status',
                'vendor_listings.price',
                'vendor_listings.total_sold',
                'vendor_listings.rating_avg',
                'vendor_listings.low_stock_threshold',
                'pv.id as variant_id',
                'pv.variant_name',
                'pv.sku',
                'p.id as product_id',
                'p.name_en',
                'p.name_ar',
            ])
            ->addSelect([
                'primary_image' => ProductImage::select('path')
                    ->whereColumn('product_id', 'p.id')
                    ->where('is_primary', true)
                    ->orderBy('position')
                    ->limit(1),
                'primary_image_disk' => ProductImage::select('disk')
                    ->whereColumn('product_id', 'p.id')
                    ->where('is_primary', true)
                    ->orderBy('position')
                    ->limit(1),
                'available_stock' => WarehouseInventory::selectRaw('COALESCE(SUM(quantity_on_hand - quantity_reserved), 0)')
                    ->whereColumn('vendor_listing_id', 'vendor_listings.id'),
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $v === 'low_stock'
                ? $q->whereRaw('vendor_listings.id IN (SELECT vendor_listing_id FROM warehouse_inventories WHERE quantity_on_hand - quantity_reserved <= vendor_listings.low_stock_threshold AND quantity_on_hand > 0)')
                : $q->where('vendor_listings.status', $v),
            'search' => fn($q, $v) => $q->where(function ($sq) use ($v) {
                $v = $v['value'] ?? '';
                $sq->where('p.name_en', 'like', '%' . $v . '%')
                    ->orWhere('p.name_ar', 'like', '%' . $v . '%')
                    ->orWhere('pv.sku', 'like', '%' . $v . '%')
                    ->orWhere('vendor_listings.vendor_sku', 'like', '%' . $v . '%');
            }),
        ]);

        $columns = [
            ['searchable_columns' => ['p.name_en', 'p.name_ar', 'pv.sku']],
            ['orderable_column' => 'pv.variant_name'],
            ['orderable_column' => 'vendor_listings.status'],
            ['orderable_column' => 'vendor_listings.price'],
            [],
            ['orderable_column' => 'vendor_listings.total_sold'],
            ['orderable_column' => 'vendor_listings.rating_avg'],
            [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            $imageUrl = null;
            if ($row->primary_image) {
                $imageUrl = Storage::disk($row->primary_image_disk ?? 'public')->url($row->primary_image);
            }

            $available = (int) $row->available_stock;
            $threshold = (int) ($row->low_stock_threshold ?? 5);
            $stockClass = $available === 0
                ? 'text-red-600 font-bold'
                : ($available <= $threshold ? 'text-orange-500 font-semibold' : 'text-gray-800');
            $stockHtml = "<span class=\"{$stockClass} text-xs\">{$available}</span>";

            return [
                'id' => $row->id,
                'name_en' => $row->name_en,
                'name_ar' => $row->name_ar,
                'image_url' => $imageUrl,
                'variant_name' => $row->variant_name ?: '—',
                'sku' => $row->sku,
                'status' => $row->status instanceof VendorListingStatus ? $row->status->value : $row->status,
                'price' => number_format($row->price / 100, 2),
                'price_raw' => $row->price,
                'available_stock' => $stockHtml,
                'available_raw' => $available,
                'total_sold' => $row->total_sold ?? 0,
                'rating_avg' => $row->rating_avg ? number_format($row->rating_avg, 1) : '—',
                'show_url' => Route::has('partner.listings.show')
                    ? route('partner.listings.show', $row->id) : '#',
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Product Search (AJAX — for create form)
    // ─────────────────────────────────────────────────────────────────────────

    public function productSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->where(function ($query) use ($q) {
                $query->where('name_en', 'like', '%' . $q . '%')
                    ->orWhere('name_ar', 'like', '%' . $q . '%')
                    ->orWhere('model_number', 'like', '%' . $q . '%')
                    ->orWhere('gtin', 'like', $q . '%');
            })
            ->with([
                'variants' => function ($vq) {
                    $vq->where('is_active', true)->orderBy('position');
                }
            ])
            ->addSelect([
                'primary_image' => ProductImage::select('path')
                    ->whereColumn('product_id', 'products.id')
                    ->where('is_primary', true)
                    ->orderBy('position')
                    ->limit(1),
                'primary_image_disk' => ProductImage::select('disk')
                    ->whereColumn('product_id', 'products.id')
                    ->where('is_primary', true)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->limit(10)
            ->get(['id', 'name_en', 'name_ar', 'model_number', 'has_variants']);

        $vendorId = $this->vendorId();

        return response()->json($products->map(function ($product) use ($vendorId) {
            $imageUrl = null;
            if ($product->primary_image) {
                $imageUrl = Storage::disk($product->primary_image_disk ?? 'public')->url($product->primary_image);
            }

            // Get which variant IDs the vendor already has listed for this product
            $existingVariantIds = VendorListing::where('vendor_id', $vendorId)
                ->whereIn('product_variant_id', $product->variants->pluck('id'))
                ->whereNotIn('status', [VendorListingStatus::Archived->value])
                ->pluck('product_variant_id')
                ->all();

            return [
                'id' => $product->id,
                'name' => $product->name_ar ?: $product->name_en,
                'name_en' => $product->name_en,
                'name_ar' => $product->name_ar,
                'model' => $product->model_number,
                'image_url' => $imageUrl,
                'has_variants' => (bool) $product->has_variants,
                'variants' => $product->variants->map(fn($v) => [
                    'id' => $v->id,
                    'variant_name' => $v->variant_name ?: 'النسخة الافتراضية',
                    'sku' => $v->sku,
                    'already_listed' => in_array($v->id, $existingVariantIds),
                ]),
            ];
        }));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Warehouses by Country (AJAX — for create form)
    // ─────────────────────────────────────────────────────────────────────────

    public function warehousesByCountry(Request $request): JsonResponse
    {
        $request->validate(['country_id' => ['required', 'exists:countries,id']]);

        $vendor = $this->vendor();

        $warehouses = Warehouse::where('is_active', true)
            ->where('country_id', $request->country_id)
            ->where(function ($q) use ($vendor) {
                $q->whereNull('owner_vendor_id')
                    ->orWhere('owner_vendor_id', $vendor->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return response()->json($warehouses->map(fn($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'code' => $w->code,
            'type' => $w->type->value,
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────────────────

    public function show(VendorListing $listing): View
    {
        $this->authoriseListing($listing);

        $listing->load([
            'productVariant.product.category',
            'productVariant.product.images',
            'warehouseInventories.warehouse',
            'country',
            'primaryShippingMethod',
        ]);

        // Last 20 inventory movements for all warehouse inventories of this listing
        $inventoryIds = $listing->warehouseInventories->pluck('id');
        $movements = InventoryMovement::whereIn('warehouse_inventory_id', $inventoryIds)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $availableShippingMethods = $this->shippingResolver->resolveForListing($listing);

        return view('partner.listings.show', compact('listing', 'movements', 'availableShippingMethods'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $vendor = $this->vendor();
        $countryId = $vendor->country_id;

        // Warehouses in vendor's country (active, either platform or vendor-owned)
        $warehouses = Warehouse::where('is_active', true)
            ->where(function ($q) use ($vendor, $countryId) {
                $q->where('country_id', $countryId)
                    ->where(function ($sq) use ($vendor) {
                        $sq->whereNull('owner_vendor_id')
                            ->orWhere('owner_vendor_id', $vendor->id);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $countries = Country::where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar', 'currency_code']);

        $fulfillmentModels = [
            'fbm' => 'FBM — الشحن الذاتي',
            'fbn' => 'FBN — التخزين في مستودعاتنا',
            'cross_dock' => 'Cross Dock — شحن جزئي',
        ];

        $conditions = [
            'new' => 'جديد',
            'like_new' => 'كالجديد',
            'good' => 'جيد',
            'acceptable' => 'مقبول',
            'refurbished' => 'مُجدَّد',
        ];

        return view('partner.listings.create', compact(
            'warehouses',
            'countries',
            'fulfillmentModels',
            'conditions',
            'countryId'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_variant_id' => ['nullable', 'required_without:product_id', 'uuid', 'exists:product_variants,id'],
            'product_id' => ['nullable', 'required_without:product_variant_id', 'uuid', 'exists:products,id'],
            'country_id' => ['required', 'exists:countries,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'condition' => ['required', 'in:new,like_new,good,acceptable,refurbished'],
            'fulfillment_model' => ['required', 'in:fbm,fbn,cross_dock'],
            'vendor_sku' => ['nullable', 'string', 'max:100'],
            'vendor_notes' => ['nullable', 'string', 'max:1000'],
            'max_order_quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'initial_quantity' => ['required', 'integer', 'min:0', 'max:99999'],
            'vendor_covers_delivery' => ['nullable', 'boolean'],
        ]);

        // Resolve product_variant_id — either sent directly (has_variants=1) or resolved
        // from the product's sole default variant (has_variants=0).
        if ($request->filled('product_variant_id')) {
            $resolvedVariantId = $request->product_variant_id;
        } else {
            $product = Product::findOrFail($request->product_id);
            if ($product->has_variants) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى اختيار نسخة محددة من هذا المنتج.',
                ], 422);
            }
            $defaultVariant = $product->variants()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
            if (!$defaultVariant) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا المنتج غير متاح للبيع حالياً — يرجى التواصل مع الإدارة.',
                ], 422);
            }
            $resolvedVariantId = $defaultVariant->id;
        }

        $vendorId = $this->vendorId();

        // Prevent duplicate active listing for same vendor + variant + country
        $existing = VendorListing::where('vendor_id', $vendorId)
            ->where('product_variant_id', $resolvedVariantId)
            ->where('country_id', $request->country_id)
            ->whereNotIn('status', ['archived'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'لديك بالفعل قائمة نشطة لهذا المنتج في هذا البلد.',
            ], 422);
        }

        $vendor = $this->vendor();

        // Established vendor (> 10 orders) gets active status directly
        $status = ($vendor->total_orders >= 10) ? VendorListingStatus::Active->value : VendorListingStatus::PendingReview->value;

        $currency = Country::find($request->country_id)?->currency_code ?? '';

        // try {
        $listing = null;
        DB::transaction(function () use ($request, $vendorId, $status, $currency, &$listing, $vendor, $resolvedVariantId) {
            $listing = VendorListing::create([
                'id' => (string) Str::uuid(),
                'vendor_id' => $vendorId,
                'product_variant_id' => $resolvedVariantId,
                'country_id' => $request->country_id,
                'price' => (int) round((float) $request->price * 100),
                'currency' => $currency,
                'condition' => $request->condition,
                'fulfillment_model' => $request->fulfillment_model,
                'vendor_sku' => $request->vendor_sku,
                'vendor_notes' => $request->vendor_notes,
                'status' => $status,
                'max_order_quantity' => $request->max_order_quantity,
                'low_stock_threshold' => $request->low_stock_threshold ?? 5,
                'vendor_covers_delivery' => $request->boolean('vendor_covers_delivery'),
            ]);

            // Create warehouse inventory record
            $inventory = WarehouseInventory::create([
                'id' => (string) Str::uuid(),
                'vendor_listing_id' => $listing->id,
                'warehouse_id' => $request->warehouse_id,
                'quantity_on_hand' => $request->initial_quantity,
                'quantity_reserved' => 0,
                'quantity_inbound' => 0,
                'quantity_damaged' => 0,
            ]);

            // Initial stock movement
            if ((int) $request->initial_quantity > 0) {
                InventoryMovement::create([
                    'warehouse_inventory_id' => $inventory->id,
                    'movement_type' => InventoryMovementType::Inbound->value,
                    'quantity_delta' => (int) $request->initial_quantity,
                    'quantity_after' => (int) $request->initial_quantity,
                    'reference_type' => InventoryMovementReferenceType::InboundShipment->value,
                    'reference_id' => $listing->id,
                    'reason' => 'initial_stock',
                    'created_by_user_id' => Auth::guard('vendor')->user()->id,
                ]);
            }
        });
        // } catch (\Throwable $e) {
        //     Log::error('ListingController::store failed', ['error' => $e->getMessage()]);
        //     return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إنشاء القائمة. يرجى المحاولة مرة أخرى.'], 500);
        // }

        return response()->json([
            'success' => true,
            'message' => $status === VendorListingStatus::Active->value ? 'تم إنشاء القائمة بنجاح وهي نشطة الآن.' : 'تم إنشاء القائمة وهي قيد المراجعة.',
            'redirect' => Route::has('partner.listings.show') ? route('partner.listings.show', $listing->id) : route('partner.listings.index'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update Price
    // ─────────────────────────────────────────────────────────────────────────

    public function updatePrice(Request $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        $request->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999'],
        ]);

        $listing->update([
            'price' => (int) round((float) $request->price * 100),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السعر بنجاح.',
            'price_formatted' => number_format($listing->price / 100, 2),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update Shipping Method
    // ─────────────────────────────────────────────────────────────────────────

    public function updateShipping(Request $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        $request->validate([
            'primary_shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],
        ]);

        $availableMethods = $this->shippingResolver->resolveForListing($listing);

        if (!$availableMethods->contains('id', $request->primary_shipping_method_id)) {
            return response()->json([
                'success' => false,
                'message' => 'طريقة الشحن هذه غير متاحة لهذه القائمة.',
            ], 422);
        }

        $listing->update(['primary_shipping_method_id' => $request->primary_shipping_method_id]);

        $method = $availableMethods->firstWhere('id', $request->primary_shipping_method_id);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طريقة الشحن بنجاح.',
            'shipping_method_name' => $method->name,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Toggle Status (active ↔ paused)
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        if (!in_array($listing->status, [VendorListingStatus::Active, VendorListingStatus::Paused], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير حالة هذه القائمة.',
            ], 422);
        }

        if ($listing->status === VendorListingStatus::Paused) {
            // Check if any stock available before activating
            $available = WarehouseInventory::where('vendor_listing_id', $listing->id)
                ->selectRaw('COALESCE(SUM(quantity_on_hand - quantity_reserved), 0) as total')
                ->value('total');

            if ((int) $available <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تفعيل القائمة لأن المخزون المتاح صفر.',
                ], 422);
            }
        }

        $newStatus = $listing->status === VendorListingStatus::Active ? VendorListingStatus::Paused : VendorListingStatus::Active;
        $listing->update(['status' => $newStatus]);

        $statusLabels = [VendorListingStatus::Active->value => 'نشط', VendorListingStatus::Paused->value => 'موقوف مؤقتاً'];

        return response()->json([
            'success' => true,
            'new_status' => $newStatus->value,
            'label' => $statusLabels[$newStatus->value] ?? $newStatus->value,
            'message' => $newStatus === VendorListingStatus::Active ? 'تم تفعيل القائمة.' : 'تم إيقاف القائمة مؤقتاً.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Toggle Vendor Covers Delivery (FBP only)
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleCoversDelivery(Request $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        if ($listing->global_system_type !== \App\Enums\GlobalSystemType::MerchantFbp) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الخيار متاح فقط لقوائم FBP.',
            ], 422);
        }

        $request->validate([
            'vendor_covers_delivery' => ['required', 'boolean'],
        ]);

        $listing->update(['vendor_covers_delivery' => $request->boolean('vendor_covers_delivery')]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعداد تغطية الشحن.',
            'vendor_covers_delivery' => $listing->vendor_covers_delivery,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Adjust Stock
    // ─────────────────────────────────────────────────────────────────────────

    public function adjustStock(Request $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        $request->validate([
            'warehouse_inventory_id' => ['required', 'exists:warehouse_inventories,id'],
            'adjustment' => ['required', 'integer', 'between:-99999,99999', 'not_in:0'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $listing) {
                $inventory = WarehouseInventory::lockForUpdate()
                    ->where('id', $request->warehouse_inventory_id)
                    ->where('vendor_listing_id', $listing->id)
                    ->firstOrFail();

                $newOnHand = $inventory->quantity_on_hand + (int) $request->adjustment;

                if ($newOnHand < 0) {
                    throw new \InvalidArgumentException('لا يمكن أن يكون المخزون بالسالب.');
                }

                $inventory->update(['quantity_on_hand' => $newOnHand]);

                InventoryMovement::create([
                    'warehouse_inventory_id' => $inventory->id,
                    'movement_type' => 'adjustment',
                    'quantity_delta' => (int) $request->adjustment,
                    'quantity_after' => $newOnHand,
                    'reference_type' => 'adjustment',
                    'reference_id' => $listing->id,
                    'reason' => $request->reason,
                    'created_by_user_id' => Auth::guard('vendor')->user()->id,
                ]);

                return $newOnHand;
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('adjustStock failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء التعديل.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل المخزون بنجاح.',
            'new_quantity' => $result,
        ]);
    }
}
