<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerConversion;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EarningsController extends Controller
{
    use HasDataTable;
    use HasExport;

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        if ($request->filled('export')) {
            return $this->exportEarnings($request, $marketer);
        }

        $dateFilters = [
            'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
        ];

        $pending = $this->applyFilters($marketer->conversions()->where('status', 'pending'), $request, $dateFilters)
            ->with('campaign:id,name')
            ->latest()
            ->paginate(15, ['*'], 'pending_page')
            ->withQueryString();

        $approved = $this->applyFilters($marketer->conversions()->where('status', 'approved'), $request, $dateFilters)
            ->with('campaign:id,name')
            ->latest()
            ->paginate(15, ['*'], 'approved_page')
            ->withQueryString();

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
        $conversions = $this->applyFilters($marketer->conversions(), $request, [
            'status' => fn($q, $v) => $q->where('status', $v),
            'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
        ])
            ->with(['campaign:id,name', 'order:id,order_number'])
            ->latest()
            ->get();

        $headers = ['Order Ref', 'Campaign', 'Commission', 'Currency', 'Status', 'Date'];

        $rows = $conversions->map(fn($c) => [
            $this->maskOrderRef((string) ($c->order?->order_number ?? $c->order_id ?? '')),
            $c->campaign?->name ?? '—',
            number_format($c->commission_amount / 100, 2),
            $c->currency,
            $c->status?->value,
            $c->created_at->format('Y-m-d'),
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('earnings', $headers, $rows),
            'csv' => $this->exportCsv('earnings', $headers, $rows),
            'word' => $this->exportWord('earnings', 'Earnings', $rows),
            default => abort(400, 'Invalid export format.'),
        };
    }

    private function maskOrderRef(string $orderRef): string
    {
        return strlen($orderRef) > 8
            ? substr($orderRef, 0, 4) . str_repeat('•', max(0, strlen($orderRef) - 8)) . substr($orderRef, -4)
            : $orderRef;
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
