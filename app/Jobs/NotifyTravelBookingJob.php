<?php

namespace App\Jobs;

use App\Models\TravelBooking;
use App\Notifications\TravelAgency\NewBookingReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyTravelBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly TravelBooking $booking) {}

    public function handle(): void
    {
        $this->booking->loadMissing('package.agency');
        $this->booking->package->agency->notify(new NewBookingReceived($this->booking));
    }
}
