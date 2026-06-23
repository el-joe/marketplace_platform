<?php

namespace App\Services\Delivery;

use App\Models\AgentLocationHistory;
use App\Models\DeliveryAgent;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class LocationTrackingService
{
    private const THROTTLE_SECONDS = 30;

    public function update(DeliveryAgent $agent, float $lat, float $lng): void
    {
        $key = 'delivery_location:' . $agent->id;

        // Redis-backed per-agent throttle; bypasses route-level throttle limits
        if (Redis::exists($key)) {
            $ttl = Redis::ttl($key);
            throw new RuntimeException(
                "Location update rate limit: please wait {$ttl} second(s) before updating again."
            );
        }

        Redis::setex($key, self::THROTTLE_SECONDS, 1);

        $agent->update([
            'current_latitude'  => $lat,
            'current_longitude' => $lng,
            'last_location_at'  => now(),
        ]);

        AgentLocationHistory::create([
            'agent_id'    => $agent->id,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'recorded_at' => now(),
        ]);
    }
}
