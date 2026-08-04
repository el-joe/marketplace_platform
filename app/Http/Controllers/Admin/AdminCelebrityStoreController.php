<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AdminProductListing;
use App\Models\CelebrityStoreProduct;
use App\Models\Marketer;
use App\Models\VendorListing;
use App\Enums\MarketerType;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCelebrityStoreController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        return view('admin.celebrity-store.index', [
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.celebrity_store.title')],
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            [],
            [],
            ['orderable_column' => 'promoter_commission_amount'],
            ['orderable_column' => 'currency_code'],
            ['orderable_column' => 'admin_commission_pct_bps'],
            ['orderable_column' => 'is_active'],
            [],
        ];

        $query = CelebrityStoreProduct::query()
            ->with([
                'celebrity',
                'vendorListing.productVariant.product',
                'adminListing.productVariant.product',
            ]);

        $query = $this->applyFilters($query, $request, [
            'celebrity_marketer_id' => fn($q, $v) => $q->where('celebrity_marketer_id', $v),
            'is_active' => fn($q, $v) => $q->where('is_active', (bool) $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function (CelebrityStoreProduct $row) {
            $listing = $row->vendorListing ?? $row->adminListing;
            $product = $listing?->productVariant?->product;

            return [
                'id' => $row->id,
                'celebrity_name' => $row->celebrity?->display_name ?? $row->celebrity?->name ?? '—',
                'product_name' => $product ? e($product->name_en) : '—',
                'promoter_commission_amount' => number_format($row->promoter_commission_amount),
                'currency_code' => $row->currency_code,
                'admin_commission_pct_bps' => number_format($row->admin_commission_pct_bps / 100, 2) . '%',
                'is_active' => (bool) $row->is_active,
                'approved' => (bool) $row->approved_by_admin_id,
                'toggle_url' => route('admin.celebrity-store.products.approve', $row->id),
                'delete_url' => route('admin.celebrity-store.products.remove', $row->id),
                'update_commission_url' => route('admin.celebrity-store.products.commission', $row->id),
                'current_commission_amount' => $row->promoter_commission_amount,
                'current_commission_bps' => $row->admin_commission_pct_bps,
            ];
        });
    }

    public function approveProduct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'celebrity_marketer_id' => ['required', 'string', 'exists:marketers,id'],
            'listing_type' => ['required', Rule::in(['vendor_listing', 'admin_product_listing'])],
            'listing_id' => ['required', 'string'],
            'promoter_commission_amount' => ['nullable', 'integer', 'min:0'],
            'admin_commission_pct_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'currency_code' => ['nullable', 'string', 'size:3'],
        ]);

        $isVendorListing = $data['listing_type'] === 'vendor_listing';

        if ($isVendorListing) {
            $exists = VendorListing::whereKey($data['listing_id'])->exists();
        } else {
            $exists = AdminProductListing::whereKey($data['listing_id'])->exists();
        }

        abort_unless($exists, 404, __('admin.celebrity_store.listing_not_found'));

        $admin = Auth::guard('admin')->user();

        $record = CelebrityStoreProduct::create([
            'celebrity_marketer_id' => $data['celebrity_marketer_id'],
            'vendor_listing_id' => $isVendorListing ? $data['listing_id'] : null,
            'admin_product_listing_id' => $isVendorListing ? null : $data['listing_id'],
            'promoter_commission_amount' => $data['promoter_commission_amount'] ?? 0,
            'admin_commission_pct_bps' => $data['admin_commission_pct_bps'] ?? 0,
            'currency_code' => $data['currency_code'] ?? 'SAR',
            'is_active' => true,
            'approved_by_admin_id' => $admin?->id,
        ]);

        Activity::create([
            'log_name' => 'celebrity_store_products',
            'description' => 'Approved celebrity store product',
            'subject_type' => CelebrityStoreProduct::class,
            'subject_id' => $record->id,
            'causer_type' => $admin ? get_class($admin) : null,
            'causer_id' => $admin?->id,
            'event' => 'approved',
            'properties' => json_encode($record->only([
                'celebrity_marketer_id', 'vendor_listing_id', 'admin_product_listing_id',
                'promoter_commission_amount', 'currency_code', 'admin_commission_pct_bps',
            ])),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('admin.celebrity_store.product_approved'),
            'id' => $record->id,
        ]);
    }

    public function setCommission(Request $request, CelebrityStoreProduct $product): JsonResponse
    {
        $data = $request->validate([
            'promoter_commission_amount' => ['required', 'integer', 'min:0'],
            'admin_commission_pct_bps' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $product->update($data);

        $admin = Auth::guard('admin')->user();

        Activity::create([
            'log_name' => 'celebrity_store_products',
            'description' => 'Updated celebrity store product commission',
            'subject_type' => CelebrityStoreProduct::class,
            'subject_id' => $product->id,
            'causer_type' => $admin ? get_class($admin) : null,
            'causer_id' => $admin?->id,
            'event' => 'commission_updated',
            'properties' => json_encode($data),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('admin.celebrity_store.commission_updated'),
        ]);
    }

    public function removeProduct(Request $request, CelebrityStoreProduct $product): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        Activity::create([
            'log_name' => 'celebrity_store_products',
            'description' => 'Removed celebrity store product',
            'subject_type' => CelebrityStoreProduct::class,
            'subject_id' => $product->id,
            'causer_type' => $admin ? get_class($admin) : null,
            'causer_id' => $admin?->id,
            'event' => 'removed',
            'properties' => json_encode($product->only([
                'celebrity_marketer_id', 'vendor_listing_id', 'admin_product_listing_id',
            ])),
            'ip_address' => $request->ip(),
        ]);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => __('admin.celebrity_store.removed_from_market'),
        ]);
    }

    /** Select2 AJAX search across celebrity marketers. */
    public function searchCelebrities(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        $celebrities = Marketer::query()
            ->where('type', MarketerType::Celebrity)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'display_name']);

        return response()->json([
            'results' => $celebrities->map(fn($c) => [
                'id' => $c->id,
                'text' => $c->display_name ?? $c->name,
            ]),
        ]);
    }

    /** Select2 AJAX search across vendor_listings + admin_product_listings. */
    public function searchListings(Request $request): JsonResponse
    {
        $term = $request->input('q', '');
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
                'text' => "{$al->name_en} [{$al->sku}] — Admin Listing ({$al->platform_sku})",
            ];
        }

        return response()->json(['results' => $results]);
    }
}
