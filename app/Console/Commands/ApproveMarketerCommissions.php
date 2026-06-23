<?php

namespace App\Console\Commands;

use App\Models\MarketerConversion;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApproveMarketerCommissions extends Command
{
    protected $signature = 'marketers:approve-commissions';
    protected $description = 'Auto-approve pending marketer commissions after the 14-day return window.';

    public function handle(): int
    {
        $cutoff = now()->subDays(14);

        // Find pending conversions older than 14 days whose orders are delivered/completed
        // and have no active return requests
        $query = MarketerConversion::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['delivered', 'completed']);
            })
            ->whereDoesntHave('order.returnRequests', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'in_progress']);
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No commissions to approve.');
            return self::SUCCESS;
        }

        $approved = 0;

        $query->chunkById(100, function ($conversions) use (&$approved) {
            foreach ($conversions as $conversion) {
                DB::transaction(function () use ($conversion, &$approved) {
                    $conversion->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);
                    $approved++;
                });
            }
        });

        $this->info("Approved {$approved} marketer commissions.");
        return self::SUCCESS;
    }
}
