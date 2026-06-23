<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\SubOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function transactions(Request $request)
    {
        $vendorAdmin = Auth::guard('vendor')->user();
        $vendor      = $vendorAdmin->vendor;
        $vendorId    = $vendor->id;
        $currency    = $vendor->country?->currency_code ?? 'SAR';

        // ── Date range ────────────────────────────────────────────────────────
        $dateFrom = $request->input('date_from')
            ? \Carbon\Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->input('date_to')
            ? \Carbon\Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $type = $request->input('type', 'all'); // all | sales | refunds | payouts

        // ── Summary stats for the selected range ──────────────────────────────
        $totalSales = SubOrder::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('vendor_payout');

        $totalRefunds = Refund::whereHas('subOrder', fn ($q) => $q->where('vendor_id', $vendorId))
            ->where('status', 'completed')
            ->where('vendor_charged_back', true)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $totalPaidOut = Payout::where('vendor_id', $vendorId)
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->sum('net_amount');

        // ── Build unified transaction collection (paginated) ──────────────────
        // We pull three query sets and merge them in PHP. For large datasets
        // this page would need a UNION query; for now the vendor's transaction
        // volume is modest, so PHP-merge + paginate is acceptable.

        $perPage = 30;
        $page    = max(1, (int) $request->input('page', 1));

        $rows = collect();

        if (in_array($type, ['all', 'sales'])) {
            SubOrder::where('vendor_id', $vendorId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->with(['order:id,order_number', 'payoutItems.payout:id,payout_number'])
                ->get()
                ->each(function ($so) use (&$rows) {
                    $payoutItem = $so->payoutItems->first();
                    $rows->push([
                        'type'          => 'sale',
                        'date'          => $so->created_at,
                        'reference'     => $so->sub_order_number,
                        'description'   => 'مبيعات – ' . ($so->order?->order_number ?? ''),
                        'amount'        => $so->vendor_payout,
                        'gross'         => $so->subtotal,
                        'commission'    => $so->platform_commission,
                        'net'           => $so->vendor_payout,
                        'payout_number' => $payoutItem?->payout?->payout_number,
                        'receipt_url'   => null,
                    ]);
                });
        }

        if (in_array($type, ['all', 'refunds'])) {
            Refund::whereHas('subOrder', fn ($q) => $q->where('vendor_id', $vendorId))
                ->where('status', 'completed')
                ->where('vendor_charged_back', true)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->with('subOrder:id,sub_order_number,order_id', 'subOrder.order:id,order_number')
                ->get()
                ->each(function ($ref) use (&$rows) {
                    $rows->push([
                        'type'          => 'refund',
                        'date'          => $ref->created_at,
                        'reference'     => $ref->subOrder?->sub_order_number ?? ('REF-' . $ref->id),
                        'description'   => 'مرتجع – ' . ($ref->subOrder?->order?->order_number ?? ''),
                        'amount'        => -$ref->amount,
                        'gross'         => null,
                        'commission'    => null,
                        'net'           => null,
                        'payout_number' => null,
                        'receipt_url'   => null,
                    ]);
                });
        }

        if (in_array($type, ['all', 'payouts'])) {
            Payout::where('vendor_id', $vendorId)
                ->where('status', 'completed')
                ->whereBetween('processed_at', [$dateFrom, $dateTo])
                ->get()
                ->each(function ($po) use (&$rows) {
                    $rows->push([
                        'type'          => 'payout',
                        'date'          => $po->processed_at ?? $po->created_at,
                        'reference'     => $po->payout_number,
                        'description'   => 'تحويل بنكي',
                        'amount'        => -$po->net_amount,
                        'gross'         => null,
                        'commission'    => null,
                        'net'           => null,
                        'payout_number' => $po->payout_number,
                        'receipt_url'   => $po->receipt_url,
                    ]);
                });
        }

        // Sort newest first then paginate manually
        $sorted     = $rows->sortByDesc('date')->values();
        $total      = $sorted->count();
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('partner.finance.transactions', compact(
            'vendor',
            'currency',
            'transactions',
            'totalSales',
            'totalRefunds',
            'totalPaidOut',
            'dateFrom',
            'dateTo',
            'type',
        ));
    }
}
