<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerConversion;
use App\Traits\HasExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EarningsController extends Controller
{
    use HasExport;

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        if ($request->filled('export')) {
            return $this->exportEarnings($request, $marketer);
        }

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
                ->selectRaw('currency, SUM(commission_amount) as total')
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

    private function exportEarnings(Request $request, $marketer): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $conversions = $marketer->conversions()
            ->with('campaign:id,name')
            ->latest()
            ->get();

        $headers = ['Campaign', 'Status', 'Commission', 'Currency', 'Date'];

        $rows = $conversions->map(fn($c) => [
            $c->campaign?->name ?? '—',
            $c->status?->value,
            number_format($c->commission_amount / 100, 2),
            $c->currency,
            $c->created_at->format('Y-m-d'),
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('earnings', $headers, $rows),
            'csv' => $this->exportCsv('earnings', $headers, $rows),
            'word' => $this->exportWord('earnings', 'Earnings', $rows),
            default => abort(400, 'Invalid export format.'),
        };
    }

    public function summary(): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $byCurrency = [];
        foreach (['pending', 'approved', 'paid'] as $status) {
            $byCurrency[$status] = $marketer->conversions()
                ->where('status', $status)
                ->selectRaw('currency, SUM(commission_amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency');
        }

        return response()->json([
            'by_currency' => $byCurrency,
            'total_earnings' => $marketer->total_earnings,
        ]);
    }
}
