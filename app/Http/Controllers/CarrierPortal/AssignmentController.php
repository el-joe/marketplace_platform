<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAssignment;
use App\Models\Shipment;
use App\Notifications\DeliveryAgent\DeliveryReassigned;
use App\Notifications\DeliveryAgent\NewDeliveryAssigned;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, 'ليس لديك صلاحية لعرض الطلبات.');

        $agentIds = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->pluck('id');

        $assignments = DeliveryAssignment::whereIn('agent_id', $agentIds)
            ->with('agent')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('assigned_at')
            ->paginate(25);

        $agents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->active()
            ->get(['id', 'name', 'vehicle_type', 'rating_avg']);

        return view('carrier.assignments.index', compact('assignments', 'agents'));
    }

    public function show(string $assignmentId): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, 'ليس لديك صلاحية لعرض الطلبات.');

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

        abort_unless($supervisor->hasPermission('assign_orders'), 403, 'ليس لديك صلاحية لإعادة التعيين.');

        $request->validate(['new_agent_id' => 'required|string|exists:delivery_agents,id']);

        $assignment = DeliveryAssignment::query()
            ->whereHas('agent', fn ($q) => $q->where('shipping_company_id', $supervisor->shipping_company_id))
            ->findOrFail($assignmentId);

        if (!in_array($assignment->status?->value, ['assigned', 'accepted'])) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إعادة تعيين هذا الطلب في حالته الحالية.'], 422);
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

        return response()->json(['success' => true, 'message' => 'تم إعادة تعيين الطلب بنجاح.']);
    }

    public function unassigned(Request $request): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, 'ليس لديك صلاحية لعرض الطلبات.');

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

        abort_unless($supervisor->hasPermission('assign_orders'), 403, 'ليس لديك صلاحية لتعيين الطلبات.');

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

        return response()->json(['success' => true, 'message' => 'تم تعيين المندوب بنجاح.']);
    }
}
