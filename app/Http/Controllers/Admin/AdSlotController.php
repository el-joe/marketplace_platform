<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerPlacementDefinition;
use App\Models\Country;
use App\Models\PaidAdSlot;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdSlotController extends Controller
{
    use HasDataTable;

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $stats = [
            'total' => PaidAdSlot::count(),
            'available' => PaidAdSlot::where('is_available', true)->count(),
        ];

        return view('admin.ad-slots.index', compact('stats'));
    }

    // ─── DataTable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $query = PaidAdSlot::query()->with(['placementDefinition', 'country']);

        $query = $this->applyFilters($query, $request, [
            'is_available' => fn($q, $v) => $q->where('is_available', (int) $v),
            'country_id' => fn($q, $v) => $q->where('country_id', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['paid_ad_slots.name', 'paid_ad_slots.slot_code'], 'orderable_column' => 'paid_ad_slots.name'],
            ['searchable_columns' => [], 'orderable_column' => null], // placement
            ['searchable_columns' => [], 'orderable_column' => null], // country
            ['searchable_columns' => [], 'orderable_column' => 'pricing_model'],
            ['searchable_columns' => [], 'orderable_column' => 'base_rate_cents'],
            ['searchable_columns' => [], 'orderable_column' => null], // booking days
            ['searchable_columns' => [], 'orderable_column' => 'is_available'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        $canEdit = $admin->hasPermissionTo('ad_campaigns.edit');

        return $this->dataTableResponse($request, $query, $columns, function (PaidAdSlot $row) use ($canEdit) {
            $isAvailBadge = $row->is_available
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Available</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unavailable</span>';

            $rate = '$' . number_format($row->base_rate_cents / 100, 2);
            $pricingModelLabel = ucwords(str_replace('_', '/', $row->pricing_model));

            $editUrl = route('admin.ad-slots.edit', $row->id);
            $bookingsUrl = route('admin.ad-slots.bookings', $row->id);

            $actions = '<div class="flex items-center gap-1">';
            $actions .= "<a href=\"{$bookingsUrl}\" class=\"btn btn-xs btn-secondary\">Bookings</a>";
            if ($canEdit) {
                $actions .= "<a href=\"{$editUrl}\" class=\"btn btn-xs btn-ghost\">Edit</a>";
            }
            $actions .= '</div>';

            return [
                'name' => e($row->name) . '<br><span class="text-xs text-gray-400 font-mono">' . e($row->slot_code) . '</span>',
                'placement' => e($row->placementDefinition?->name ?? '—'),
                'country' => $row->country
                    ? ($row->country->flag_emoji ? $row->country->flag_emoji . ' ' : '') . e($row->country->name_en)
                    : '<span class="text-gray-400 text-xs">Global</span>',
                'pricing_model' => $pricingModelLabel,
                'base_rate' => $rate . ' <span class="text-xs text-gray-400">/ ' . strtolower($row->pricing_model) . '</span>',
                'booking_days' => $row->min_booking_days . ' – ' . ($row->max_booking_days ?? '∞') . ' days',
                'is_available' => $isAvailBadge,
                'actions' => $actions,
                'DT_RowData' => ['id' => $row->id],
            ];
        });
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $placements = BannerPlacementDefinition::where('is_active', true)->orderBy('sort_order')->get();
        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.ad-slots.create', compact('placements', 'countries'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slot_code' => ['required', 'string', 'max:50', 'unique:paid_ad_slots,slot_code'],
            'placement_definition_id' => ['nullable', 'uuid', 'exists:banner_placement_definitions,id'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'pricing_model' => ['required', Rule::in(['fixed_weekly', 'fixed_monthly', 'cpm', 'cpc'])],
            'base_rate_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'min_booking_days' => ['required', 'integer', 'min:1'],
            'max_booking_days' => ['nullable', 'integer', 'min:1'],
            'is_available' => ['boolean'],
            'requires_approval' => ['boolean'],
            'min_seller_tier' => ['nullable', 'string', 'max:20'],
            'notes_for_vendors' => ['nullable', 'string'],
        ]);

        $data['created_by_admin_id'] = $admin->id;

        PaidAdSlot::create($data);

        return response()->json([
            'message' => 'Ad slot created.',
            'redirect' => route('admin.ad-slots.index'),
        ]);
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(PaidAdSlot $adSlot): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $placements = BannerPlacementDefinition::where('is_active', true)->orderBy('sort_order')->get();
        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.ad-slots.edit', compact('adSlot', 'placements', 'countries'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, PaidAdSlot $adSlot): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slot_code' => ['required', 'string', 'max:50', Rule::unique('paid_ad_slots', 'slot_code')->ignore($adSlot->id)],
            'placement_definition_id' => ['nullable', 'uuid', 'exists:banner_placement_definitions,id'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'pricing_model' => ['required', Rule::in(['fixed_weekly', 'fixed_monthly', 'cpm', 'cpc'])],
            'base_rate_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'min_booking_days' => ['required', 'integer', 'min:1'],
            'max_booking_days' => ['nullable', 'integer', 'min:1'],
            'is_available' => ['boolean'],
            'requires_approval' => ['boolean'],
            'min_seller_tier' => ['nullable', 'string', 'max:20'],
            'notes_for_vendors' => ['nullable', 'string'],
        ]);

        $adSlot->update($data);

        return response()->json([
            'message' => 'Ad slot updated.',
            'redirect' => route('admin.ad-slots.index'),
        ]);
    }

    // ─── Bookings for slot ────────────────────────────────────────────────────

    public function bookings(PaidAdSlot $adSlot): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $adSlot->load(['placementDefinition', 'country']);

        $bookings = $adSlot->bookings()
            ->with(['vendor', 'country', 'approvedByAdmin'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.ad-slots.bookings', compact('adSlot', 'bookings'));
    }
}
