<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\SubOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayoutController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $payouts = Payout::where('vendor_id', $this->vendorId())
            ->orderByDesc('period_end')
            ->paginate(20);

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $thisMonthEarnings = SubOrder::where('vendor_id', $this->vendorId())
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->sum('vendor_payout');

        $pendingAmount = Payout::where('vendor_id', $this->vendorId())
            ->where('status', 'pending')
            ->sum('net_amount');

        $lastPayout = Payout::where('vendor_id', $this->vendorId())
            ->where('status', 'completed')
            ->orderByDesc('processed_at')
            ->first(['net_amount', 'processed_at', 'payout_number']);

        return view('partner.payouts.index', compact(
            'payouts',
            'thisMonthEarnings',
            'pendingAmount',
            'lastPayout',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $payoutNumber): View
    {
        $payout = Payout::where('vendor_id', $this->vendorId())
            ->where('payout_number', $payoutNumber)
            ->with(['items.subOrder', 'bankAccount'])
            ->firstOrFail();

        return view('partner.payouts.show', compact('payout'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Earnings Summary (AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    public function earningsSummary(): JsonResponse
    {
        $vendorId = $this->vendorId();
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        $thisMonth = SubOrder::where('vendor_id', $vendorId)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->sum('vendor_payout');

        $pending = Payout::where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->sum('net_amount');

        $ytd = SubOrder::where('vendor_id', $vendorId)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$startOfYear, $now])
            ->sum('vendor_payout');

        $lastPayout = Payout::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->orderByDesc('processed_at')
            ->first(['net_amount', 'processed_at', 'payout_number']);

        return response()->json([
            'this_month' => round($thisMonth / 100, 2),
            'pending' => round($pending / 100, 2),
            'ytd' => round($ytd / 100, 2),
            'last_payout' => $lastPayout ? [
                'amount' => round($lastPayout->net_amount / 100, 2),
                'date' => $lastPayout->processed_at?->format('Y-m-d'),
                'number' => $lastPayout->payout_number,
            ] : null,
        ]);
    }
}
