<?php

namespace App\Jobs;

use App\Enums\MarketerTrackingStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\MarketerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApproveMarketerCommissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Auto-approve pending conversions that are:
        //  • older than 14 days
        //  • on completed/delivered orders
        //  • with no unrejected return requests
        MarketerConversion::where('status', MarketerTrackingStatus::Pending)
            ->where('created_at', '<=', now()->subDays(14))
            ->whereHas(
                'order',
                fn($q) =>
                $q->whereIn('status', ['completed', 'delivered'])
            )
            ->whereDoesntHave(
                'order.returnRequests',
                fn($q) =>
                $q->where('status', '!=', ReturnRequestStatus::Rejected)
            )
            ->update([
                'status' => MarketerTrackingStatus::Approved,
                'approved_at' => now(),
            ]);
    }
}
