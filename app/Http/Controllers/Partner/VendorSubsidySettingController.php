<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\VendorExceptionalZone;
use App\Models\VendorSubsidySetting;
use App\Traits\HasDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VendorSubsidySettingController extends Controller
{
    use HasDataTable;

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    public function index(Request $request): View|JsonResponse
    {
        if (!$request->has('draw')) {
            $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'currency_code']);

            return view('partner.subsidy-settings', compact('countries'));
        }

        $query = VendorSubsidySetting::query()
            ->with('country')
            ->where('vendor_id', $this->vendorId())
            ->select('vendor_subsidy_settings.*');

        $columns = [
            ['orderable_column' => 'country_id'],
            ['orderable_column' => 'admin_support'],
            ['orderable_column' => 'vendor_share'],
            ['orderable_column' => 'effective_from'],
            ['orderable_column' => 'effective_until'],
            [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (VendorSubsidySetting $setting) {
            return [
                'id' => $setting->id,
                'country' => $setting->country?->name_en,
                'admin_support' => $this->formatCents($setting->admin_support, $setting->currency),
                'vendor_pays' => $this->formatCents($setting->vendor_share, $setting->currency),
                'effective_from' => $setting->effective_from->format('d M Y'),
                'effective_until' => $setting->effective_until?->format('d M Y') ?? '∞ No End',
                'status' => $setting->status,
                'can_edit' => $setting->effective_from->isAfter(today()),
                'actions' => [
                    'id' => $setting->id,
                    'country_id' => $setting->country_id,
                    'admin_support' => $setting->admin_support,
                    'vendor_share' => $setting->vendor_share,
                    'currency' => $setting->currency,
                    'effective_from' => $setting->effective_from->toDateString(),
                    'effective_until' => $setting->effective_until?->toDateString(),
                ],
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $validated = $request->validate([
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'admin_support' => ['required', 'integer', 'min:0'],
            'vendor_share' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
        ]);

        $this->assertNoOverlap($vendorId, $validated['country_id'], $validated['effective_from'], $validated['effective_until'] ?? null);

        $setting = VendorSubsidySetting::create($validated + ['vendor_id' => $vendorId]);

        return response()->json(['message' => 'Subsidy setting created.', 'data' => ['id' => $setting->id]]);
    }

    public function update(Request $request, VendorSubsidySetting $setting): JsonResponse
    {
        abort_unless($setting->vendor_id === $this->vendorId(), 404);

        if (!$setting->effective_from->isAfter(today())) {
            return response()->json([
                'message' => 'Cannot edit an active or past setting. Create a new one instead.',
            ], 422);
        }

        $validated = $request->validate([
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'admin_support' => ['required', 'integer', 'min:0'],
            'vendor_share' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
        ]);

        $this->assertNoOverlap(
            $setting->vendor_id,
            $validated['country_id'],
            $validated['effective_from'],
            $validated['effective_until'] ?? null,
            excludeId: $setting->id
        );

        $setting->update($validated);

        return response()->json(['message' => 'Subsidy setting updated.']);
    }

    public function deactivate(VendorSubsidySetting $setting): JsonResponse
    {
        abort_unless($setting->vendor_id === $this->vendorId(), 404);

        $setting->update(['effective_until' => Carbon::yesterday()->toDateString()]);

        return response()->json(['message' => 'Subsidy setting deactivated.']);
    }

    // ── Exceptional zones (read-only) ────────────────────────────────────────

    public function exceptionalZones(Request $request): View|JsonResponse
    {
        if (!$request->has('draw')) {
            return view('partner.exceptional-zones');
        }

        $query = VendorExceptionalZone::query()
            ->with('shippingZone.country')
            ->where('vendor_id', $this->vendorId())
            ->select('vendor_exceptional_zones.*');

        $columns = [
            [],
            [],
            [],
            ['orderable_column' => 'created_at'],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (VendorExceptionalZone $zone) {
            return [
                'zone_name' => $zone->shippingZone?->name,
                'country' => $zone->shippingZone?->country?->name_en,
                'is_active' => $zone->is_active,
                'created_at' => $zone->created_at->format('d M Y'),
            ];
        });
    }

    private function formatCents(int $cents, string $currency): string
    {
        return $currency . ' ' . number_format($cents / 100, 2);
    }

    private function assertNoOverlap(
        string $vendorId,
        string $countryId,
        string $effectiveFrom,
        ?string $effectiveUntil,
        ?string $excludeId = null
    ): void {
        $query = VendorSubsidySetting::query()
            ->where('vendor_id', $vendorId)
            ->where('country_id', $countryId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($effectiveFrom, $effectiveUntil) {
                $q->where('effective_from', '<=', $effectiveUntil ?? '9999-12-31')
                    ->where(function ($q2) use ($effectiveFrom) {
                        $q2->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $effectiveFrom);
                    });
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'This date range overlaps with an existing subsidy setting for this country.',
            ]);
        }
    }
}
