<?php

namespace App\Jobs;

use App\Models\VendorInfluencerPromotionRequest;
use App\Notifications\Vendor\FulfillmentReceiptRequired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendVendorFulfillmentAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly VendorInfluencerPromotionRequest $request) {}

    public function handle(): void
    {
        Notification::send($this->request->vendor->vendorAdmins, new FulfillmentReceiptRequired($this->request));
    }
}
