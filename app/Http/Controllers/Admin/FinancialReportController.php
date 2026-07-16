<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class FinancialReportController extends Controller
{
    public function __construct(protected FinancialReportService $reports)
    {
    }

    public function index(): \Illuminate\View\View
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'symbol', 'exchange_rate_to_base', 'rate_updated_at', 'is_manually_overridden']);

        return view('admin.reports.financial', compact('currencies'));
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'from'             => ['required', 'date'],
            'to'               => ['required', 'date', 'after_or_equal:from'],
            'include_unlaunched' => ['nullable', 'boolean'],
            'view'             => ['nullable', 'in:native,usd'],
        ]);

        $from = Carbon::parse($request->from);
        $to   = Carbon::parse($request->to);
        $includeUnlaunched = $request->boolean('include_unlaunched', false);

        // Fetch all data sets
        $revenue    = $this->reports->revenueByCountry($from, $to);
        $commission = $this->reports->commissionByCountry($from, $to);
        $gatewayFee = $this->reports->gatewayFeeByCountry($from, $to);
        $vat        = $this->reports->vatCollectedByCountry($from, $to);
        $marketer   = $this->reports->marketerPayoutsByCountry($from, $to);
        $ads        = $this->reports->adSpendByCountry($from, $to);

        // Active launched countries to drive the row list
        $countriesQuery = Country::where('is_active', true);
        if (! $includeUnlaunched) {
            $countriesQuery->where('is_launched', true);
        }
        $countries = $countriesQuery->orderBy('name_en')->get(['id', 'name_en', 'iso_code_2', 'flag_emoji', 'currency_code', 'is_launched']);

        // Build one row per country
        $rows = $countries->map(function ($country) use ($revenue, $commission, $gatewayFee, $vat, $marketer, $ads) {
            $rev  = $revenue->firstWhere('country_id', $country->id);
            $com  = $commission->firstWhere('country_id', $country->id);
            $gwf  = $gatewayFee->firstWhere('country_id', $country->id);
            $vatR = $vat->firstWhere('country_id', $country->id);
            $mkt  = $marketer->firstWhere('country_id', $country->id);
            $ad   = $ads->firstWhere('country_id', $country->id);

            $currency = $rev->currency_code ?? $country->currency_code;

            return [
                'country_id'      => $country->id,
                'country_name'    => $country->name_en,
                'flag_emoji'      => $country->flag_emoji,
                'iso_code_2'      => $country->iso_code_2,
                'currency_code'   => $currency,
                'is_launched'     => $country->is_launched,
                'order_count'     => $rev ? (int) $rev->order_count : 0,
                'revenue'   => $rev ? (int) $rev->total_cents : 0,
                'commission_cents'=> $com ? (int) $com->commission_cents : 0,
                'gateway_fee_cents'=> $gwf ? (int) $gwf->gateway_fee_cents : 0,
                'vat_cents'       => $vatR ? (int) $vatR->collected_vat_cents : 0,
                'vat_discrepancy' => $vatR?->has_vat_discrepancy ?? false,
                'marketer_cents'  => $mkt ? (int) $mkt->net_cents : 0,
                'ad_revenue_cents'=> $ad ? (int) $ad->spend : 0,
            ];
        });

        // USD conversion data
        $rates = Currency::pluck('exchange_rate_to_base', 'code');
        $rowsWithUsd = $rows->map(function ($row) use ($rates) {
            $rate = $rates[$row['currency_code']] ?? null;
            $row['revenue_usd']    = ($rate && $rate > 0) ? round(($row['revenue'] / 100) / $rate, 2) : null;
            $row['commission_usd'] = ($rate && $rate > 0) ? round(($row['commission_cents'] / 100) / $rate, 2) : null;
            $row['gateway_fee_usd'] = ($rate && $rate > 0) ? round(($row['gateway_fee_cents'] / 100) / $rate, 2) : null;
            $row['vat_usd']        = ($rate && $rate > 0) ? round(($row['vat_cents'] / 100) / $rate, 2) : null;
            $row['marketer_usd']   = ($rate && $rate > 0) ? round(($row['marketer_cents'] / 100) / $rate, 2) : null;
            $row['ad_revenue_usd'] = ($rate && $rate > 0) ? round(($row['ad_revenue_cents'] / 100) / $rate, 2) : null;
            return $row;
        });

        $totalUsd = $rowsWithUsd->sum(fn($r) => $r['revenue_usd'] ?? 0);

        return response()->json([
            'rows'      => $rowsWithUsd->values(),
            'total_usd' => round($totalUsd, 2),
            'as_of'     => now()->toIso8601String(),
        ]);
    }

    public function export(Request $request): Response
    {
        $request->validate([
            'from'             => ['required', 'date'],
            'to'               => ['required', 'date', 'after_or_equal:from'],
            'include_unlaunched' => ['nullable', 'boolean'],
        ]);

        $from = Carbon::parse($request->from);
        $to   = Carbon::parse($request->to);
        $includeUnlaunched = $request->boolean('include_unlaunched', false);

        $revenue    = $this->reports->revenueByCountry($from, $to);
        $commission = $this->reports->commissionByCountry($from, $to);
        $gatewayFee = $this->reports->gatewayFeeByCountry($from, $to);
        $vat        = $this->reports->vatCollectedByCountry($from, $to);
        $marketer   = $this->reports->marketerPayoutsByCountry($from, $to);
        $ads        = $this->reports->adSpendByCountry($from, $to);

        $countriesQuery = Country::where('is_active', true);
        if (! $includeUnlaunched) {
            $countriesQuery->where('is_launched', true);
        }
        $countries = $countriesQuery->orderBy('name_en')->get(['id', 'name_en', 'iso_code_2', 'currency_code', 'is_launched']);

        $rates = Currency::pluck('exchange_rate_to_base', 'code');

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Country', 'ISO', 'Currency', 'Status',
            'Orders',
            'Revenue', 'Revenue (USD est.)',
            'Commission', 'Commission (USD est.)',
            'Gateway Fees (vendor-borne)', 'Gateway Fees (USD est.)',
            'VAT Collected', 'VAT (USD est.)', 'VAT Discrepancy?',
            'Marketer Payouts (net)', 'Marketer Payouts (USD est.)',
            'Ad Revenue', 'Ad Revenue (USD est.)',
            'Period From', 'Period To', 'Export Note',
        ]);

        $note = 'USD estimates use current stored exchange rates and are indicative only — not accounting-grade conversions.';

        foreach ($countries as $country) {
            $rev  = $revenue->firstWhere('country_id', $country->id);
            $com  = $commission->firstWhere('country_id', $country->id);
            $gwf  = $gatewayFee->firstWhere('country_id', $country->id);
            $vatR = $vat->firstWhere('country_id', $country->id);
            $mkt  = $marketer->firstWhere('country_id', $country->id);
            $ad   = $ads->firstWhere('country_id', $country->id);

            $ccy  = $rev->currency_code ?? $country->currency_code;
            $rate = $rates[$ccy] ?? null;

            $toUsd = function ($cents) use ($rate) {
                if ($rate && $rate > 0) {
                    return round(($cents / 100) / $rate, 2);
                }
                return '';
            };

            $fmt = fn($cents) => number_format($cents / 100, 2, '.', '');

            $revCents = $rev ? (int) $rev->total_cents : 0;
            $comCents = $com ? (int) $com->commission_cents : 0;
            $gwfCents = $gwf ? (int) $gwf->gateway_fee_cents : 0;
            $vatCents = $vatR ? (int) $vatR->collected_vat_cents : 0;
            $mktCents = $mkt ? (int) $mkt->net_cents : 0;
            $adCents  = $ad ? (int) $ad->spend : 0;

            fputcsv($handle, [
                $country->name_en,
                $country->iso_code_2,
                $ccy,
                $country->is_launched ? 'Launched' : 'Unlaunched',
                $rev ? (int) $rev->order_count : 0,
                $fmt($revCents) . ' ' . $ccy,
                $toUsd($revCents),
                $fmt($comCents) . ' ' . $ccy,
                $toUsd($comCents),
                $fmt($gwfCents) . ' ' . $ccy,
                $toUsd($gwfCents),
                $fmt($vatCents) . ' ' . $ccy,
                $toUsd($vatCents),
                ($vatR?->has_vat_discrepancy ? 'YES' : 'no'),
                $fmt($mktCents) . ' ' . $ccy,
                $toUsd($mktCents),
                $fmt($adCents) . ' ' . $ccy,
                $toUsd($adCents),
                $from->toDateString(),
                $to->toDateString(),
                $note,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'financial-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
