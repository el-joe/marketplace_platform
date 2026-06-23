<?php

namespace App\Services\Delivery;

use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentShift;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShiftService
{
    public function start(DeliveryAgent $agent): DeliveryAgentShift
    {
        if ($agent->status === 'on_shift') {
            throw new RuntimeException('You are already on shift.');
        }

        if (is_null($agent->current_latitude) || is_null($agent->current_longitude)) {
            throw new RuntimeException('You must send your current location before starting a shift.');
        }

        return DB::transaction(function () use ($agent): DeliveryAgentShift {
            $agent->update(['status' => 'on_shift', 'is_available' => true]);

            return DeliveryAgentShift::create([
                'agent_id'         => $agent->id,
                'shift_date'       => now()->toDateString(),
                'zone_id'          => $agent->zone_id,
                'scheduled_start'  => now()->toTimeString(),
                'scheduled_end'    => now()->addHours(8)->toTimeString(),
                'actual_start'     => now(),
                'status'           => 'active',
            ]);
        });
    }

    public function end(DeliveryAgent $agent): void
    {
        if ($agent->status !== 'on_shift') {
            throw new RuntimeException('You are not currently on shift.');
        }

        $hasActiveAssignment = $agent->assignments()
            ->whereIn('status', ['accepted', 'picked_up'])
            ->exists();

        if ($hasActiveAssignment) {
            throw new RuntimeException('You have an active delivery in progress. Complete or fail it before ending your shift.');
        }

        DB::transaction(function () use ($agent): void {
            $agent->update([
                'status'       => 'active',
                'is_available' => false,
            ]);

            DeliveryAgentShift::where('agent_id', $agent->id)
                ->where('status', 'active')
                ->whereDate('shift_date', now()->toDateString())
                ->update([
                    'actual_end' => now(),
                    'status'     => 'completed',
                ]);
        });
    }
}
