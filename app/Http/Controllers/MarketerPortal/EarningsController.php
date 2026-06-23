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

        $summary = [
            'pending' => $marketer->conversions()->where('status', 'pending')->sum('commission_amount_cents'),
            'approved' => $marketer->conversions()->where('status', 'approved')->sum('commission_amount_cents'),
            'paid' => $marketer->conversions()->where('status', 'paid')->sum('commission_amount_cents'),
        ];

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

        return response()->json([
            'pending' => $marketer->conversions()->where('status', 'pending')->sum('commission_amount_cents'),
            'approved' => $marketer->conversions()->where('status', 'approved')->sum('commission_amount_cents'),
            'paid' => $marketer->conversions()->where('status', 'paid')->sum('commission_amount_cents'),
            'total' => $marketer->total_earnings_cents,
        ]);
    }
}
