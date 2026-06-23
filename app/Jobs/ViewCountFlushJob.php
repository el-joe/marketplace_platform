<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ViewCountFlushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $counts = Redis::hgetall('product_views:counts');

        if (empty($counts)) {
            return;
        }

        Redis::del('product_views:counts');

        foreach ($counts as $productId => $count) {
            Product::where('id', $productId)->increment('view_count', (int) $count);
        }
    }
}
