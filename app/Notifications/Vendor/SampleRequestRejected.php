<?php

namespace App\Notifications\Vendor;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;

class SampleRequestRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'sample_request_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Sample Request Rejected',
            'message'           => "You have rejected a marketer's sample request.",
            'url'               => route('partner.marketer-samples.index'),
            'sample_request_id' => $this->sampleRequest->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
