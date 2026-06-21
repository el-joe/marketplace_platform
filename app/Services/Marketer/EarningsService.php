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

    public function summary(Marketer $marketer): array
    {
        $base = MarketerConversion::where('marketer_id', $marketer->id);

        return [
            'total_pending_cents'      => (int) (clone $base)->where('status', 'pending')->sum('commission_amount_cents'),
            'total_approved_cents'     => (int) (clone $base)->where('status', 'approved')->sum('commission_amount_cents'),
            'total_paid_lifetime_cents'=> (int) (clone $base)->where('status', 'paid')->sum('commission_amount_cents'),
            'this_month_cents'         => (int) (clone $base)
                ->whereIn('status', ['approved', 'paid'])
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('commission_amount_cents'),
            'currency'                 => $marketer->conversions()->value('currency') ?? 'SAR',
        ];
    }

    public function findConversion(Marketer $marketer, string $conversionId): ?MarketerConversion
    {
        return MarketerConversion::where('id', $conversionId)
            ->where('marketer_id', $marketer->id)
            ->with(['campaign:id,name,attribution_model', 'order:id,order_number'])
            ->first();
    }

    public function listPayouts(Marketer $marketer): LengthAwarePaginator
    {
        return MarketerPayout::where('marketer_id', $marketer->id)
            ->orderByDesc('created_at')
            ->paginate(20);
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
