<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('view_orders'), 403, 'ليس لديك صلاحية لعرض الطلبات.');

        $agentIds = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->pluck('id');

        // Supervisor sees ALL assignments for their company's agents — even unaccepted ones.
        // This is the core "مشرف يستقبل كل الطلبات حتى لو المندوبين لم يتفاعلوا" requirement.
        $assignments = DeliveryAssignment::whereIn('agent_id', $agentIds)
            ->with('agent')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('assigned_at')
            ->paginate(25);

        $agents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->active()
            ->get(['id', 'name']);

        return view('carrier.assignments.index', compact('assignments', 'agents'));
    }

    public function reassign(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $supervisor = auth('shipping_supervisor')->user();

        abort_unless($supervisor->hasPermission('assign_orders'), 403, 'ليس لديك صلاحية لإعادة التعيين.');

        // Ensure the assignment belongs to this company's agents
        $agentIds = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->pluck('id');

        abort_unless($agentIds->contains($assignment->agent_id), 403);

        $data = $request->validate([
            'agent_id' => ['required', 'string', 'in:' . $agentIds->implode(',')],
        ]);

        $assignment->update([
            'agent_id'    => $data['agent_id'],
            'status'      => 'assigned',
            'accepted_at' => null,
        ]);

        return back()->with('success', 'تم إعادة تعيين الطلب بنجاح.');
    }
}
