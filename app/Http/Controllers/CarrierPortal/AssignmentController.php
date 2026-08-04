<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAssignment;
use App\Models\Shipment;
use App\Notifications\DeliveryAgent\DeliveryReassigned;
use App\Notifications\DeliveryAgent\NewDeliveryAssigned;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    use HasDataTable;
    use HasExport;

    public function index(Request $request): View|StreamedResponse
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, __('carrier.errors.no_permission_view_orders'));

        if ($request->filled('export')) {
            return $this->exportAssignments($request, $supervisor);
        }

        $assignments = $this->buildAssignmentsQuery($request, $supervisor)
            ->with('agent')
            ->latest('assigned_at')
            ->paginate(25);

        $agents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->active()
            ->get(['id', 'name', 'vehicle_type', 'rating_avg']);

        return view('carrier.assignments.index', compact('assignments', 'agents'));
    }

    /** Shared query for the index view and the export, scoped to the supervisor's company. */
    private function buildAssignmentsQuery(Request $request, $supervisor): Builder
    {
        $agentIds = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->pluck('id');

        $query = DeliveryAssignment::whereIn('agent_id', $agentIds);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('agent', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        return $this->applyFilters($query, $request, [
            'status' => fn ($q, $v) => $q->where('status', $v),
            'date_from' => fn ($q, $v) => $q->whereDate('assigned_at', '>=', $v),
            'date_to' => fn ($q, $v) => $q->whereDate('assigned_at', '<=', $v),
        ]);
    }

    private function exportAssignments(Request $request, $supervisor): StreamedResponse
    {
        $assignments = $this->buildAssignmentsQuery($request, $supervisor)
            ->with('agent')
            ->latest('assigned_at')
            ->get();

        $headers = ['Assignment #', 'Agent', 'Status', 'Date'];

        $rows = $assignments->map(fn (DeliveryAssignment $a) => [
            $a->id,
            $a->agent?->name ?? '—',
            $a->status?->value,
            $a->assigned_at?->format('Y-m-d H:i') ?? '—',
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('carrier-assignments', $headers, $rows),
            'csv' => $this->exportCsv('carrier-assignments', $headers, $rows),
            'word' => $this->exportWord('carrier-assignments', 'Carrier Assignments', $rows),
            default => abort(400, __('carrier.errors.invalid_export_format')),
        };
    }

    public function show(string $assignmentId): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, __('carrier.errors.no_permission_view_orders'));

        $assignment = DeliveryAssignment::query()
            ->whereHas('agent', fn ($q) => $q->where('shipping_company_id', $supervisor->shipping_company_id))
            ->with([
                'agent',
                'shipment.subOrder.order.customer',
                'shipment.subOrder.items',
                'shipment.trackingEvents' => fn ($q) => $q->orderBy('occurred_at'),
            ])
            ->findOrFail($assignmentId);

        $availableAgents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->where('status', 'active')
            ->where('is_available', true)
            ->where('id', '!=', $assignment->agent_id)
            ->get(['id', 'name', 'vehicle_type', 'rating_avg']);

        return view('carrier.assignments.show', compact('assignment', 'availableAgents'));
    }

    public function reassign(Request $request, string $assignmentId): JsonResponse
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('assign_orders'), 403, __('carrier.errors.no_permission_reassign'));

        $request->validate(['new_agent_id' => 'required|string|exists:delivery_agents,id']);

        $assignment = DeliveryAssignment::query()
            ->whereHas('agent', fn ($q) => $q->where('shipping_company_id', $supervisor->shipping_company_id))
            ->findOrFail($assignmentId);

        if (!in_array($assignment->status?->value, ['assigned', 'accepted'])) {
            return response()->json(['success' => false, 'message' => __('carrier.assignments.cannot_reassign_picked_up')], 422);
        }

        // Scope new agent to same company — prevents assigning to another company's agent.
        $newAgent = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->where('id', $request->new_agent_id)
            ->where('status', 'active')
            ->firstOrFail();

        $oldAgentId = $assignment->agent_id;

        DB::transaction(function () use ($assignment, $newAgent) {
            $assignment->update([
                'agent_id'    => $newAgent->id,
                'status'      => 'assigned',
                'assigned_at' => now(),
                'accepted_at' => null,
            ]);
            // NOTE: No dedicated reassignment audit table exists in this schema.
            // The reassignment is reflected in the assignment record itself (agent_id, assigned_at reset).
            // Consider adding an assignment_history table for a proper audit trail.
        });

        $oldAgent = DeliveryAgent::find($oldAgentId);
        if ($oldAgent) {
            $oldAgent->notify(new DeliveryReassigned($assignment, $oldAgentId));
        }
        $newAgent->notify(new NewDeliveryAssigned($assignment));

        return response()->json(['success' => true, 'message' => __('carrier.assignments.reassigned_success')]);
    }

    public function unassigned(Request $request): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, __('carrier.errors.no_permission_view_orders'));

        // NOTE: shipments.carrier_id → shipping_carriers (DHL/FedEx integrations) is a DIFFERENT
        // concept from shipping_company_id (local delivery companies with supervisors/agents).
        // There is no schema link between them, so we show all unassigned non-delivered shipments.
        // A future shipping_company_id column on shipments would enable company-scoped filtering.
        $shipments = Shipment::whereDoesntHave('deliveryAssignment')
            ->where('status', '!=', 'delivered')
            ->with('subOrder.order')
            ->latest()
            ->paginate(20);

        $agents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->where('status', 'active')
            ->get(['id', 'name', 'vehicle_type', 'is_available', 'rating_avg']);

        return view('carrier.assignments.unassigned', compact('shipments', 'agents'));
    }

    public function assign(Request $request, string $shipmentId): JsonResponse
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('assign_orders'), 403, __('carrier.errors.no_permission_assign'));

        $request->validate(['agent_id' => 'required|string|exists:delivery_agents,id']);

        $shipment = Shipment::whereDoesntHave('deliveryAssignment')
            ->findOrFail($shipmentId);

        $agent = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->where('id', $request->agent_id)
            ->where('status', 'active')
            ->firstOrFail();

        $assignment = null;

        DB::transaction(function () use ($shipment, $agent, &$assignment) {
            $assignment = DeliveryAssignment::create([
                'shipment_id'  => $shipment->id,
                'sub_order_id' => $shipment->sub_order_id,
                'agent_id'     => $agent->id,
                'status'       => 'assigned',
                'assigned_at'  => now(),
                'delivery_otp' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);
        });

        $agent->notify(new NewDeliveryAssigned($assignment));

        return response()->json(['success' => true, 'message' => __('carrier.assignments.assign_success')]);
    }
}
