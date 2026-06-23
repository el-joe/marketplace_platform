<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $supervisor = auth('shipping_supervisor')->user();
        $company    = $supervisor->company;

        $agentIds = $company->agents()->pluck('id');

        $stats = [
            'total_agents'     => $agentIds->count(),
            'active_agents'    => $company->agents()->where('status', 'active')->count(),
            'pending'          => DeliveryAssignment::whereIn('agent_id', $agentIds)
                                    ->where('status', 'assigned')->count(),
            'in_progress'      => DeliveryAssignment::whereIn('agent_id', $agentIds)
                                    ->whereIn('status', ['accepted', 'picked_up'])->count(),
            'delivered_today'  => DeliveryAssignment::whereIn('agent_id', $agentIds)
                                    ->where('status', 'delivered')
                                    ->whereDate('delivered_at', today())->count(),
        ];

        $recentAssignments = DeliveryAssignment::whereIn('agent_id', $agentIds)
            ->with('agent')
            ->latest('assigned_at')
            ->limit(10)
            ->get();

        return view('carrier.dashboard', compact('company', 'stats', 'recentAssignments'));
    }
}
