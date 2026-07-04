<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $pending = $marketer->conversions()
            ->where('status', 'pending')
            ->with('campaign:id,name')
            ->latest()
            ->paginate(15, ['*'], 'pending_page');

        $approved = $marketer->conversions()
            ->where('status', 'approved')
            ->with('campaign:id,name')
            ->latest()
            ->paginate(15, ['*'], 'approved_page');

        $payouts = $marketer->payouts()
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'payout_page');

        // Group by currency — summing across currencies would produce a meaningless number
        // for marketers who earn commissions in more than one country's currency.
        $summary = [];
        foreach (['pending', 'approved', 'paid'] as $status) {
            $summary[$status] = $marketer->conversions()
                ->where('status', $status)
                ->selectRaw('currency, SUM(commission_amount_cents) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency');
        }

        return view('marketer.earnings.index', [
            'marketer' => $marketer,
            'pending' => $pending,
            'approved' => $approved,
            'payouts' => $payouts,
            'summary' => $summary,
        ]);
    }

    public function summary(): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $byCurrency = [];
        foreach (['pending', 'approved', 'paid'] as $status) {
            $byCurrency[$status] = $marketer->conversions()
                ->where('status', $status)
                ->selectRaw('currency, SUM(commission_amount_cents) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency');
        }

        return response()->json([
            'by_currency' => $byCurrency,
            'total_earnings_cents' => $marketer->total_earnings_cents,
        ]);
    }
}
