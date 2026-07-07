<?php

namespace App\Services\Marketer;

use App\Models\Marketer;
use App\Models\MarketerConversion;
use App\Models\MarketerPayout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EarningsService
{
    public function list(Marketer $marketer, array $filters = []): LengthAwarePaginator
    {
        $query = MarketerConversion::where('marketer_id', $marketer->id)
            ->with('campaign:id,name');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Returns per-currency summaries.  Never sums across currencies — a marketer
     * may earn in SAR, AED, EGP simultaneously if they promote multi-country listings.
     *
     * Shape: ['by_currency' => ['SAR' => [...], 'AED' => [...]], 'currencies' => ['SAR','AED']]
     */
    public function summary(Marketer $marketer): array
    {
        $base = MarketerConversion::where('marketer_id', $marketer->id);

        $statuses = ['pending', 'approved', 'paid'];
        $byCurrency = [];

        foreach ($statuses as $status) {
            $rows = (clone $base)->where('status', $status)
                ->selectRaw('currency, SUM(commission_amount_cents) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency');
            foreach ($rows as $currency => $total) {
                $byCurrency[$currency][$status . '_cents'] = (int) $total;
            }
        }

        $thisMonth = (clone $base)
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('currency, SUM(commission_amount_cents) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        foreach ($thisMonth as $currency => $total) {
            $byCurrency[$currency]['this_month_cents'] = (int) $total;
        }

        // Ensure all statuses exist for every currency to avoid missing-key errors in views
        foreach ($byCurrency as $currency => &$data) {
            $data += ['pending_cents' => 0, 'approved_cents' => 0, 'paid_cents' => 0, 'this_month_cents' => 0];
        }
        unset($data);

        return [
            'by_currency' => $byCurrency,
            'currencies'  => array_keys($byCurrency),
        ];
    }

    public function findConversion(Marketer $marketer, string $conversionId): ?MarketerConversion
    {
        return MarketerConversion::where('id', $conversionId)
            ->where('marketer_id', $marketer->id)
            ->with(['campaign:id,name,attribution_model', 'order:id,order_number'])
            ->first();
    }

    public function listPayouts(Marketer $marketer, array $filters = []): LengthAwarePaginator
    {
        $query = MarketerPayout::where('marketer_id', $marketer->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function findPayout(Marketer $marketer, string $payoutId): ?MarketerPayout
    {
        return MarketerPayout::where('id', $payoutId)
            ->where('marketer_id', $marketer->id)
            ->first();
    }

    public function payoutConversions(MarketerPayout $payout): \Illuminate\Database\Eloquent\Collection
    {
        // Conversions are linked to a payout by being marked paid within the payout period
        return MarketerConversion::where('marketer_id', $payout->marketer_id)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                $payout->period_start->startOfDay(),
                $payout->period_end->endOfDay(),
            ])
            ->with('campaign:id,name')
            ->get();
    }
}
