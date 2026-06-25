<?php

namespace App\Jobs;

use App\Models\ClassifiedInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifySellerNewInquiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ClassifiedInquiry $inquiry) {}

    public function handle(): void
    {
        // Notify the listing's seller (Customer or Vendor) of a new inquiry.
        // Wire to the platform's notification channel once classifieds notifications are defined.
    }
}
