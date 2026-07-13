<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreCouponRequest;
use App\Http\Requests\Vendor\UpdateCouponRequest;
use App\Http\Resources\Vendor\VendorCouponResource;
use App\Models\Coupon;
use App\Policies\VendorCouponPolicy;
use App\Services\Vendor\CouponService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CouponController extends Controller
{
    use HasDataTable;

    public function __construct(
        private readonly CouponService $coupons,
        private readonly VendorCouponPolicy $policy,
    ) {
    }

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index / DataTable
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('vendor.coupons.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $columns = [
            ['title' => 'Code', 'data' => 'code', 'name' => 'code', 'orderable_column' => 'coupons.code', 'searchable_columns' => ['coupons.code', 'coupons.name']],
            ['title' => 'Name', 'data' => 'name', 'name' => 'name', 'searchable' => false],
            ['title' => 'Type', 'data' => 'type', 'name' => 'type', 'orderable_column' => 'coupons.type', 'searchable' => false],
            ['title' => 'Scope', 'data' => 'scope', 'name' => 'scope', 'orderable_column' => 'coupons.scope', 'searchable' => false],
            ['title' => 'Value', 'data' => 'value', 'name' => 'value', 'orderable_column' => 'coupons.value', 'searchable' => false],
            ['title' => 'Used', 'data' => 'times_used', 'name' => 'times_used', 'orderable_column' => 'coupons.times_used', 'searchable' => false],
            ['title' => 'Active', 'data' => 'is_active', 'name' => 'is_active', 'orderable_column' => 'coupons.is_active', 'searchable' => false],
            ['title' => 'Valid Until', 'data' => 'valid_until', 'name' => 'valid_until', 'orderable_column' => 'coupons.valid_until', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ];

        $query = Coupon::query()
            ->where('coupons.vendor_id', $vendorId)
            ->whereIn('coupons.scope', CouponService::VENDOR_MANAGEABLE_SCOPES)
            ->select([
                'coupons.id',
                'coupons.code',
                'coupons.name',
                'coupons.type',
                'coupons.scope',
                'coupons.value',
                'coupons.times_used',
                'coupons.usage_limit_total',
                'coupons.valid_until',
                'coupons.is_active',
            ]);

        $query = $this->applyFilters($query, $request, [
            'is_active' => fn ($q, $v) => $q->where('coupons.is_active', (bool) $v),
            'type' => fn ($q, $v) => $q->where('coupons.type', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'name' => e($row->name),
                'type' => $row->type,
                'scope' => $row->scope,
                'value' => $row->value,
                'times_used' => (int) $row->times_used,
                'usage_limit_total' => $row->usage_limit_total ? (int) $row->usage_limit_total : null,
                'valid_until' => $row->valid_until,
                'is_active' => (bool) $row->is_active,
                'is_expired' => $row->valid_until < now()->toDateTimeString(),
                'show_url' => route('partner.coupons.show', $row->id),
                'edit_url' => route('partner.coupons.edit', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('vendor.coupons.create', [
            'coupon' => null,
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $actor = Auth::guard('vendor')->user();

        abort_unless($this->policy->create($actor), 403);

        try {
            $coupon = $this->coupons->create($actor->vendor, $actor, $request->validated());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('partner.coupons.show', $coupon->id),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show / Edit / Update
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $id): View
    {
        $coupon = Coupon::with('products:id,name_en,name_ar')->findOrFail($id);

        abort_unless($this->policy->view(Auth::guard('vendor')->user(), $coupon), 403);

        return view('vendor.coupons.create', [
            'coupon' => VendorCouponResource::make($coupon)->resolve(),
        ]);
    }

    public function edit(string $id): View
    {
        return $this->show($id);
    }

    public function update(UpdateCouponRequest $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $actor = Auth::guard('vendor')->user();

        abort_unless($this->policy->update($actor, $coupon), 403);

        try {
            $this->coupons->update($coupon, $actor->vendor, $request->validated());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        abort_unless($this->policy->toggleActive(Auth::guard('vendor')->user(), $coupon), 403);

        $coupon->update(['is_active' => !$coupon->is_active]);

        return response()->json(['success' => true, 'is_active' => $coupon->is_active]);
    }

    public function destroy(string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $actor = Auth::guard('vendor')->user();

        abort_unless($this->policy->update($actor, $coupon), 403);

        if ($coupon->times_used > 0) {
            return response()->json(['message' => 'This coupon has already been used and cannot be deleted.'], 422);
        }

        $this->coupons->delete($coupon);

        return response()->json(['success' => true]);
    }
}
