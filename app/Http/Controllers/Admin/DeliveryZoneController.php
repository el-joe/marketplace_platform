<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryAgentStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryAgent;
use App\Models\DeliveryZone;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    use HasDataTable;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $zones = DeliveryZone::with('country')
            ->withCount(['agents' => fn($q) => $q->whereIn('status', [DeliveryAgentStatus::Active, DeliveryAgentStatus::OnShift])])
            ->orderBy('name')
            ->get();

        $countries = Country::orderBy('name_en')->get(['id', 'name_en']);

        return view('admin.delivery.zones.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Delivery'],
                ['label' => 'Zones'],
            ],
            'zones' => $zones,
            'countries' => $countries,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:delivery_zones,code', 'regex:/^[A-Z0-9_-]+$/'],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['exists:cities,id'],
            'base_delivery_fee_cents' => ['required', 'integer', 'min:0'],
            'cod_fee_cents' => ['required', 'integer', 'min:0'],
            'max_active_agents' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $zone = DeliveryZone::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zone created.',
            'zone' => $zone->load('country'),
        ]);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(DeliveryZone $zone): JsonResponse
    {
        $zone->load('country');

        return response()->json(['zone' => $zone]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, DeliveryZone $zone): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:delivery_zones,code,' . $zone->id, 'regex:/^[A-Z0-9_-]+$/'],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['exists:cities,id'],
            'base_delivery_fee_cents' => ['required', 'integer', 'min:0'],
            'cod_fee_cents' => ['required', 'integer', 'min:0'],
            'max_active_agents' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $zone->update($validated);

        return response()->json(['success' => true, 'message' => 'Zone updated.']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(DeliveryZone $zone): JsonResponse
    {
        if ($zone->agents()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete zone with assigned agents. Reassign them first.'], 422);
        }

        $zone->delete();

        return response()->json(['success' => true, 'message' => 'Zone deleted.']);
    }

    // ── Assign Agents (bulk) ──────────────────────────────────────────────────

    public function assignAgents(Request $request, DeliveryZone $zone): JsonResponse
    {
        $request->validate([
            'agent_ids' => ['required', 'array'],
            'agent_ids.*' => ['exists:delivery_agents,id'],
        ]);

        DeliveryAgent::whereIn('id', $request->input('agent_ids'))
            ->update(['zone_id' => $zone->id]);

        $count = count($request->input('agent_ids'));

        return response()->json(['success' => true, 'message' => "{$count} agent(s) assigned to {$zone->name}."]);
    }

    // ── Live Map Data ─────────────────────────────────────────────────────────

    public function getAgentMap(DeliveryZone $zone): JsonResponse
    {
        $agents = DeliveryAgent::where('zone_id', $zone->id)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->select(['id', 'name', 'status', 'is_available', 'current_latitude', 'current_longitude', 'last_location_at'])
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'status' => $a->status?->value,
                'is_available' => (bool) $a->is_available,
                'lat' => (float) $a->current_latitude,
                'lng' => (float) $a->current_longitude,
                'updated' => $a->last_location_at?->diffForHumans() ?? 'Unknown',
            ]);

        return response()->json(['agents' => $agents]);
    }
}
