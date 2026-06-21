<?php

namespace App\Services\Customer;

use App\Models\Country;
use App\Models\Product;
use Illuminate\Support\Facades\Redis;

class ProductViewService
{
    private const REDIS_PREFIX = 'product_views:';

    public function logView(
        Product $product,
        Country $country,
        ?string $customerId,
        string $sessionId,
        string $source = 'direct',
        ?string $referrerUrl = null,
    ): void {
        // Increment Redis counter; ViewCountFlushJob persists to DB every 5 min
        Redis::hincrby(self::REDIS_PREFIX . 'counts', $product->id, 1);

        dispatch(new \App\Jobs\ProductViewLogJob(
            productId: $product->id,
            customerId: $customerId,
            sessionId: $sessionId,
            source: $source,
            referrerUrl: $referrerUrl,
        ))->afterResponse();
    }
}
