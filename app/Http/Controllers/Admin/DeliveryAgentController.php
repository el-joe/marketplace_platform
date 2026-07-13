<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryAgentEarningStatus;
use App\Enums\DeliveryAgentStatus;
use App\Enums\DeliveryAgentType;
use App\Enums\DeliveryAgentVehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeliveryAgentRequest;
use App\Models\Country;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentDocument;
use App\Models\DeliveryZone;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryAgentController extends Controller
{
    use HasDataTable;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $countries = Country::orderBy('name_en')->get(['id', 'name_en']);
        $zones = DeliveryZone::where('is_active', true)->orderBy('name')->get(['id', 'name', 'country_id']);

        $stats = [
            'total' => DeliveryAgent::withoutTrashed()->count(),
            'active' => DeliveryAgent::where('status', DeliveryAgentStatus::Active)->count(),
            'on_shift' => DeliveryAgent::where('status', DeliveryAgentStatus::OnShift)->count(),
            'suspended' => DeliveryAgent::where('status', DeliveryAgentStatus::Suspended)->count(),
        ];

        return view('admin.delivery.agents.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Delivery'],
                ['label' => 'Agents'],
            ],
            'countries' => $countries,
            'zones' => $zones,
            'stats' => $stats,
        ]);
    }

    // ── DataTable ─────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['delivery_agents.name'], 'orderable_column' => 'delivery_agents.name'],
            ['searchable_columns' => ['delivery_agents.phone'], 'orderable_column' => 'delivery_agents.phone'],
            ['orderable_column' => 'countries.name_en'],
            ['orderable_column' => 'delivery_zones.name'],
            ['orderable_column' => 'delivery_agents.agent_type'],
            ['orderable_column' => 'delivery_agents.status'],
            ['orderable_column' => 'delivery_agents.rating_avg'],
            ['orderable_column' => 'delivery_agents.total_deliveries'],
            ['orderable_column' => 'delivery_agents.is_available'],
            ['orderable_column' => 'delivery_agents.last_login_at'],
            [],
        ];

        $query = DeliveryAgent::query()
            ->leftJoin('countries', 'countries.id', '=', 'delivery_agents.country_id')
            ->leftJoin('delivery_zones', 'delivery_zones.id', '=', 'delivery_agents.zone_id')
            ->whereNull('delivery_agents.deleted_at')
            ->select([
                'delivery_agents.id',
                'delivery_agents.name',
                'delivery_agents.email',
                'delivery_agents.phone',
                'delivery_agents.status',
                'delivery_agents.agent_type',
                'delivery_agents.vehicle_type',
                'delivery_agents.rating_avg',
                'delivery_agents.total_deliveries',
                'delivery_agents.is_available',
                'delivery_agents.last_login_at',
                'countries.name_en as country_name',
                'delivery_zones.name as zone_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'country_id' => fn($q, $v) => $q->where('delivery_agents.country_id', $v),
            'zone_id' => fn($q, $v) => $q->where('delivery_agents.zone_id', $v),
            'status' => fn($q, $v) => $q->where('delivery_agents.status', $v),
            'agent_type' => fn($q, $v) => $q->where('delivery_agents.agent_type', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function (DeliveryAgent $agent) {
            return [
                'id' => $agent->id,
                'name' => e($agent->name),
                'email' => e($agent->email),
                'phone' => e($agent->phone),
                'country' => e($agent->country_name ?? '—'),
                'zone' => e($agent->zone_name ?? '—'),
                'agent_type' => $agent->agent_type?->value,
                'status' => $agent->status->value,
                'rating_avg' => $agent->rating_avg ? number_format((float) $agent->rating_avg, 1) : '—',
                'total_deliveries' => (int) $agent->total_deliveries,
                'is_available' => (bool) $agent->is_available,
                'last_login_at' => $agent->last_login_at?->format('d M Y H:i') ?? '—',
                'show_url' => route('admin.delivery.agents.show', $agent->id),
            ];
        });
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(DeliveryAgent $agent): View
    {
        $agent->load([
            'country',
            'zone',
            'documents',
            'shifts' => fn($q) => $q->latest('shift_date')->limit(30),
        ]);

        $assignmentStats = DB::table('delivery_assignments')
            ->where('agent_id', $agent->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = ?) as delivered,
                SUM(status = ?) as failed,
                AVG(NULLIF(customer_rating, 0)) as avg_rating
            ', ['delivered', 'failed'])
            ->first();

        return view('admin.delivery.agents.show', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Delivery Agents', 'url' => route('admin.delivery.agents.index')],
                ['label' => $agent->name],
            ],
            'agent' => $agent,
            'assignmentStats' => $assignmentStats,
            'zones' => DeliveryZone::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreDeliveryAgentRequest $request): JsonResponse
    {
        $agent = DeliveryAgent::create([
            ...$request->validated(),
            'password' => Hash::make($request->input('password')),
            'status' => DeliveryAgentStatus::Active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery agent created successfully.',
            'redirect' => route('admin.delivery.agents.show', $agent->id),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, DeliveryAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:delivery_agents,phone,' . $agent->id],
            'country_id' => ['required', 'exists:countries,id'],
            'zone_id' => ['nullable', 'exists:delivery_zones,id'],
            'agent_type' => ['required', Rule::enum(DeliveryAgentType::class)],
            'vehicle_type' => ['required', Rule::enum(DeliveryAgentVehicleType::class)],
            'national_id' => ['nullable', 'string', 'max:30'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'base_salary_cents' => ['nullable', 'integer', 'min:0'],
            'per_delivery_fee_cents' => ['nullable', 'integer', 'min:0'],
        ]);

        $agent->update($validated);

        return response()->json(['success' => true, 'message' => 'Agent updated.']);
    }

    // ── Suspend ───────────────────────────────────────────────────────────────

    public function suspend(Request $request, DeliveryAgent $agent): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $agent->update(['status' => DeliveryAgentStatus::Suspended]);

        return response()->json(['success' => true, 'message' => 'Agent suspended.']);
    }

    // ── Activate ──────────────────────────────────────────────────────────────

    public function activate(DeliveryAgent $agent): JsonResponse
    {
        $agent->update(['status' => DeliveryAgentStatus::Active, 'is_available' => false]);

        return response()->json(['success' => true, 'message' => 'Agent activated.']);
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetPassword(DeliveryAgent $agent): JsonResponse
    {
        $tempPassword = Str::random(10);
        $agent->update(['password' => Hash::make($tempPassword)]);

        // In production: dispatch a job to SMS/email the agent
        // For now, return temp password so admin can relay it
        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
            'temp_password' => $tempPassword,
        ]);
    }

    // ── Assign to Zone ────────────────────────────────────────────────────────

    public function assignToZone(Request $request, DeliveryAgent $agent): JsonResponse
    {
        $request->validate(['zone_id' => ['nullable', 'exists:delivery_zones,id']]);

        $agent->update(['zone_id' => $request->input('zone_id')]);

        $zoneName = $agent->fresh('zone')->zone?->name ?? 'No zone';

        return response()->json(['success' => true, 'message' => "Agent assigned to zone: {$zoneName}."]);
    }

    // ── Assignments DataTable (per agent) ────────────────────────────────────

    public function assignmentsDatatable(Request $request, DeliveryAgent $agent): JsonResponse
    {
        $columns = [
            ['orderable_column' => 'sub_orders.sub_order_number'],
            ['orderable_column' => 'delivery_assignments.status'],
            ['orderable_column' => 'delivery_assignments.assigned_at'],
            ['orderable_column' => 'delivery_assignments.delivered_at'],
            ['orderable_column' => 'delivery_assignments.customer_rating'],
        ];

        $query = DB::table('delivery_assignments as da')
            ->leftJoin('sub_orders', 'sub_orders.id', '=', 'da.sub_order_id')
            ->where('da.agent_id', $agent->id)
            ->select([
                'da.id',
                'sub_orders.sub_order_number',
                'da.status',
                'da.assigned_at',
                'da.picked_up_at',
                'da.delivered_at',
                'da.failed_at',
                'da.customer_rating',
                'da.failure_reason',
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('da.status', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            $duration = null;
            if ($row->assigned_at && $row->delivered_at) {
                $duration = (int) \Carbon\Carbon::parse($row->assigned_at)
                    ->diffInMinutes(\Carbon\Carbon::parse($row->delivered_at));
            }

            return [
                'id' => $row->id,
                'sub_order_number' => $row->sub_order_number ?? '—',
                'status' => $row->status,
                'assigned_at' => $row->assigned_at
                    ? \Carbon\Carbon::parse($row->assigned_at)->format('d M Y H:i') : '—',
                'delivered_at' => $row->delivered_at
                    ? \Carbon\Carbon::parse($row->delivered_at)->format('d M Y H:i') : '—',
                'duration_minutes' => $duration,
                'customer_rating' => $row->customer_rating,
                'failure_reason' => $row->failure_reason,
            ];
        });
    }

    // ── Earnings Summary ──────────────────────────────────────────────────────

    public function earningsSummary(DeliveryAgent $agent): JsonResponse
    {
        $now = now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();

        $base = fn($from, $to) => DB::table('delivery_agent_earnings')
            ->where('agent_id', $agent->id)
            ->where('status', '!=', DeliveryAgentEarningStatus::Cancelled->value)
            ->whereBetween('created_at', [$from, $to]);

        $summary = [
            'this_month_cents' => (int) (clone $base($thisMonth, $now->copy()->endOfMonth()))
                ->sum('amount_cents'),
            'last_month_cents' => (int) (clone $base($lastMonth, $lastMonth->copy()->endOfMonth()))
                ->sum('amount_cents'),
            'ytd_cents' => (int) DB::table('delivery_agent_earnings')
                ->where('agent_id', $agent->id)
                ->where('status', '!=', DeliveryAgentEarningStatus::Cancelled->value)
                ->whereYear('created_at', $now->year)
                ->sum('amount_cents'),
        ];

        // Monthly breakdown for chart (last 6 months)
        $monthly = DB::table('delivery_agent_earnings')
            ->where('agent_id', $agent->id)
            ->where('status', '!=', DeliveryAgentEarningStatus::Cancelled->value)
            ->where('created_at', '>=', $now->copy()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount_cents) as total_cents')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get();

        return response()->json(['summary' => $summary, 'monthly' => $monthly]);
    }

    // ── Document Verify / Reject ──────────────────────────────────────────────

    public function verifyDocument(Request $request, DeliveryAgentDocument $doc): JsonResponse
    {
        $doc->update([
            'status' => 'verified',
            'verified_by_admin_id' => auth('admin')->id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Document verified.']);
    }

    public function rejectDocument(Request $request, DeliveryAgentDocument $doc): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $doc->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json(['success' => true, 'message' => 'Document rejected.']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(DeliveryAgent $agent): JsonResponse
    {
        $agent->delete();

        return response()->json(['success' => true, 'message' => 'Agent deleted.']);
    }
}
