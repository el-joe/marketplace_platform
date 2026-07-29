<?php

namespace App\Jobs;

use App\Models\VendorInfluencerPromotionRequestItem;
use App\Notifications\Marketer\NewPromotionRequestReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendInfluencerPromotionWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly VendorInfluencerPromotionRequestItem $item) {}

    public function handle(): void
    {
        Notification::send($this->item->marketer, new NewPromotionRequestReceived($this->item));

        Log::info('Influencer promotion WhatsApp notification sent', [
            'promotion_request_item_id' => $this->item->id,
            'promotion_request_id' => $this->item->promotion_request_id,
            'marketer_id' => $this->item->marketer_id,
        ]);
    }
}
