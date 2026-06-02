<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentEarning;
use App\Models\DeliveryAgentPayout;
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
            ->with('deliveryAssignment.subOrder')
            ->orderByDesc('created_at')
            ->paginate(20);

        $payouts = DeliveryAgentPayout::where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('delivery.earnings.index', compact('agent', 'earnings', 'payouts'));
    }

    public function summary(): JsonResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        $thisMonth = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_cents');

        $pendingBalance = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->where('status', 'pending')
            ->sum('amount_cents');

        $paidBalance = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->where('status', 'paid')
            ->sum('amount_cents');

        $todayEarnings = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->whereDate('created_at', today())
            ->sum('amount_cents');

        return response()->json([
            'today_cents' => $todayEarnings,
            'month_cents' => $thisMonth,
            'pending_cents' => $pendingBalance,
            'paid_cents' => $paidBalance,
        ]);
    }
}
