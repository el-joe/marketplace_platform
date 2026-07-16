<?php

namespace App\Http\Controllers\Delivery;

use App\Enums\DeliveryAgentCodSettlementStatus;
use App\Enums\DeliveryAgentEarningStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentCodSettlement;
use App\Models\DeliveryAgentEarning;
use App\Models\DeliveryAgentPayout;
use App\Models\DeliveryAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(): View
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        $earnings = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->with('assignment.subOrder')
            ->orderByDesc('created_at')
            ->paginate(20);

        $payouts = DeliveryAgentPayout::where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Settled period ranges — deliveries within these dates are already remitted
        $settledPeriods = DeliveryAgentCodSettlement::where('agent_id', $agent->id)
            ->where('status', DeliveryAgentCodSettlementStatus::Settled)
            ->get(['period_start', 'period_end']);

        // Cash physically held by the agent and not yet remitted
        $cashInHandCents = DeliveryAssignment::where('agent_id', $agent->id)
            ->where('status', DeliveryAssignment::STATUS_DELIVERED)
            ->whereNotNull('cod_amount_collected')
            ->where(function ($q) use ($settledPeriods) {
                foreach ($settledPeriods as $period) {
                    $q->where(function ($inner) use ($period) {
                        $inner->where('delivered_at', '<', $period->period_start)
                            ->orWhere('delivered_at', '>', $period->period_end);
                    });
                }
            })
            ->sum('cod_amount_collected');

        return view('delivery.earnings.index', compact('agent', 'earnings', 'payouts', 'cashInHandCents'));
    }

    public function summary(): JsonResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        $thisMonth = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $pendingBalance = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->where('status', DeliveryAgentEarningStatus::Pending)
            ->sum('amount');

        $paidBalance = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->where('status', DeliveryAgentEarningStatus::Paid)
            ->sum('amount');

        $todayEarnings = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->whereDate('created_at', today())
            ->sum('amount');

        return response()->json([
            'today' => $todayEarnings,
            'month' => $thisMonth,
            'pending' => $pendingBalance,
            'paid' => $paidBalance,
        ]);
    }
}
