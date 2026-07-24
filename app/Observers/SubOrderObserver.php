<?php

namespace App\Observers;

use App\Models\SubOrder;
use App\Models\WarrantyPurchase;
use Illuminate\Support\Facades\DB;

class SubOrderObserver
{
    public function updating(SubOrder $subOrder): void
    {
        if (! $subOrder->isDirty('status')) {
            return;
        }

        $newStatus = $subOrder->status instanceof \BackedEnum
            ? $subOrder->status->value
            : $subOrder->status;

        if ($newStatus !== 'delivered') {
            return;
        }

        $warrantyPurchaseIds = $subOrder->items()
            ->whereNotNull('warranty_purchase_id')
            ->pluck('warranty_purchase_id');

        $warrantyPurchases = WarrantyPurchase::query()
            ->whereIn('id', $warrantyPurchaseIds)
            ->get();

        if ($warrantyPurchases->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($warrantyPurchases): void {
            foreach ($warrantyPurchases as $warrantyPurchase) {
                $durationMonths = $warrantyPurchase->plan_snapshot['duration_months'] ?? 0;

                $warrantyPurchase->update([
                    'coverage_starts_at' => today(),
                    'coverage_ends_at' => today()->addMonths($durationMonths),
                    'status' => 'active',
                ]);
            }
        });
    }
}
