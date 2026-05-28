<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Vendor;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    use HasDataTable;

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.coupons.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Coupons'],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTable
    // ─────────────────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $columns = $this->columnDefinitions();

        $query = Coupon::query()
            ->select([
                'coupons.id',
                'coupons.code',
                'coupons.name',
                'coupons.type',
                'coupons.value',
                'coupons.scope',
                'coupons.times_used',
                'coupons.usage_limit_total',
                'coupons.customer_eligibility',
                'coupons.valid_from',
                'coupons.valid_until',
                'coupons.is_active',
                'coupons.is_stackable',
                'coupons.created_at',
            ]);

        $query = $this->applyFilters($query, $request, [
            'is_active' => fn($q, $v) => $q->where('coupons.is_active', (bool) $v),
            'type' => fn($q, $v) => $q->where('coupons.type', $v),
            'scope' => fn($q, $v) => $q->where('coupons.scope', $v),
            'expired' => function ($q, $v) {
                if ($v === '1') {
                    $q->where('coupons.valid_until', '<', now());
                } elseif ($v === '0') {
                    $q->where('coupons.valid_until', '>=', now());
                }
            },
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'name' => e($row->name),
                'type' => $row->type,
                'value' => $row->value,
                'scope' => $row->scope,
                'times_used' => (int) $row->times_used,
                'usage_limit_total' => $row->usage_limit_total ? (int) $row->usage_limit_total : null,
                'customer_eligibility' => $row->customer_eligibility,
                'valid_from' => $row->valid_from,
                'valid_until' => $row->valid_until,
                'is_active' => (bool) $row->is_active,
                'is_stackable' => (bool) $row->is_stackable,
                'created_at' => $row->created_at,
                'is_expired' => $row->valid_until < now()->toDateTimeString(),
                'edit_url' => route('admin.coupons.edit', $row->id),
                'delete_url' => route('admin.coupons.destroy', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('admin.coupons.create', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Coupons', 'url' => route('admin.coupons.index')],
                ['label' => 'New Coupon'],
            ],
            'vendors' => Vendor::query()->orderBy('store_name')->get(['id', 'store_name']),
            'categories' => Category::query()->where('is_active', true)->whereNull('deleted_at')->orderBy('name_en')->get(['id', 'name_en']),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['is_stackable'] = (bool) ($data['is_stackable'] ?? false);
        $data['created_by_user_id'] = Auth::guard('admin')->id();

        // Clear scope-specific foreign keys
        if ($data['scope'] !== 'vendor') {
            $data['vendor_id'] = null;
        }
        if ($data['scope'] !== 'category') {
            $data['category_id'] = null;
        }

        DB::beginTransaction();
        try {
            $coupon = Coupon::query()->create(array_merge(['id' => Str::uuid()->toString()], $data));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CouponController@store failed', ['error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to create coupon.'], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Failed to create coupon.']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('admin.coupons.edit', $coupon->id),
            ]);
        }

        return redirect()->route('admin.coupons.edit', $coupon->id)
            ->with('success', 'Coupon created successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Edit / Update
    // ─────────────────────────────────────────────────────────────────────────

    public function edit(string $coupon): View
    {
        $model = Coupon::findOrFail($coupon);

        $usageCount = CouponUsage::query()->where('coupon_id', $model->id)->count();

        return view('admin.coupons.edit', [
            'coupon' => $model,
            'usageCount' => $usageCount,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Coupons', 'url' => route('admin.coupons.index')],
                ['label' => e($model->code)],
            ],
            'vendors' => Vendor::query()->orderBy('store_name')->get(['id', 'store_name']),
            'categories' => Category::query()->where('is_active', true)->whereNull('deleted_at')->orderBy('name_en')->get(['id', 'name_en']),
        ]);
    }

    public function update(UpdateCouponRequest $request, string $coupon): JsonResponse
    {
        $model = Coupon::findOrFail($coupon);

        $data = $request->validated();
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['is_stackable'] = (bool) ($data['is_stackable'] ?? false);

        if ($data['scope'] !== 'vendor') {
            $data['vendor_id'] = null;
        }
        if ($data['scope'] !== 'category') {
            $data['category_id'] = null;
        }

        DB::beginTransaction();
        try {
            $model->update($data);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CouponController@update failed', ['coupon' => $coupon, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update coupon.'], 500);
        }

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(string $coupon): JsonResponse
    {
        $model = Coupon::findOrFail($coupon);

        $usageCount = CouponUsage::query()->where('coupon_id', $model->id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'message' => "Cannot delete coupon: it has been used {$usageCount} time(s).",
            ], 422);
        }

        $model->delete();

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bulk Actions
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkAction(Request $request): JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => 'No coupons selected.'], 422);
        }

        $allowed = ['activate', 'deactivate', 'delete'];
        if (!in_array($action, $allowed, true)) {
            return response()->json(['message' => 'Invalid action.'], 422);
        }

        match ($action) {
            'activate' => Coupon::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Coupon::query()->whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => Coupon::query()
                ->whereIn('id', $ids)
                ->whereNotIn('id', CouponUsage::query()->pluck('coupon_id'))
                ->delete(),
        };

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // Generate Code
    // ─────────────────────────────────────────────────────────────────────────

    public function generateCode(): JsonResponse
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Usages
    // ─────────────────────────────────────────────────────────────────────────

    public function usages(Coupon $coupon): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('coupons.view'), 403);

        $usages = CouponUsage::where('coupon_id', $coupon->id)
            ->with(['customer:id,name,email', 'order:id,order_number,status'])
            ->orderByDesc('used_at')
            ->paginate(20);

        return response()->json([
            'data' => $usages->items(),
            'total' => $usages->total(),
            'current_page' => $usages->currentPage(),
            'last_page' => $usages->lastPage(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private
    // ─────────────────────────────────────────────────────────────────────────

    private function columnDefinitions(): array
    {
        return [
            ['title' => 'Code', 'data' => 'code', 'name' => 'code', 'orderable_column' => 'coupons.code', 'searchable_columns' => ['coupons.code', 'coupons.name']],
            ['title' => 'Name', 'data' => 'name', 'name' => 'name', 'orderable_column' => 'coupons.name', 'searchable' => false],
            ['title' => 'Type', 'data' => 'type', 'name' => 'type', 'orderable_column' => 'coupons.type', 'searchable' => false],
            ['title' => 'Value', 'data' => 'value', 'name' => 'value', 'orderable_column' => 'coupons.value', 'searchable' => false],
            ['title' => 'Scope', 'data' => 'scope', 'name' => 'scope', 'orderable_column' => 'coupons.scope', 'searchable' => false],
            ['title' => 'Used', 'data' => 'times_used', 'name' => 'times_used', 'orderable_column' => 'coupons.times_used', 'searchable' => false],
            ['title' => 'Active', 'data' => 'is_active', 'name' => 'is_active', 'orderable_column' => 'coupons.is_active', 'searchable' => false],
            ['title' => 'Valid Until', 'data' => 'valid_until', 'name' => 'valid_until', 'orderable_column' => 'coupons.valid_until', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ];
    }
}
