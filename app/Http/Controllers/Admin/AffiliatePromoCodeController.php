<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePromoCode;
use App\Models\Coupon;
use App\Models\Marketer;
use App\Services\AffiliatePromoCodeService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliatePromoCodeController extends Controller
{
    use HasDataTable;

    public function __construct(private readonly AffiliatePromoCodeService $promoCodes)
    {
    }

    public function index(): View
    {
        $stats = [
            'total' => AffiliatePromoCode::count(),
            'active' => AffiliatePromoCode::where('is_active', true)->count(),
            'total_uses' => AffiliatePromoCode::sum('times_used'),
            'total_revenue' => AffiliatePromoCode::sum('total_revenue_generated'),
        ];

        $affiliates = Marketer::where('type', 'affiliate')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.affiliate_promo_codes.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Affiliate Promo Codes'],
            ],
            'stats' => $stats,
            'affiliates' => $affiliates,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['affiliate_promo_codes.code']],
            ['searchable_columns' => ['marketers.name']],
            ['orderable_column' => 'affiliate_promo_codes.discount_value'],
            ['orderable_column' => 'affiliate_promo_codes.times_used'],
            ['orderable_column' => 'affiliate_promo_codes.total_revenue_generated'],
            ['orderable_column' => 'affiliate_promo_codes.total_commission_earned'],
            ['orderable_column' => 'affiliate_promo_codes.is_active'],
            ['orderable_column' => 'affiliate_promo_codes.valid_until'],
            [],
        ];

        $query = AffiliatePromoCode::query()
            ->join('marketers', 'marketers.id', '=', 'affiliate_promo_codes.marketer_id')
            ->select([
                'affiliate_promo_codes.*',
                'marketers.name as marketer_name',
            ]);

        if ($marketerId = $request->input('filter_marketer')) {
            $query->where('affiliate_promo_codes.marketer_id', $marketerId);
        }
        if ($request->filled('filter_active')) {
            $query->where('affiliate_promo_codes.is_active', (bool) $request->input('filter_active'));
        }

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            $discount = $row->discount_type->value === 'percentage'
                ? $row->discount_value . '%'
                : ($row->discount_type->value === 'free_shipping'
                    ? 'Free Shipping'
                    : number_format($row->discount_value, 2) . ' ' . ($row->currency ?? ''));

            return [
                '<span class="font-mono text-xs">' . e($row->code) . '</span>',
                '<a href="' . route('admin.marketers.all.show', $row->marketer_id) . '" class="text-primary-600 hover:underline">' . e($row->marketer_name) . '</a>',
                $discount,
                number_format($row->times_used) . ' / ' . ($row->max_uses ? number_format($row->max_uses) : '∞'),
                number_format($row->total_revenue_generated / 100, 2),
                number_format($row->total_commission_earned / 100, 2),
                '<span class="badge badge-' . ($row->is_active ? 'success' : 'secondary') . '">' . ($row->is_active ? 'Active' : 'Inactive') . '</span>',
                $row->valid_until?->format('d M Y') ?? '—',
                $this->actions($row),
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketer_id' => 'required|uuid|exists:marketers,id',
            'campaign_id' => 'nullable|uuid|exists:marketer_campaigns,id',
            'code' => 'nullable|string|max:50',
            'discount_type' => 'required|in:percentage,fixed_amount,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
        ]);

        $marketer = Marketer::findOrFail($validated['marketer_id']);

        if (!$marketer->isAffiliate()) {
            return response()->json([
                'success' => false,
                'message' => 'Promo codes can only be created for affiliate marketers.',
            ], 422);
        }

        if ($validated['discount_type'] === 'fixed_amount' && empty($validated['currency'])) {
            return response()->json([
                'success' => false,
                'message' => 'Currency is required for fixed amount discounts.',
            ], 422);
        }

        $code = $validated['code'] ?? null;

        if ($code) {
            $exists = AffiliatePromoCode::where('code', $code)->exists()
                || Coupon::where('code', $code)->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This code is already in use.',
                ], 422);
            }
        } else {
            $code = $this->promoCodes->generateCode($marketer);
        }

        $promoCode = AffiliatePromoCode::create([
            ...$validated,
            'code' => $code,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo code created successfully.',
            'code' => $promoCode->code,
        ], 201);
    }

    public function toggle(string $id): JsonResponse
    {
        $promoCode = AffiliatePromoCode::findOrFail($id);
        $promoCode->update(['is_active' => !$promoCode->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $promoCode->is_active,
            'message' => $promoCode->is_active ? 'Promo code activated.' : 'Promo code deactivated.',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $promoCode = AffiliatePromoCode::findOrFail($id);
        $promoCode->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Promo code disabled.']);
    }

    private function actions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-' . ($row->is_active ? 'warning' : 'success') . ' btn-toggle-promo">' . ($row->is_active ? 'Deactivate' : 'Activate') . '</button>';
        $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-danger btn-disable-promo">Disable</button>';
        $html .= '</div>';
        return $html;
    }
}
