<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminInfluencerOpenMarketProduct;
use App\Models\AdminProductListing;
use App\Models\VendorListing;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminInfluencerOpenMarketProductController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        return view('admin.open-market.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Influencer Open Market'],
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            [],
            [],
            ['orderable_column' => 'promotion_tier'],
            ['orderable_column' => 'celebrity_commission_amount'],
            ['orderable_column' => 'is_active'],
            ['orderable_column' => 'created_at'],
            [],
        ];

        $query = AdminInfluencerOpenMarketProduct::query()
            ->with([
                'vendorListing.productVariant.product',
                'adminProductListing.productVariant.product',
                'addedByAdmin',
            ]);

        $query = $this->applyFilters($query, $request, [
            'promotion_tier' => fn($q, $v) => $q->where('promotion_tier', $v),
            'is_active' => fn($q, $v) => $q->where('is_active', (bool) $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function (AdminInfluencerOpenMarketProduct $row) {
            $listing = $row->vendorListing ?? $row->adminProductListing;
            $product = $listing?->productVariant?->product;

            return [
                'id' => $row->id,
                'product_name' => $product ? e($product->name_en) : '—',
                'listing_id' => $listing?->id ?? '—',
                'promotion_tier' => $row->promotion_tier === AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW
                    ? 'Tier 4 (Nawy Now)'
                    : 'Tier 3 (Admin Curated)',
                'celebrity_commission_amount' => number_format($row->celebrity_commission_amount) . ' ' . $row->commission_currency,
                'is_active' => (bool) $row->is_active,
                'added_by' => $row->addedByAdmin?->name,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'toggle_url' => route('admin.open-market.toggle', $row->id),
                'delete_url' => route('admin.open-market.destroy', $row->id),
                'update_commission_url' => route('admin.open-market.update-commission', $row->id),
                'current_commission_amount' => $row->celebrity_commission_amount,
                'current_commission_currency' => $row->commission_currency,
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'listing_type' => ['required', Rule::in(['vendor_listing', 'admin_product_listing'])],
            'listing_id' => ['required', 'string'],
            'open_market_category' => ['required', 'integer', Rule::in([
                AdminInfluencerOpenMarketProduct::CATEGORY_ADMIN_INTERMEDIARY,
                AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW,
            ])],
            'celebrity_commission_amount' => ['nullable', 'integer', 'min:0'],
            'commission_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $isVendorListing = $data['listing_type'] === 'vendor_listing';

        if ($isVendorListing) {
            $exists = VendorListing::whereKey($data['listing_id'])->exists();
        } else {
            $listing = AdminProductListing::find($data['listing_id']);
            $exists = (bool) $listing;

            if ($listing && $data['open_market_category'] === AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW
                && !$listing->featured_in_nawy) {
                throw ValidationException::withMessages([
                    'listing_id' => 'Category 4 (Nawy Now) requires an admin product listing featured in Nawy.',
                ]);
            }
        }

        abort_unless($exists, 404, 'Listing not found.');

        $record = AdminInfluencerOpenMarketProduct::create([
            'vendor_listing_id' => $isVendorListing ? $data['listing_id'] : null,
            'admin_product_listing_id' => $isVendorListing ? null : $data['listing_id'],
            'open_market_category' => $data['open_market_category'],
            'promotion_tier' => $data['open_market_category'],
            'celebrity_commission_amount' => $data['celebrity_commission_amount'] ?? 0,
            'commission_currency' => $data['commission_currency'] ?? 'SAR',
            'is_active' => true,
            'added_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to the influencer open market.',
            'id' => $record->id,
        ]);
    }

    public function toggle(AdminInfluencerOpenMarketProduct $openMarketProduct): JsonResponse
    {
        $openMarketProduct->is_active = !$openMarketProduct->is_active;
        $openMarketProduct->save();

        return response()->json([
            'success' => true,
            'is_active' => (bool) $openMarketProduct->is_active,
        ]);
    }

    public function destroy(AdminInfluencerOpenMarketProduct $openMarketProduct): JsonResponse
    {
        $openMarketProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from the influencer open market.',
        ]);
    }

    /**
     * Toggle Category 4 (Nawy Now) inclusion for an admin product listing from
     * its create/edit page checkbox. Creates the record on first check, then
     * flips is_active on subsequent toggles.
     */
    public function toggleForAdminListing(Request $request, AdminProductListing $adminProductListing): JsonResponse
    {
        $data = $request->validate([
            'included' => ['required', 'boolean'],
            'promotion_tier' => ['required', 'integer', Rule::in([
                AdminInfluencerOpenMarketProduct::CATEGORY_ADMIN_INTERMEDIARY,
                AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW,
            ])],
            'celebrity_commission_amount' => ['required_if:included,1', 'nullable', 'integer', 'min:0'],
            'commission_currency' => ['nullable', 'string', 'size:3'],
        ]);

        if ($data['promotion_tier'] === AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW) {
            abort_unless($adminProductListing->featured_in_nawy, 422, 'Listing must be featured in Nawy to be included in Tier 4.');
        }

        $record = AdminInfluencerOpenMarketProduct::firstOrNew([
            'admin_product_listing_id' => $adminProductListing->id,
            'promotion_tier' => $data['promotion_tier'],
        ]);

        $record->open_market_category = $data['promotion_tier'];
        $record->is_active = $data['included'];

        if ($data['included']) {
            $record->celebrity_commission_amount = $data['celebrity_commission_amount'];
            $record->commission_currency = $data['commission_currency'] ?? 'SAR';
        }

        if (!$record->exists) {
            $record->added_by_admin_id = Auth::guard('admin')->id();
        }
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => (bool) $record->is_active,
        ]);
    }

    /** Bulk-assign selected open-market products to a tier from the pool page. */
    public function bulkAssignTier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
            'promotion_tier' => ['required', 'integer', Rule::in([
                AdminInfluencerOpenMarketProduct::CATEGORY_ADMIN_INTERMEDIARY,
                AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW,
            ])],
        ]);

        $records = AdminInfluencerOpenMarketProduct::whereIn('id', $data['ids'])->get();

        if ($data['promotion_tier'] === AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW) {
            $ineligible = $records->first(fn (AdminInfluencerOpenMarketProduct $r) =>
                !$r->admin_product_listing_id || !$r->adminProductListing?->featured_in_nawy);

            if ($ineligible) {
                throw ValidationException::withMessages([
                    'promotion_tier' => 'Tier 4 (Nawy Now) requires admin product listings that are featured in Nawy.',
                ]);
            }
        }

        foreach ($records as $record) {
            $record->promotion_tier = $data['promotion_tier'];
            $record->open_market_category = $data['promotion_tier'];
            $record->save();
        }

        return response()->json([
            'success' => true,
            'message' => count($records) . ' product(s) reassigned.',
        ]);
    }

    /** Update the celebrity commission amount/currency for a single pool row. */
    public function updateCommission(Request $request, AdminInfluencerOpenMarketProduct $openMarketProduct): JsonResponse
    {
        $data = $request->validate([
            'celebrity_commission_amount' => ['required', 'integer', 'min:0'],
            'commission_currency' => ['required', 'string', 'size:3'],
        ]);

        $openMarketProduct->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Commission updated.',
        ]);
    }

    /** Select2 AJAX search across vendor_listings + admin_product_listings. */
    public function searchListings(Request $request): JsonResponse
    {
        $term = $request->input('q', '');
        $category = (int) $request->input('open_market_category', AdminInfluencerOpenMarketProduct::CATEGORY_ADMIN_INTERMEDIARY);

        $results = [];

        $vendorListings = VendorListing::query()
            ->join('product_variants as pv', 'pv.id', '=', 'vendor_listings.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->join('vendors as v', 'v.id', '=', 'vendor_listings.vendor_id')
            ->whereNull('p.deleted_at')
            ->where('vendor_listings.status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('p.name_en', 'like', "%{$term}%")
                    ->orWhere('pv.sku', 'like', "%{$term}%")
                    ->orWhere('v.business_name', 'like', "%{$term}%");
            })
            ->orderBy('p.name_en')
            ->limit(20)
            ->get(['vendor_listings.id', 'p.name_en', 'pv.sku', 'v.business_name']);

        foreach ($vendorListings as $vl) {
            $results[] = [
                'id' => 'vendor_listing:' . $vl->id,
                'text' => "{$vl->name_en} [{$vl->sku}] — Vendor: {$vl->business_name}",
            ];
        }

        $adminListings = AdminProductListing::query()
            ->join('product_variants as pv', 'pv.id', '=', 'admin_product_listings.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->whereNull('p.deleted_at')
            ->when($category === AdminInfluencerOpenMarketProduct::CATEGORY_NAWY_NOW, function ($q) {
                $q->where('admin_product_listings.featured_in_nawy', true);
            })
            ->where(function ($q) use ($term) {
                $q->where('p.name_en', 'like', "%{$term}%")
                    ->orWhere('pv.sku', 'like', "%{$term}%")
                    ->orWhere('admin_product_listings.platform_sku', 'like', "%{$term}%");
            })
            ->orderBy('p.name_en')
            ->limit(20)
            ->get(['admin_product_listings.id', 'p.name_en', 'pv.sku', 'admin_product_listings.platform_sku']);

        foreach ($adminListings as $al) {
            $results[] = [
                'id' => 'admin_product_listing:' . $al->id,
                'text' => "{$al->name_en} [{$al->sku}] — Nawy Now ({$al->platform_sku})",
            ];
        }

        return response()->json(['results' => $results]);
    }
}
